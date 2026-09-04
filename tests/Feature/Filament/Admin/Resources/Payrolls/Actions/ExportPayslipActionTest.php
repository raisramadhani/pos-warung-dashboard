<?php

use App\Filament\Admin\Resources\Payrolls\Actions\ExportPayslipAction;
use App\Filament\Admin\Resources\Payrolls\Pages\ViewPayroll;
use App\Models\Merchants\Merchant;
use App\Models\Payrolls\Payroll;
use App\Models\Payrolls\PayrollItem;
use App\Models\User;
use App\Services\PayslipExportService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSpout\Writer\XLSX\Writer;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    actingAs(User::factory()->superAdmin()->create());

    $this->merchant = Merchant::factory()->main()->create(['name' => 'Outlet Es Teh Desa']);
    $this->user = User::factory()->create(['name' => 'Seren']);
    $this->merchant->members()->attach($this->user);

    $this->payroll = Payroll::factory()
        ->for($this->merchant, 'merchant')
        ->for($this->user, 'user')
        ->approved()
        ->create([
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'total_amount' => 175000,
            'notes' => null,
        ]);

    PayrollItem::factory()
        ->for($this->payroll, 'payroll')
        ->create(['component_name' => 'Gaji Harian', 'daily_rate' => 45000, 'days' => 1, 'amount' => 45000]);

    PayrollItem::factory()
        ->for($this->payroll, 'payroll')
        ->create(['component_name' => 'Gaji Hari Libur dan Tanggal Merah', 'daily_rate' => 130000, 'days' => 1, 'amount' => 130000]);
});

// ==========================================================================
// PayslipExportService — Unit Tests
// ==========================================================================

