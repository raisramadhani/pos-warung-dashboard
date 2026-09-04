<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string|null $log_name
 * @property string $description
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string|null $event
 * @property string|null $causer_type
 * @property int|null $causer_id
 * @property \Illuminate\Support\Collection<array-key, mixed>|null $attribute_changes
 * @property \Illuminate\Support\Collection<array-key, mixed>|null $properties
 * @property string|null $batch_uuid
 * @property int|null $tenant_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|null $causer
 * @property-read \Illuminate\Database\Eloquent\Model|null $subject
 * @method static Builder<static>|Activity causedBy(\Illuminate\Database\Eloquent\Model $causer)
 * @method static Builder<static>|Activity forEvent(\Spatie\Activitylog\Enums\ActivityEvent|string $event)
 * @method static Builder<static>|Activity forSubject(\Illuminate\Database\Eloquent\Model $subject)
 * @method static Builder<static>|Activity inLog(array|string ...$logNames)
 * @method static Builder<static>|Activity newModelQuery()
 * @method static Builder<static>|Activity newQuery()
 * @method static Builder<static>|Activity query()
 * @mixin \Eloquent
 */
	class Activity extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $merchant_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, \App\Models\Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read Merchant|null $merchant
 * @property-read Collection<int, Product> $products
 * @property-read int|null $products_count
 * @property-read bool|null $products_exists
 * @method static \Database\Factories\CategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category withoutTrashed()
 * @mixin \Eloquent
 */
	class Category extends \Eloquent {}
}

namespace App\Models\Customers{
/**
 * @property int $id
 * @property int $merchant_id
 * @property string $name
 * @property string|null $phone
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Merchant|null $merchant
 * @property-read Collection<int, Transaction> $transactions
 * @property-read int|null $transactions_count
 * @property-read bool|null $transactions_exists
 * @method static \Database\Factories\Customers\CustomerFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer query()
 * @mixin \Eloquent
 */
	class Customer extends \Eloquent {}
}

namespace App\Models\Inventories{
/**
 * @property int $id
 * @property int|null $item_id
 * @property string $name
 * @property string|null $description
 * @property Carbon $purchase_date
 * @property numeric $purchase_price
 * @property int $useful_life_months
 * @property numeric $salvage_value
 * @property AssetStatus $status
 * @property Carbon|null $last_depreciation_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read Collection<int, \App\Models\Inventories\AssetDepreciation> $depreciations
 * @property-read int|null $depreciations_count
 * @property-read bool|null $depreciations_exists
 * @property-read float $accumulated_depreciation
 * @property-read float $current_book_value
 * @property-read float $monthly_depreciation
 * @property-read \App\Models\Inventories\Item|null $item
 * @method static \Database\Factories\Inventories\AssetFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset withoutTrashed()
 * @mixin \Eloquent
 */
	class Asset extends \Eloquent {}
}

namespace App\Models\Inventories{
/**
 * @property int $id
 * @property int|null $asset_id
 * @property Carbon $period_date
 * @property numeric $depreciation_amount
 * @property numeric $book_value_before
 * @property numeric $book_value_after
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \App\Models\Inventories\Asset|null $asset
 * @method static \Database\Factories\Inventories\AssetDepreciationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetDepreciation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetDepreciation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetDepreciation query()
 * @mixin \Eloquent
 */
	class AssetDepreciation extends \Eloquent {}
}

namespace App\Models\Inventories{
/**
 * Distribusi mencatat pengiriman barang dari gudang/merchant sumber ke merchant tujuan.
 *
 * Alur:
 * 1. SPV membuat distribusi → status = Sent, stok gudang sumber berkurang (quantity_sent).
 * 2. Staff outlet memeriksa item satu per satu (verifikasi), mengisi quantity_received.
 * 3. Setelah semua item diperiksa (qty sesuai atau tidak), staff klik Finish.
 * 4. Saat status Finished, stok merchant tujuan bertambah sesuai quantity_received per item.
 *
 * @property int $id
 * @property int|null $source_merchant_id
 * @property int|null $merchant_id
 * @property DistributionStatus $status
 * @property string|null $notes
 * @property Carbon|null $sent_at
 * @property Carbon|null $received_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read Collection<int, \App\Models\Inventories\DistributionItem> $items
 * @property-read int|null $items_count
 * @property-read bool|null $items_exists
 * @property-read Merchant|null $merchant
 * @property-read Merchant|null $sourceMerchant
 * @method static \Database\Factories\Inventories\DistributionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Distribution newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Distribution newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Distribution onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Distribution query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Distribution withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Distribution withoutTrashed()
 * @mixin \Eloquent
 */
	class Distribution extends \Eloquent {}
}

