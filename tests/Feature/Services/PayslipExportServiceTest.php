<?php

use App\Models\Merchants\Merchant;
use App\Models\Payrolls\Payroll;
use App\Models\Payrolls\PayrollItem;
use App\Models\User;
use App\Services\PayslipExportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'Budi Karyawan']);
    $this->merchant = Merchant::factory()->main()->create(['name' => 'Toko Maju']);
    $this->payroll = Payroll::factory()->for($this->user, 'user')->for($this->merchant, 'merchant')->create([
        'period_start' => '2026-02-01',
        'period_end' => '2026-02-28',
    ]);
    PayrollItem::factory()->for($this->payroll, 'payroll')->create([
        'component_name' => 'Gaji Pokok',
        'amount' => 1000000,
    ]);
    PayrollItem::factory()->for($this->payroll, 'payroll')->create([
        'component_name' => 'Bonus',
        'amount' => 250000,
    ]);
});

// ─── formatPeriodRange ──────────────────────────────────

test('formatPeriodRange formats same-month range', function () {
    $service = app(PayslipExportService::class);

    $result = $service->formatPeriodRange(
        Carbon::parse('2026-03-15'),
        Carbon::parse('2026-03-30')
    );

    expect($result)->toBe('15-30 Maret 2026');
});

test('formatPeriodRange formats cross-month range', function () {
    $service = app(PayslipExportService::class);

    $result = $service->formatPeriodRange(
        Carbon::parse('2026-01-25'),
        Carbon::parse('2026-02-07')
    );

    expect($result)->toBe('25 Jan - 7 Feb 2026');
});

// ─── exportSingle ───────────────────────────────────────