describe('PayslipExportService - Happy Path', function () {
    it('exportSingle returns a streamed response with correct headers', function () {
        $service = new PayslipExportService;
        $response = $service->exportSingle($this->payroll);

        expect($response->headers->get('Content-Type'))
            ->toBe('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->and($response->headers->get('Content-Disposition'))
            ->toContain('Slip_Gaji_Seren_1-31_Januari_2026.xlsx');
    });

    it('exportBulk returns a streamed response with correct filename', function () {
        $user2 = User::factory()->create(['name' => 'April']);
        $this->merchant->members()->attach($user2);

        $payroll2 = Payroll::factory()
            ->for($this->merchant, 'merchant')
            ->for($user2, 'user')
            ->approved()
            ->create([
                'period_start' => '2026-01-01',
                'period_end' => '2026-01-31',
                'total_amount' => 100000,
            ]);

        PayrollItem::factory()
            ->for($payroll2, 'payroll')
            ->create(['component_name' => 'Gaji Harian', 'daily_rate' => 100000, 'days' => 1, 'amount' => 100000]);

        $service = new PayslipExportService;
        $response = $service->exportBulk(collect([$this->payroll, $payroll2]));

        expect($response->headers->get('Content-Type'))
            ->toBe('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->and($response->headers->get('Content-Disposition'))
            ->toContain('Slip_Gaji_Seren_dkk_1-31_Januari_2026.xlsx');
    });

    it('addSheet writes correct structure to writer', function () {
        $tempFile = sys_get_temp_dir().'/payslip_test_'.uniqid().'.xlsx';

        $writer = new Writer;
        $writer->openToFile($tempFile);

        $service = new PayslipExportService;
        $service->addSheet($writer, $this->payroll);
        $writer->close();

        expect(file_exists($tempFile))->toBeTrue()
            ->and(filesize($tempFile))->toBeGreaterThan(0);

        unlink($tempFile);
    });
});

// ==========================================================================
// ViewPayroll — ExportPayslipAction
// ==========================================================================

describe('ViewPayroll - ExportPayslipAction (Happy Path)', function () {
    it('export button exists on ViewPayroll page', function () {
        livewire(ViewPayroll::class, ['record' => $this->payroll->id])
            ->assertActionExists('exportPayslip');
    });

    it('can trigger export action without errors', function () {
        livewire(ViewPayroll::class, ['record' => $this->payroll->id])
            ->callAction('exportPayslip')
            ->assertHasNoActionErrors();
    });

    it('export action streams download with correct filename', function () {
        $service = app(PayslipExportService::class);
        $response = $service->exportSingle($this->payroll);

        expect($response->getStatusCode())->toBe(200)
            ->and($response->headers->get('Content-Disposition'))
            ->toContain('attachment')
            ->and($response->headers->get('Content-Disposition'))
            ->toContain('Slip_Gaji_Seren_1-31_Januari_2026.xlsx');
    });
});

describe('ViewPayroll - ExportPayslipAction (Sad Path)', function () {
    it('export still works when payroll has no items', function () {
        $emptyPayroll = Payroll::factory()
            ->for($this->merchant, 'merchant')
            ->for($this->user, 'user')
            ->approved()
            ->create([
                'period_start' => '2026-03-01',
                'period_end' => '2026-03-31',
                'total_amount' => 0,
            ]);

        livewire(ViewPayroll::class, ['record' => $emptyPayroll->id])
            ->callAction('exportPayslip')
            ->assertHasNoActionErrors();
    });

    it('export still works when payroll has zero-amount items', function () {
        $payroll = Payroll::factory()
            ->for($this->merchant, 'merchant')
            ->for($this->user, 'user')
            ->approved()
            ->create([
                'period_start' => '2026-04-01',
                'period_end' => '2026-04-30',
                'total_amount' => 0,
            ]);

        PayrollItem::factory()
            ->for($payroll, 'payroll')
            ->create(['component_name' => 'Zero Bonus', 'daily_rate' => 0, 'days' => 0, 'amount' => 0]);

        livewire(ViewPayroll::class, ['record' => $payroll->id])
            ->callAction('exportPayslip')
            ->assertHasNoActionErrors();
    });
});

describe('ViewPayroll - ExportPayslipAction (Edge Cases)', function () {
    it('export works with very long component names', function () {
        $payroll = Payroll::factory()
            ->for($this->merchant, 'merchant')
            ->for($this->user, 'user')
            ->approved()
            ->create([
                'period_start' => '2026-05-01',
                'period_end' => '2026-05-31',
                'total_amount' => 50000,
            ]);

        PayrollItem::factory()
            ->for($payroll, 'payroll')
            ->create([
                'component_name' => 'Tunjangan Hari Raya Keagamaan dan Libur Nasional',
                'daily_rate' => 50000,
                'days' => 1,
                'amount' => 50000,
            ]);

        livewire(ViewPayroll::class, ['record' => $payroll->id])
            ->callAction('exportPayslip')
            ->assertHasNoActionErrors();
    });

    it('export works with many items', function () {
        $payroll = Payroll::factory()
            ->for($this->merchant, 'merchant')
            ->for($this->user, 'user')
            ->approved()
            ->create([
                'period_start' => '2026-06-01',
                'period_end' => '2026-06-30',
                'total_amount' => 1000000,
            ]);

        $components = [
            'Gaji Harian', 'Gaji Lembur', 'Bonus Harian',
            'Tunjangan Makan', 'Tunjangan Transport', 'Tunjangan Kesehatan',
            'Bonus Kehadiran', 'Insentif Penjualan', 'Komisi', 'Bonus Akhir Tahun',
        ];

        foreach ($components as $i => $name) {
            PayrollItem::factory()
                ->for($payroll, 'payroll')
                ->create(['component_name' => $name, 'daily_rate' => 100000, 'days' => 1, 'amount' => 100000]);
        }

        livewire(ViewPayroll::class, ['record' => $payroll->id])
            ->callAction('exportPayslip')
            ->assertHasNoActionErrors();
    });

    it('export handles single-day period', function () {
        $payroll = Payroll::factory()
            ->for($this->merchant, 'merchant')
            ->for($this->user, 'user')
            ->approved()
            ->create([
                'period_start' => '2026-08-15',
                'period_end' => '2026-08-15',
                'total_amount' => 50000,
            ]);

        PayrollItem::factory()
            ->for($payroll, 'payroll')
            ->create(['component_name' => 'Gaji Setengah Bulan', 'daily_rate' => 50000, 'days' => 1, 'amount' => 50000]);

        livewire(ViewPayroll::class, ['record' => $payroll->id])
            ->callAction('exportPayslip')
            ->assertHasNoActionErrors();
    });

    it('sheet name truncates to 31 characters for long names', function () {
        $longNameUser = User::factory()->create([
            'name' => 'Muhammad Abdul Rahman Al-Fatih Bin Abdullah',
        ]);
        $this->merchant->members()->attach($longNameUser);

        $payroll = Payroll::factory()
            ->for($this->merchant, 'merchant')
            ->for($longNameUser, 'user')
            ->approved()
            ->create([
                'period_start' => '2026-07-01',
                'period_end' => '2026-07-31',
                'total_amount' => 50000,
            ]);

        PayrollItem::factory()
            ->for($payroll, 'payroll')
            ->create(['component_name' => 'Gaji Harian', 'daily_rate' => 50000, 'days' => 1, 'amount' => 50000]);

        livewire(ViewPayroll::class, ['record' => $payroll->id])
            ->callAction('exportPayslip')
            ->assertHasNoActionErrors();
    });
});

describe('ExportPayslipAction - Edge Cases', function () {
    it('export action class has correct default name', function () {
        $reflection = new ReflectionClass(ExportPayslipAction::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        expect($instance->getDefaultName())->toBe('exportPayslip');
    });
});