namespace App\Models\Inventories{
/**
 * @property int $id
 * @property int|null $distribution_id
 * @property int|null $item_id
 * @property int $quantity_sent
 * @property int $quantity_received
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read \App\Models\Inventories\Distribution|null $distribution
 * @property-read \App\Models\Inventories\Item|null $item
 * @method static \Database\Factories\Inventories\DistributionItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DistributionItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DistributionItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DistributionItem onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DistributionItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DistributionItem withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DistributionItem withoutTrashed()
 * @mixin \Eloquent
 */
	class DistributionItem extends \Eloquent {}
}

namespace App\Models\Inventories{
/**
 * Goods Receipt mencatat setiap event penerimaan barang dari supplier.
 *
 * Setiap pengiriman dari supplier (bisa parsial) dibuat sebagai goods receipt
 * terpisah yang terkait ke purchase order. Saat goods receipt diverifikasi,
 * stok langsung masuk ke merchant_stocks (untuk bahan baku) atau asset
 * record dibuat (untuk alat).
 *
 * @property int $id
 * @property int|null $purchase_order_id
 * @property int|null $merchant_id
 * @property string $receipt_number
 * @property ReceiptSourceType $source_type
 * @property GoodsReceiptStatus $status
 * @property string|null $notes
 * @property Carbon|null $verified_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read Collection<int, \App\Models\Inventories\GoodsReceiptItem> $items
 * @property-read int|null $items_count
 * @property-read bool|null $items_exists
 * @property-read Merchant|null $merchant
 * @property-read \App\Models\Inventories\PurchaseOrder|null $purchaseOrder
 * @method static \Database\Factories\Inventories\GoodsReceiptFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceipt withoutTrashed()
 * @mixin \Eloquent
 */
	class GoodsReceipt extends \Eloquent {}
}

namespace App\Models\Inventories{
/**
 * @property int $id
 * @property int|null $goods_receipt_id
 * @property int|null $item_id
 * @property int|null $quantity_ordered
 * @property int $quantity_received
 * @property numeric|null $unit_price
 * @property numeric|null $subtotal
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read \App\Models\Inventories\GoodsReceipt|null $goodsReceipt
 * @property-read \App\Models\Inventories\Item|null $item
 * @method static \Database\Factories\Inventories\GoodsReceiptItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsReceiptItem withoutTrashed()
 * @mixin \Eloquent
 */
	class GoodsReceiptItem extends \Eloquent {}
}

namespace App\Models\Inventories{
/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property ItemType $type
 * @property string $unit
 * @property string|null $description
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read Collection<int, \App\Models\Inventories\Asset> $assets
 * @property-read int|null $assets_count
 * @property-read bool|null $assets_exists
 * @property-read Collection<int, \App\Models\Inventories\DistributionItem> $distributionItems
 * @property-read int|null $distribution_items_count
 * @property-read bool|null $distribution_items_exists
 * @property-read Collection<int, \App\Models\Inventories\MerchantStock> $merchantStocks
 * @property-read int|null $merchant_stocks_count
 * @property-read bool|null $merchant_stocks_exists
 * @property-read Collection<int, \App\Models\Inventories\PurchaseOrderItem> $purchaseOrderItems
 * @property-read int|null $purchase_order_items_count
 * @property-read bool|null $purchase_order_items_exists
 * @method static \Database\Factories\Inventories\ItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item withoutTrashed()
 * @mixin \Eloquent
 */
	class Item extends \Eloquent {}
}