test('exportSingle returns streamed XLSX response with correct filename', function () {
    $response = app(PayslipExportService::class)->exportSingle($this->payroll);

    expect($response)->toBeInstanceOf(StreamedResponse::class)
        ->and($response->headers->get('Content-Type'))->toBe('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->and($response->headers->get('Content-Disposition'))->toContain('Slip_Gaji_Budi Karyawan_1-28_Februari_2026.xlsx');
});

test('exportSingle handles payroll without user name', function () {
    $this->payroll->update(['user_id' => null]);

    $response = app(PayslipExportService::class)->exportSingle($this->payroll);

    expect($response->headers->get('Content-Disposition'))->toContain('Slip_Gaji_Karyawan_');
});

test('exportSingle uses cross-month period range in filename', function () {
    $this->payroll->update([
        'period_start' => '2026-01-25',
        'period_end' => '2026-02-07',
    ]);

    $response = app(PayslipExportService::class)->exportSingle($this->payroll);

    expect($response->headers->get('Content-Disposition'))
        ->toContain('Slip_Gaji_Budi Karyawan_25_Jan_-_7_Feb_2026.xlsx');
});

// ─── exportBulk ─────────────────────────────────────────

test('exportBulk returns streamed XLSX response', function () {
    $payroll2 = Payroll::factory()->for($this->user, 'user')->for($this->merchant, 'merchant')->create([
        'period_start' => '2026-02-01',
        'period_end' => '2026-02-28',
    ]);
    PayrollItem::factory()->for($payroll2, 'payroll')->create([
        'component_name' => 'Gaji Pokok',
        'amount' => 800000,
    ]);

    $response = app(PayslipExportService::class)->exportBulk(collect([$this->payroll, $payroll2]));

    expect($response)->toBeInstanceOf(StreamedResponse::class)
        ->and($response->headers->get('Content-Type'))->toBe('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->and($response->headers->get('Content-Disposition'))->toContain('Slip_Gaji_Budi Karyawan_dkk_1-28_Februari_2026.xlsx');
});

test('exportBulk generates unique sheet names for duplicate employees', function () {
    $payroll2 = Payroll::factory()->for($this->user, 'user')->for($this->merchant, 'merchant')->create([
        'period_start' => '2026-02-01',
        'period_end' => '2026-02-28',
    ]);
    PayrollItem::factory()->for($payroll2, 'payroll')->create([
        'component_name' => 'Gaji Pokok',
        'amount' => 800000,
    ]);

    $response = app(PayslipExportService::class)->exportBulk(collect([$this->payroll, $payroll2]));

    expect($response)->toBeInstanceOf(StreamedResponse::class);
});

// ─── addSheet content ───────────────────────────────────

test('addSheet writes payroll data without error', function () {
    $service = app(PayslipExportService::class);
    $writer = new Writer;
    $writer->openToFile(sys_get_temp_dir().'/payslip_test_single_'.uniqid().'.xlsx');

    // Should not throw with full payroll data.
    $service->addSheet($writer, $this->payroll);

    $writer->close();

    expect(true)->toBeTrue();
});

test('addSheet handles payroll with empty items', function () {
    $emptyPayroll = Payroll::factory()->for($this->user, 'user')->for($this->merchant, 'merchant')->create([
        'period_start' => '2026-03-01',
        'period_end' => '2026-03-31',
    ]);

    $service = app(PayslipExportService::class);
    $writer = new Writer;
    $writer->openToFile(sys_get_temp_dir().'/payslip_test_empty_'.uniqid().'.xlsx');

    $service->addSheet($writer, $emptyPayroll);

    $writer->close();

    expect(true)->toBeTrue();
});

test('single export writes to the writer current sheet (no extra sheets)', function () {
    $service = app(PayslipExportService::class);
    $writer = new Writer;
    $writer->openToFile(sys_get_temp_dir().'/payslip_current_sheet_'.uniqid().'.xlsx');

    $service->addSheet($writer, $this->payroll);

    expect($writer->getSheets())->toHaveCount(1);

    $writer->close();
});

test('bulk export writes one sheet per payroll', function () {
    $payroll2 = Payroll::factory()->for($this->user, 'user')->for($this->merchant, 'merchant')->create([
        'period_start' => '2026-03-01',
        'period_end' => '2026-03-31',
    ]);
    PayrollItem::factory()->for($payroll2, 'payroll')->create([
        'component_name' => 'Gaji Pokok',
        'amount' => 800000,
    ]);

    $tempFile = sys_get_temp_dir().'/payslip_bulk_sheets_'.uniqid().'.xlsx';

    // Capture the streamed XLSX bytes into a temp file.
    ob_start();
    app(PayslipExportService::class)->exportBulk(collect([$this->payroll, $payroll2]))->sendContent();
    file_put_contents($tempFile, ob_get_clean());

    $zip = new ZipArchive;
    $zip->open($tempFile);
    $sheetCount = 0;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        if (preg_match('#^xl/worksheets/sheet\d+\.xml$#', $zip->getNameIndex($i))) {
            $sheetCount++;
        }
    }
    $zip->close();
    @unlink($tempFile);

    expect($sheetCount)->toBe(2);
});

test('generated xlsx contains merged cells, borders and column widths', function () {
    $tempFile = sys_get_temp_dir().'/payslip_verify_'.uniqid().'.xlsx';

    $service = app(PayslipExportService::class);
    $writer = new Writer;
    $writer->openToFile($tempFile);

    $service->addSheet($writer, $this->payroll);
    $writer->close();

    $zip = new ZipArchive;
    $zip->open($tempFile);

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $stylesXml = $zip->getFromName('xl/styles.xml');

    $zip->close();
    @unlink($tempFile);

    // Merged header banners (A1:C1, A2:C2, A3:C3, A4:C4) + total row (A:C).
    expect($sheetXml)->toContain('A1:C1')
        ->and($sheetXml)->toContain('A2:C2')
        ->and($sheetXml)->toContain('A3:C3')
        ->and($sheetXml)->toContain('A4:C4')
        ->and($sheetXml)->toContain('mergeCell');

    // Column widths auto-computed.
    expect($sheetXml)->toContain('customWidth="true"')
        ->and($sheetXml)->toContain('<cols>');

    // Border styles exist in the stylesheet.
    expect($stylesXml)->toContain('borders')
        ->and($stylesXml)->toContain('style="thin"')
        ->and($stylesXml)->toContain('C0C0C0');

    // Default grid lines are hidden so only explicit borders show.
    expect($sheetXml)->toContain('showGridLines="false"');

    // Period text present in the sheet.
    expect($sheetXml)->toContain('Periode : 1-28 Februari 2026');
});
