<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

use App\Filament\Admin\Resources\Items\Pages\ListItems;
use App\Filament\Admin\Resources\Payrolls\Pages\ListPayrolls;
use App\Filament\Exports\ItemExporter;
use App\Filament\Exports\PayrollExporter;
use App\Models\Inventories\Item;
use App\Models\Payrolls\Payroll;
use App\Models\User;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\Models\Export;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    actingAs(User::factory()->superAdmin()->create());
    Storage::fake('local');
});

describe('Items export', function () {
    it('has export header action and bulk action', function () {
        Item::factory()->count(3)->create();

        livewire(ListItems::class)
            ->assertTableActionExists('export')
            ->assertTableBulkActionExists('export');
    });

    it('can run export and create xlsx file', function () {
        Item::factory()->count(5)->create();

        livewire(ListItems::class)
            ->callTableAction('export');

        $export = Export::query()->latest('id')->first();

        expect($export)->not->toBeNull()
            ->and($export->exporter)->toBe(ItemExporter::class)
            ->and($export->completed_at)->not->toBeNull();

        Storage::disk('local')->assertExists("{$export->getFileDirectory()}/{$export->file_name}.xlsx");
    });
});

describe('Payroll export', function () {
    it('has export header action and bulk action', function () {
        Payroll::factory()->count(3)->create();

        livewire(ListPayrolls::class)
            ->assertTableActionExists('export')
            ->assertTableBulkActionExists('export');
    });

    it('can run export and create xlsx file', function () {
        Payroll::factory()->count(5)->create();

        livewire(ListPayrolls::class)
            ->callTableAction('export');

        $export = Export::query()->latest('id')->first();

        expect($export)->not->toBeNull()
            ->and($export->exporter)->toBe(PayrollExporter::class)
            ->and($export->completed_at)->not->toBeNull();

        Storage::disk('local')->assertExists("{$export->getFileDirectory()}/{$export->file_name}.xlsx");
    });
});

describe('BaseExporter', function () {
    it('exporter supports xlsx only', function () {
        $exporter = new ReflectionClass(ItemExporter::class)->newInstanceWithoutConstructor();

        expect($exporter->getFormats())
            ->toBe([ExportFormat::Xlsx]);
    });

    it('exporter has xlsx header style, cell style, and options', function () {
        $exporter = new ReflectionClass(ItemExporter::class)->newInstanceWithoutConstructor();

        expect($exporter->getXlsxHeaderCellStyle())->not->toBeNull()
            ->and($exporter->getXlsxCellStyle())->not->toBeNull()
            ->and($exporter->getXlsxWriterOptions())->not->toBeNull();
    });
});