namespace App\Models\Inventories{
/**
 * Saldo stok saat ini per merchant per item.
 *
 * Berbeda dengan StockMovement yang mencatat riwayat mutasi,
 * model ini menyimpan jumlah stok terkini (running balance).
 * Ditulis oleh StockMovementService::increase() / decrease(),
 * dibaca untuk validasi stok dan tampilan daftar stok.
 *
 * @property int $id
 * @property int $merchant_id
 * @property int $item_id
 * @property int $quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \App\Models\Inventories\Item|null $item
 * @property-read Merchant|null $merchant
 * @method static \Database\Factories\Inventories\MerchantStockFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantStock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantStock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantStock query()
 * @mixin \Eloquent
 */
	class MerchantStock extends \Eloquent {}
}

namespace App\Models\Inventories{
/**
 * Purchase Order mencatat pemesanan barang ke supplier.
 *
 * Alur:
 * 1. SPV membuat PO → status = Approved (auto, ke depannya oleh accounting).
 * 2. Saat barang datang, SPV klik "Terima Barang" di halaman detail PO
 *    → goods receipt dibuat (bisa parsial, satu PO bisa banyak goods receipt).
 * 3. SPV merubah status  goods receipt menjadi verifikasi maka data tersimpan ke stok.
 *    → Per item (quantity_received) stok masuk ke merchant_stocks (bahan baku) atau asset dibuat (alat).
 * 4. Semua item sudah diterima lengkap → status otomatis Finished.
 *
 * Jumlah diterima per item dihitung dari goods_receipt_items (single source of truth),
 * bukan disimpan di purchase_order_items.
 *
 * @property int $id
 * @property int|null $merchant_id
 * @property int|null $supplier_id
 * @property string $po_number
 * @property PurchaseOrderSource $source_type
 * @property PurchaseOrderStatus $status
 * @property string|null $notes
 * @property Carbon|null $approved_at
 * @property Carbon|null $finished_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read User|null $creator
 * @property-read bool $is_complete
 * @property-read Collection<int, \App\Models\Inventories\GoodsReceipt> $goodsReceipts
 * @property-read int|null $goods_receipts_count
 * @property-read bool|null $goods_receipts_exists
 * @property-read Collection<int, \App\Models\Inventories\PurchaseOrderItem> $items
 * @property-read int|null $items_count
 * @property-read bool|null $items_exists
 * @property-read Merchant|null $merchant
 * @property-read Supplier|null $supplier
 * @method static \Database\Factories\Inventories\PurchaseOrderFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrder withoutTrashed()
 * @mixin \Eloquent
 */
	class PurchaseOrder extends \Eloquent {}
}

namespace App\Models\Inventories{
/**
 * @property int $id
 * @property int|null $purchase_order_id
 * @property int|null $item_id
 * @property int $quantity_ordered
 * @property numeric $unit_price_ordered
 * @property numeric $subtotal_ordered
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read bool $is_complete
 * @property-read int $quantity_received
 * @property-read int $quantity_remaining
 * @property-read \App\Models\Inventories\Item|null $item
 * @property-read \App\Models\Inventories\PurchaseOrder|null $purchaseOrder
 * @method static \Database\Factories\Inventories\PurchaseOrderItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem withoutTrashed()
 * @mixin \Eloquent
 */
	class PurchaseOrderItem extends \Eloquent {}
}

namespace App\Models\Inventories{
/**
 * @property int $id
 * @property int $merchant_id
 * @property int $item_id
 * @property int $quantity
 * @property int $quantity_before
 * @property int $quantity_after
 * @property StockMovementType $type
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string|null $notes
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property-read User|null $creator
 * @property-read \App\Models\Inventories\Item|null $item
 * @property-read Merchant|null $merchant
 * @property-read Model|\Eloquent|null $reference
 * @method static \Database\Factories\Inventories\StockMovementFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement query()
 * @mixin \Eloquent
 */
	class StockMovement extends \Eloquent {}
}

