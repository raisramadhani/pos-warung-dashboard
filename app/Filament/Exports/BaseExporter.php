<?php

namespace App\Filament\Exports;

use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\Exporter;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

abstract class BaseExporter extends Exporter
{
    public function getFormats(): array
    {
        return [ExportFormat::Xlsx];
    }

    public function getXlsxHeaderCellStyle(): Style
    {
        $border = new Border(
            new BorderPart(Border::LEFT, Color::WHITE, Border::WIDTH_THIN, Border::STYLE_SOLID),
            new BorderPart(Border::RIGHT, Color::WHITE, Border::WIDTH_THIN, Border::STYLE_SOLID),
            new BorderPart(Border::TOP, Color::WHITE, Border::WIDTH_THIN, Border::STYLE_SOLID),
            new BorderPart(Border::BOTTOM, Color::WHITE, Border::WIDTH_THIN, Border::STYLE_SOLID),
        );

        return (new Style)
            ->setFontBold()
            ->setFontColor(Color::WHITE)
            ->setFontSize(11)
            ->setBackgroundColor(Color::rgb(37, 99, 235))
            ->setBorder($border)
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellAlignment::CENTER)
            ->setShouldWrapText(true);
    }

    public function getXlsxCellStyle(): Style
    {
        $border = new Border(
            new BorderPart(Border::LEFT, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID),
            new BorderPart(Border::RIGHT, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID),
            new BorderPart(Border::TOP, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID),
            new BorderPart(Border::BOTTOM, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID),
        );

        return (new Style)
            ->setBorder($border)
            ->setCellVerticalAlignment(CellAlignment::CENTER)
            ->setShouldWrapText(true);
    }

    public function getXlsxWriterOptions(): ?Options
    {
        $options = new Options;

        $options->setColumnWidthForRange(20, 1, \count(static::getVisibleColumns()));

        return $options;
    }

    public function configureXlsxWriterBeforeClose(Writer $writer): Writer
    {
        $sheet = $writer->getCurrentSheet();

        $sheet->setName((string) str(class_basename(static::class))
            ->beforeLast('Exporter')
            ->kebab()
            ->replace('-', ' ')
            ->ucwords());
        $sheetView = $sheet->getSheetView() ?? new SheetView;
        $sheetView->setFreezeRow(2);
        $sheet->setSheetView($sheetView);

        return $writer;
    }
}
