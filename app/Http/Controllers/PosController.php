<?php

namespace App\Http\Controllers;

use App\Enums\Payments\PaymentMethod;
use App\Filament\Merchant\Resources\StockOpnames\StockOpnameResource;
use App\Models\Activity;
use App\Models\Customers\Customer;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use App\Models\Transactions\Transaction;
use App\Services\PromotionService;
use App\Services\TransactionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PosController extends Controller
{
    private Model|Merchant|null $merchant = null;

    private function merchant()
    {
        if ($this->merchant ?? false) {
            return $this->merchant;
        }

        if (filament()->getTenant()) {
            return $this->merchant = filament()->getTenant();
        }

        $merchant = $this->merchant = auth()->user()->merchants()->firstOrFail();
        filament()->setTenant($merchant);

        return $merchant;
    }

    public function index(Request $request): View
    {
        $merchant = $this->merchant();

        $catalog = $this->catalog();

        $customers = Customer::query()->where('merchant_id', $merchant->id)
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        $activeOpname = StockOpnameResource::getActiveLockedOpname();

        return view('pos.index', [
            'merchant' => $merchant->toArray(),
            ...$catalog,
            'customers' => $customers,
            'promotions' => $catalog['promotions'],
            'isTransactionLocked' => $activeOpname !== null,
            'activeOpnameNumber' => $activeOpname?->opname_number,
        ]);
    }

    /**
     * Muat ulang katalog produk tanpa reload halaman (dipakai tombol Reload Product).
     * Mengembalikan data identik dengan initial render index() agar state JS konsisten.
     */
    public function products(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            ...$this->catalog(),
        ]);
    }

    /**
     * Riwayat transaksi POS (list): search + filter + pagination via JSON.
     * Dipakai halaman #view-history di POS tanpa reload halaman.
     */
    public function history(Request $request): JsonResponse
    {
        $merchant = $this->merchant();

        $query = Transaction::query()
            ->where('merchant_id', $merchant->id)
            ->with('customer')
            ->withCount('transactionItems')
            ->orderByDesc('transaction_at');

        // Search: nomor transaksi atau nama pelanggan.
        if ($q = trim((string) $request->query('q', ''))) {
            $query->where(function ($builder) use ($q): void {
                $builder->where('transaction_number', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$q}%"));
            });
        }

        // Filter metode bayar.
        if ($method = $request->query('payment_method')) {
            $query->where('payment_method', $method);
        }

        // Filter rentang tanggal (transaction_at).
        if ($from = $request->query('from')) {
            $query->whereDate('transaction_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('transaction_at', '<=', $to);
        }

        $perPage = min(max((int) $request->query('per_page', 5), 1), 50);
        $transactions = $query->paginate($perPage)->withQueryString();

        // Nama kasir diambil dari activitylog terakhir per transaksi.
        // Ambil semua sekaligus (satu query) untuk menghindari N+1.
        $items = collect($transactions->items());
        $latestActivities = Activity::query()
            ->where('subject_type', (new Transaction)->getMorphClass())
            ->whereIn('subject_id', $items->pluck('id'))
            ->with('causer')
            ->orderByDesc('id')
            ->get()
            ->groupBy('subject_id')
            ->map(fn ($group) => $group->first());

        return response()->json([
            'success' => true,
            'data' => $items->map(fn (Transaction $t): array => [
                'id' => $t->id,
                'transaction_number' => $t->transaction_number,
                'transaction_at' => $t->transaction_at->toIso8601String(),
                'payment_method' => $t->payment_method->value,
                'payment_label' => $t->payment_method->getLabel(),
                'total_amount' => $t->total_amount,
                'items_count' => $t->items_count,
                'line_items_count' => $t->transaction_items_count ?? 0,
                'customer_name' => $t->customer?->name,
                'cashier' => $latestActivities->get($t->id)?->causer?->getAttribute('name'),
            ]),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
            ],
        ]);
    }

    /**
     * Detail transaksi untuk modal riwayat & cetak ulang struk.
     * Data lengkap dibangun ulang dari DB dengan format yang sama dengan
     * lastTransactionData di sisi klien (dipakai pos-bluetooth.printReceipt).
     */
    public function historyDetail(Request $request, int $id): JsonResponse
    {
        $merchant = $this->merchant();

        /** @var Transaction|null $transaction */
        $transaction = Transaction::query()
            ->where('merchant_id', $merchant->id)
            ->with(['transactionItems', 'promotionRedemptions.transactionItem', 'customer'])
            ->find($id);

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan.',
            ], 404);
        }

        // Rincian diskon per promo diambil dari promotion_redemptions (bukan kolom discounts
        // yang sudah dihapus). Dikelompokkan per promo, hanya yang nominal > 0.
        $discounts = $transaction->promotionRedemptions
            ->groupBy('promotion_id')
            ->map(fn ($group): array => [
                'desc' => $group->first()->transactionItem?->promotionData()['name'] ?? 'Promo',
                'amount' => (int) $group->sum('discount_amount'),
            ])
            ->filter(fn (array $d): bool => $d['amount'] > 0)
            ->values()
            ->all();

        // amount_received & change disimpan di DB sejak kolom ditambahkan;
        // transaksi lama fallback ke total (change 0).
        $total = $transaction->total_amount;

        return response()->json([
            'success' => true,
            'transaction' => [
                'id' => $transaction->id,
                'storeName' => $merchant->name,
                'storeAddress' => $merchant->address,
                'transactionNumber' => $transaction->transaction_number,
                'date' => $transaction->transaction_at->format('d/m/Y H:i'),
                'cashier' => $this->cashierName($transaction),
                'customer' => optional($transaction->customer)->name ?? 'Umum',
                'paymentMethod' => $transaction->payment_method->value,
                'paymentLabel' => $transaction->payment_method->getLabel(),
                'items' => $transaction->transactionItems->map(fn ($item): array => [
                    'name' => $item->productData()['name'] ?? 'Item',
                    'qty' => $item->quantity,
                    'price' => $item->unit_price,
                    'original_price' => $item->original_price ?? $item->unit_price,
                    'discount_amount' => $item->discount_amount,
                    'subtotal' => $item->subtotal,
                    'promotionName' => $item->promotionData()['name'] ?? null,
                    'is_free' => $item->unit_price === 0,
                ])->values()->all(),
                'subtotal' => $transaction->subtotal,
                'discount' => $transaction->discount,
                'discounts' => $discounts,
                'total' => $total,
                'amountReceived' => $transaction->amount_received ?? $total,
                'change' => $transaction->change ?? 0,
                'notes' => $transaction->notes,
            ],
        ]);
    }

    /**
     * Nama kasir (causer) dari activitylog terakhir pada transaksi.
     * Fallback: null jika tidak tercatat (klien menampilkan '-').
     */
    private function cashierName(Transaction $transaction): ?string
    {
        $activity = Activity::query()
            ->forSubject($transaction)
            ->with('causer')
            ->latest()
            ->first();

        $causer = $activity?->causer;

        if ($causer === null) {
            return null;
        }

        return $causer->getAttribute('name');
    }

    /**
     * Data katalog POS: produk aktif, kategori, badge promo, dan harga promo efektif.
     * Dipakai baik oleh initial render (index) maupun reload via API (products).
     */
    private function catalog(): array
    {
        $merchant = $this->merchant();

        $products = Product::query()->where('merchant_id', $merchant->id)
            ->where('is_active', true)
            ->with('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Product $p): array => [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category->name,
                'price' => $p->selling_price,
                'image' => $p->image_path ? asset('storage/'.$p->image_path) : null,
            ]);

        $categories = $products->pluck('category')->unique()->values();

        /** @var PromotionService $promotionService */
        $promotionService = app(PromotionService::class);
        $promotions = $promotionService->getActivePromotionsForMerchant($merchant->id);

        // Peta badge promo per produk (untuk ditampilkan di kartu produk POS).
        $productCategories = $products->mapWithKeys(
            fn (array $p): array => [$p['id'] => $p['category']],
        );

        $promoBadges = [];
        foreach ($promotions as $promotion) {
            foreach ($promotion->conditions as $condition) {
                if ($condition->product_id !== null) {
                    $promoBadges[$condition->product_id][] = $promotion->name;

                    continue;
                }

                foreach ($productCategories as $productId => $categoryName) {
                    if ($categoryName === $condition->category?->name) {
                        $promoBadges[$productId][] = $promotion->name;
                    }
                }
            }
        }

        // Harga promo efektif per produk (harga awal dicoret + harga promo di kartu).
        $productsById = $products->mapWithKeys(fn (array $p): array => [$p['id'] => $p]);
        $effectivePrices = $promotionService->getEffectivePricesForProducts(
            Product::query()->whereIn('id', $productsById->keys())->get()->keyBy('id'),
            $promotions,
        );

        return [
            'products' => $products,
            'categories' => $categories,
            'promoBadges' => $promoBadges,
            'effectivePrices' => $effectivePrices,
            'promotions' => $promotions,
        ];
    }

    /**
     * Preview checkout: evaluasi promo server-side tanpa menyimpan transaksi.
     * Dipanggil POS saat kasir membuka halaman checkout (tanpa reload halaman).
     */
    public function preview(Request $request): JsonResponse
    {
        $merchant = $this->merchant();

        if (StockOpnameResource::isTransactionLocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi dikunci karena sedang ada stock opname aktif.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('merchant_id', $merchant->id)],
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        /** @var PromotionService $promotionService */
        $promotionService = app(PromotionService::class);
        $result = $promotionService->evaluateCart(
            $merchant->id,
            $validator->validated()['items'],
        );

        return response()->json([
            'success' => true,
            ...$result,
        ]);
    }

    public function process(Request $request): JsonResponse
    {
        $merchant = $this->merchant();

        if (StockOpnameResource::isTransactionLocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi dikunci karena sedang ada stock opname aktif.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|in:qris,cash',
            'idempotency_key' => 'required|string|uuid',
            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('merchant_id', $merchant->id)],
            'items.*.quantity' => 'required|integer|min:1',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $validated = $validator->validated();

        // Resolve customer: prefer existing id, otherwise case-insensitive find / create from trimmed name
        $customerId = $validated['customer_id'] ?? null;
        if (! $customerId && ! empty($validated['customer_name'])) {
            $trimmedName = trim((string) $validated['customer_name']);
            if ($trimmedName !== '') {
                $customer = Customer::query()
                    ->where('merchant_id', $merchant->id)
                    ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($trimmedName)])
                    ->first();

                if (! $customer) {
                    $customer = Customer::query()->create([
                        'merchant_id' => $merchant->id,
                        'name' => $trimmedName,
                    ]);
                }

                $customerId = $customer->id;
            }
        }

        // Evaluasi promo server-side sebagai satu-satunya sumber kebenaran harga.
        // Klien hanya mengirim product_id + quantity; total tidak bisa dimanipulasi.
        $promotionService = app(PromotionService::class);
        $result = $promotionService->evaluateCart($merchant->id, $validated['items']);

        $products = Product::query()->whereIn('id', collect($result['items'])->pluck('product_id'))
            ->with(['category', 'productMaterials.item'])
            ->get()
            ->keyBy('id');

        $transactionItems = [];

        foreach ($result['items'] as $line) {
            $product = $products->get($line['product_id']);

            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk tidak ditemukan.',
                ], 422);
            }

            $transactionItems[] = [
                'product_id' => $product->id,
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'original_price' => $line['original_price'],
                'discount_amount' => $line['discount_amount'],
                'promotion_id' => $line['promotion_id'],
                'promotion_data' => $line['promotion_data'] ?? null,
                'subtotal' => $line['subtotal'],
                'product_data' => $product->toArray(),
            ];
        }

        $totalAmount = $result['total'];
        $amountReceived = (int) $request->input('amount_received', $totalAmount);

        // QRIS: nominal harus sama persis dengan total (tanpa kembalian).
        if ($validated['payment_method'] === PaymentMethod::Qris->value) {
            $amountReceived = $totalAmount;
        }

        // Cash: uang diterima tidak boleh kurang dari total transaksi.
        if ($validated['payment_method'] === PaymentMethod::Cash->value && $amountReceived < $totalAmount) {
            return response()->json([
                'success' => false,
                'message' => 'Uang diterima tidak boleh kurang dari total transaksi.',
            ], 422);
        }

        $change = max(0, $amountReceived - $totalAmount);

        try {
            $transaction = app(TransactionService::class)->create([
                'payment_method' => $validated['payment_method'],
                'idempotency_key' => $validated['idempotency_key'],
                'transactionItems' => $transactionItems,
                'subtotal' => $result['subtotal'],
                'discount' => $result['discount_total'],
                'total_amount' => $totalAmount,
                'amount_received' => $amountReceived,
                'change' => $change,
                'items_count' => array_sum(array_column($transactionItems, 'quantity')),
                'customer_id' => $customerId,
                'notes' => $validated['notes'] ?? null,
                'transaction_at' => now(),
            ], $merchant->id);

            $promotionService->recordRedemptions($transaction, $result);

            session()->forget('pos_cart');

            return response()->json([
                'success' => true,
                'transaction' => [
                    'number' => $transaction->transaction_number,
                    'total' => $transaction->total_amount,
                    'change' => $transaction->change,
                ],
            ]);
        } catch (UniqueConstraintViolationException $e) {
            // Request duplikat dengan idempotency_key sama: transaksi sudah tersimpan,
            // kembalikan data transaksi yang ada (no-op) supaya tidak terjadi duplikat.
            $existing = Transaction::query()->where('merchant_id', $merchant->id)
                ->where('idempotency_key', $validated['idempotency_key'])
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => true,
                    'transaction' => [
                        'number' => $existing->transaction_number,
                        'total' => $existing->total_amount,
                        'change' => $existing->change ?? max(0, (int) $request->input('amount_received', $existing->total_amount) - $existing->total_amount),
                    ],
                ]);
            }

            throw $e;
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $firstError = collect($errors)->flatten()->first();

            return response()->json([
                'success' => false,
                'message' => $firstError ?? 'Stok bahan tidak mencukupi.',
            ], 422);
        }
    }
}