namespace App\Models\Inventories{
/**
 * Stock opname mencatat sesi penghitungan stok fisik oleh staff merchant.
 *
 * Alur:
 * 1. Staff membuat sesi -> status Draft, pilih item yang akan dihitung.
 * 2. Staff mulai penghitungan -> status Counting, stok sistem di-freeze (snapshot).
 * 3. Staff input stok fisik untuk setiap item -> status Counting.
 * 4. Staff review selisih -> status Reconciling.
 * 5. Staff selesaikan -> status Completed, stok disesuaikan via StockMovementService::adjust().
 *
 * Opsional: is_lock_transactions = true mencegah transaksi keluar selama Counting/Reconciling.
 *
 * @property int $id
 * @property int $merchant_id
 * @property string $opname_number
 * @property StockOpnameStatus $status
 * @property bool $is_lock_transactions
 * @property string|null $notes
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $canceled_at
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read User|null $creator
 * @property-read Collection<int, \App\Models\Inventories\StockOpnameItem> $items
 * @property-read int|null $items_count
 * @property-read bool|null $items_exists
 * @property-read Merchant|null $merchant
 * @method static \Database\Factories\Inventories\StockOpnameFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOpname newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOpname newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOpname onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOpname query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOpname withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOpname withoutTrashed()
 * @mixin \Eloquent
 */
	class StockOpname extends \Eloquent {}
}

namespace App\Models\Inventories{
/**
 * @property int $id
 * @property int $stock_opname_id
 * @property int $item_id
 * @property int $system_quantity
 * @property int|null $actual_quantity
 * @property int|null $difference
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \App\Models\Inventories\Item|null $item
 * @property-read \App\Models\Inventories\StockOpname|null $stockOpname
 * @method static \Database\Factories\Inventories\StockOpnameItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOpnameItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOpnameItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOpnameItem query()
 * @mixin \Eloquent
 */
	class StockOpnameItem extends \Eloquent {}
}

namespace App\Models\Merchants{
/**
 * @property int $id
 * @property string $name
 * @property MerchantType $type Tipe merchant: warehouse (gudang) atau merchant (outlet)
 * @property string $slug
 * @property string|null $avatar_path
 * @property string|null $address
 * @property MerchantStatus $current_status
 * @property numeric|null $latitude
 * @property numeric|null $longitude
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read Collection<int, Distribution> $distributions
 * @property-read int|null $distributions_count
 * @property-read bool|null $distributions_exists
 * @property-read \App\Models\Merchants\MerchantUser|null $pivot
 * @property-read Collection<int, User> $members
 * @property-read int|null $members_count
 * @property-read bool|null $members_exists
 * @property-read Collection<int, MerchantStock> $merchantStocks
 * @property-read int|null $merchant_stocks_count
 * @property-read bool|null $merchant_stocks_exists
 * @property-read Collection<int, Product> $products
 * @property-read int|null $products_count
 * @property-read bool|null $products_exists
 * @property-read Collection<int, Status> $statuses
 * @property-read int|null $statuses_count
 * @property-read bool|null $statuses_exists
 * @property-read Collection<int, Transaction> $transactions
 * @property-read int|null $transactions_count
 * @property-read bool|null $transactions_exists
 * @method static Builder<static>|Merchant currentStatus(...$names)
 * @method static \Database\Factories\Merchants\MerchantFactory factory($count = null, $state = [])
 * @method static Builder<static>|Merchant merchantsOnly()
 * @method static Builder<static>|Merchant newModelQuery()
 * @method static Builder<static>|Merchant newQuery()
 * @method static Builder<static>|Merchant onlyTrashed()
 * @method static Builder<static>|Merchant otherCurrentStatus(...$names)
 * @method static Builder<static>|Merchant query()
 * @method static Builder<static>|Merchant warehouses()
 * @method static Builder<static>|Merchant withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Merchant withoutTrashed()
 * @mixin \Eloquent
 */
	class Merchant extends \Eloquent {}
}

namespace App\Models\Merchants{
/**
 * @property int $id
 * @property int $merchant_id
 * @property int $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MerchantUser query()
 * @mixin \Eloquent
 */
	class MerchantUser extends \Eloquent {}
}

namespace App\Models\Products{
/**
 * @property int $id
 * @property int|null $merchant_id
 * @property int|null $category_id
 * @property string $name
 * @property string $slug
 * @property string|null $image_path
 * @property int $selling_price
 * @property int $cost_price
 * @property string|null $description
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read Category|null $category
 * @property-read Merchant|null $merchant
 * @property-read Collection<int, \App\Models\Products\ProductMaterial> $productMaterials
 * @property-read int|null $product_materials_count
 * @property-read bool|null $product_materials_exists
 * @property-read Collection<int, TransactionItem> $transactionItems
 * @property-read int|null $transaction_items_count
 * @property-read bool|null $transaction_items_exists
 * @method static \Database\Factories\Products\ProductFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product withoutTrashed()
 * @mixin \Eloquent
 */
	class Product extends \Eloquent {}
}

namespace App\Models\Products{
/**
 * @property int $id
 * @property int|null $product_id
 * @property int|null $item_id
 * @property int $quantity_required
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Item|null $item
 * @property-read \App\Models\Products\Product|null $product
 * @method static \Database\Factories\Products\ProductMaterialFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductMaterial newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductMaterial newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductMaterial onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductMaterial query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductMaterial withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductMaterial withoutTrashed()
 * @mixin \Eloquent
 */
	class ProductMaterial extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $id
 * @property int|null $user_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string $payload
 * @property Carbon $last_activity
 * @method static Builder<static>|Session newModelQuery()
 * @method static Builder<static>|Session newQuery()
 * @method static Builder<static>|Session query()
 * @mixin \Eloquent
 */
	class Session extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $reason
 * @property string $model_type
 * @property int $model_id
 * @property string|null $actor_type
 * @property int|null $actor_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model|\Eloquent $model
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status query()
 * @mixin \Eloquent
 */
	class Status extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $merchant_id
 * @property string $name
 * @property string $slug
 * @property string|null $contact_person
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, \App\Models\Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read Merchant|null $merchant
 * @property-read Collection<int, PurchaseOrder> $purchaseOrders
 * @property-read int|null $purchase_orders_count
 * @property-read bool|null $purchase_orders_exists
 * @method static \Database\Factories\SupplierFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier withoutTrashed()
 * @mixin \Eloquent
 */
	class Supplier extends \Eloquent {}
}

namespace App\Models\Transactions{
/**
 * @property int $id
 * @property int|null $merchant_id
 * @property int|null $customer_id
 * @property string $transaction_number
 * @property PaymentMethod $payment_method
 * @property int $total_amount
 * @property string|null $notes
 * @property Carbon $transaction_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read Customer|null $customer
 * @property-read Merchant|null $merchant
 * @property-read Collection<int, \App\Models\Transactions\TransactionItem> $transactionItems
 * @property-read int|null $transaction_items_count
 * @property-read bool|null $transaction_items_exists
 * @method static \Database\Factories\Transactions\TransactionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transaction query()
 * @mixin \Eloquent
 */
	class Transaction extends \Eloquent {}
}

namespace App\Models\Transactions{
/**
 * @property int $id
 * @property int|null $transaction_id
 * @property int|null $product_id
 * @property array<array-key, mixed>|null $product_data
 * @property int $quantity
 * @property int $unit_price
 * @property int $subtotal
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product|null $product
 * @property-read \App\Models\Transactions\Transaction|null $transaction
 * @method static \Database\Factories\Transactions\TransactionItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionItem query()
 * @mixin \Eloquent
 */
	class TransactionItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $username
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $avatar_path
 * @property RoleType $role
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, \App\Models\Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read MerchantUser|null $pivot
 * @property-read Collection<int, Merchant> $merchants
 * @property-read int|null $merchants_count
 * @property-read bool|null $merchants_exists
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read bool|null $notifications_exists
 * @property-read Collection<int, \App\Models\Session> $sessions
 * @property-read int|null $sessions_count
 * @property-read bool|null $sessions_exists
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 * @mixin \Eloquent
 */
	class User extends \Eloquent implements \Filament\Models\Contracts\FilamentUser, \Filament\Models\Contracts\HasAvatar, \Filament\Models\Contracts\HasDefaultTenant, \Filament\Models\Contracts\HasName, \Filament\Models\Contracts\HasTenants {}
}

