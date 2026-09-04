<?php

namespace App\Services;

use App\Models\Payrolls\Payroll;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayslipExportService
{
    /** Column index (zero-based) for the "NO" column. */
    private const COL_NO = 0;

    /** Column index (zero-based) for the "Keterangan" column. */
    private const COL_DESCRIPTION = 1;

    /** Column index (zero-based) for the "Jumlah" column. */
    private const COL_AMOUNT = 2;

    /** Maximum column width (in Excel character units) to avoid absurdly wide sheets. */
    private const MAX_COLUMN_WIDTH = 60;

    /** Padding added to the computed content width per column. */
    private const WIDTH_PADDING = 4;

    /**
     * Write a single employee's payslip as a sheet in the given writer.
     * The slip is written to the writer's current sheet.
     */
    public function addSheet(Writer $writer, Payroll $payroll): void
    {
        $this->writeSheet($writer, $payroll);
    }

    /**
     * Export a single payroll as XLSX and return a streamed response.
     */
    public function exportSingle(Payroll $payroll): StreamedResponse
    {
        $payroll->loadMissing(['items', 'user', 'merchant']);

        $userName = $payroll->user->name ?? 'Karyawan';
        $period = $this->formatPeriodRange($payroll->period_start, $payroll->period_end);
        $filename = "Slip_Gaji_{$userName}_{$this->sanitizeForFilename($period)}.xlsx";

        return $this->buildStreamedResponse(function (string $tempPath) use ($payroll) {
            $this->writeSingle($payroll, $tempPath);
        }, $filename);
    }

    /**
     * Export multiple payrolls as a multi-sheet XLSX and return a streamed response.
     */
    public function exportBulk(Collection $payrolls): StreamedResponse
    {
        $payrolls->each->loadMissing(['user', 'items', 'merchant']);

        $firstUserName = $payrolls->first()->user->name ?? 'Karyawan';
        $period = $this->formatPeriodRange($payrolls->first()->period_start, $payrolls->first()->period_end);
        $filename = "Slip_Gaji_{$firstUserName}_dkk_{$this->sanitizeForFilename($period)}.xlsx";

        return $this->buildStreamedResponse(function (string $tempPath) use ($payrolls) {
            $this->writeBulk($payrolls, $tempPath);
        }, $filename);
    }

    /**
     * Write a single payroll to a temp XLSX file and stream its contents.
     */
    private function writeSingle(Payroll $payroll, string $tempPath): void
    {
        $writer = new Writer;
        $writer->openToFile($tempPath);

        // Single export writes to the writer's current (first) sheet.
        $this->writeSheet($writer, $payroll);

        $writer->close();
    }

    /**
     * Write multiple payrolls to a temp XLSX file and stream its contents.
     */
    private function writeBulk(Collection $payrolls, string $tempPath): void
    {
        $writer = new Writer;
        $writer->openToFile($tempPath);

        $usedNames = [];
        foreach ($payrolls as $index => $payroll) {
            // The first payroll uses the writer's current sheet; subsequent
            // payrolls get a new sheet.
            if ($index > 0) {
                $writer->addNewSheetAndMakeItCurrent();
            }

            $this->writeSheet($writer, $payroll);

            // Ensure unique sheet names in case of same employee
            $currentSheet = $writer->getCurrentSheet();
            $idx = 0;
            $baseName = mb_strimwidth($payroll->user->name ?? 'Karyawan', 0, 31);
            $name = $baseName;
            while (\in_array($name, $usedNames)) {
                $idx++;
                $suffix = " ({$idx})";
                $name = mb_strimwidth($baseName, 0, 31 - \strlen($suffix)).$suffix;
            }
            $usedNames[] = $name;
            $currentSheet->setName($name);
        }

        $writer->close();
    }

    /**
     * Write one payslip to the writer's current sheet.
     *
     * The layout mirrors the HTML mockup (3 columns):
     *   row 1: OUTLET name (merged A:C)
     *   row 2: SLIP GAJI KARYAWAN (merged A:C)
     *   row 3: Nama : ... (merged A:C)
     *   row 4: Periode : ... (merged A:C)
     *   row 5: table headers (NO, Keterangan, Jumlah)
     *   rows 6..n: items
     *   spacer
     *   total row (label merged A:B + amount in C)
     */
    private function writeSheet(Writer $writer, Payroll $payroll): void
    {
        $payroll->loadMissing(['items', 'user', 'merchant']);

        $sheetIndex = $writer->getCurrentSheet()->getIndex();
        $options = $writer->getOptions();

        // Hide the default grid lines so only the explicit cell borders show.
        $writer->getCurrentSheet()->setSheetView(
            (new SheetView)->setShowGridLines(false)
        );

        $merchantName = mb_strtoupper($payroll->merchant->name ?? 'OUTLET');
        $userName = $payroll->user->name ?? '-';
        $period = 'Periode : '.$this->formatPeriodRange($payroll->period_start, $payroll->period_end);

        // Styles
        $titleStyle = $this->borderedStyle((new Style)->setFontBold()->setFontSize(14)->setCellAlignment(CellAlignment::CENTER));
        $subheaderStyle = $this->borderedStyle((new Style)->setFontBold()->setFontSize(11)->setCellAlignment(CellAlignment::CENTER));
        $infoStyle = $this->borderedStyle((new Style)->setFontBold()->setFontSize(10)->setCellAlignment(CellAlignment::LEFT));
        $tableHeaderStyle = $this->borderedStyle((new Style)->setFontBold()->setFontSize(10)->setCellAlignment(CellAlignment::CENTER));
        $normalStyle = $this->borderedStyle(new Style);
        $leftStyle = $this->borderedStyle((new Style)->setCellAlignment(CellAlignment::LEFT));
        $centerStyle = $this->borderedStyle((new Style)->setCellAlignment(CellAlignment::CENTER));
        $rightStyle = $this->borderedStyle((new Style)->setCellAlignment(CellAlignment::RIGHT));
        $totalLabelStyle = $this->borderedStyle((new Style)->setFontBold()->setCellAlignment(CellAlignment::CENTER)->setBackgroundColor(Color::rgb(192, 192, 192)));
        $totalStyle = $this->borderedStyle((new Style)->setFontBold()->setCellAlignment(CellAlignment::RIGHT)->setBackgroundColor(Color::rgb(192, 192, 192)));

        // Width bookkeeping: track the longest content per column.
        $columnWidths = [self::COL_NO => 0, self::COL_DESCRIPTION => 0, self::COL_AMOUNT => 0];

        // Header banners merged across all used columns.
        $this->trackWidth($columnWidths, self::COL_DESCRIPTION, $merchantName);
        $this->addMergedRow($writer, $options, $sheetIndex, 1, self::COL_NO, self::COL_AMOUNT, $merchantName, $titleStyle);

        $this->trackWidth($columnWidths, self::COL_DESCRIPTION, 'SLIP GAJI KARYAWAN');
        $this->addMergedRow($writer, $options, $sheetIndex, 2, self::COL_NO, self::COL_AMOUNT, 'SLIP GAJI KARYAWAN', $subheaderStyle);

        $this->trackWidth($columnWidths, self::COL_DESCRIPTION, "Nama : {$userName}");
        $this->addMergedRow($writer, $options, $sheetIndex, 3, self::COL_NO, self::COL_AMOUNT, "Nama : {$userName}", $infoStyle);

        $this->trackWidth($columnWidths, self::COL_DESCRIPTION, $period);
        $this->addMergedRow($writer, $options, $sheetIndex, 4, self::COL_NO, self::COL_AMOUNT, $period, $infoStyle);

        // Table header row.
        $writer->addRow(new Row([
            Cell::fromValue('NO', $tableHeaderStyle),
            Cell::fromValue('Keterangan', $tableHeaderStyle),
            Cell::fromValue('Jumlah', $tableHeaderStyle),
        ]));

        // Item rows.
        $no = 1;
        $rowIndex = 6;
        foreach ($payroll->items as $item) {
            $noText = (string) $no;
            $componentText = $item->component_name;
            $amountText = number_format($item->amount, 0, ',', '.');

            $this->trackWidth($columnWidths, self::COL_NO, $noText);
            $this->trackWidth($columnWidths, self::COL_DESCRIPTION, $componentText);
            $this->trackWidth($columnWidths, self::COL_AMOUNT, $amountText);

            $writer->addRow(new Row([
                Cell::fromValue($noText, $centerStyle),
                Cell::fromValue($componentText, $leftStyle),
                Cell::fromValue($amountText, $rightStyle),
            ]));

            $no++;
            $rowIndex++;
        }

        // Spacer row (bordered, empty).
        $writer->addRow(new Row([
            Cell::fromValue('', $normalStyle),
            Cell::fromValue('', $normalStyle),
            Cell::fromValue('', $normalStyle),
        ]));

        // Total row: label merged A:B, amount right-aligned, gray background.
        $totalText = number_format($payroll->total_amount, 0, ',', '.');
        $totalRow = $rowIndex + 1;

        $this->trackWidth($columnWidths, self::COL_DESCRIPTION, 'Total Yang Diterima');
        $this->trackWidth($columnWidths, self::COL_AMOUNT, $totalText);

        $options->mergeCells(self::COL_NO, $totalRow, self::COL_DESCRIPTION, $totalRow, $sheetIndex);
        $writer->addRow(new Row([
            Cell::fromValue('Total Yang Diterima', $totalLabelStyle),
            Cell::fromValue('', $totalLabelStyle),
            Cell::fromValue($totalText, $totalStyle),
        ]));

        // Column widths auto-computed from the longest content per column.
        $sheet = $writer->getCurrentSheet();
        foreach ($columnWidths as $columnIndex => $width) {
            $sheet->setColumnWidthForRange(
                min($width + self::WIDTH_PADDING, self::MAX_COLUMN_WIDTH),
                $columnIndex + 1,
                $columnIndex + 1
            );
        }
    }

    /**
     * Register a merge for the current sheet and write a single-value row
     * that occupies the given column range (value placed in the leftmost cell).
     */
    private function addMergedRow(
        Writer $writer,
        Options $options,
        int $sheetIndex,
        int $rowIndex,
        int $startColumn,
        int $endColumn,
        string $value,
        Style $style,
    ): void {
        $options->mergeCells($startColumn, $rowIndex, $endColumn, $rowIndex, $sheetIndex);

        $cells = [];
        for ($col = self::COL_NO; $col <= self::COL_AMOUNT; $col++) {
            $cells[] = Cell::fromValue($col === $startColumn ? $value : '', $style);
        }
        $writer->addRow(new Row($cells));
    }

    /**
     * Update the widest-content tracker for a column.
     */
    private function trackWidth(array &$widths, int $column, string $text): void
    {
        $width = mb_strlen($text);
        if ($width > $widths[$column]) {
            $widths[$column] = $width;
        }
    }

    /**
     * Apply a full thin border to the given style.
     */
    private function borderedStyle(Style $style): Style
    {
        $border = new Border(
            new BorderPart(Border::TOP, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID),
            new BorderPart(Border::RIGHT, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID),
            new BorderPart(Border::BOTTOM, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID),
            new BorderPart(Border::LEFT, Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID),
        );

        return $style->setBorder($border);
    }

    /**
     * Format the payroll period as a localized date range.
     *
     * Same month (e.g. 15-30 Maret 2026)    -> "15-30 Maret 2026"
     * Cross month (e.g. 25 Jan - 7 Feb 2026) -> "25 Jan - 7 Feb 2026"
     */
    public function formatPeriodRange(CarbonInterface $start, CarbonInterface $end): string
    {
        if ($start->month === $end->month && $start->year === $end->year) {
            return \sprintf(
                '%s-%s %s',
                $start->translatedFormat('j'),
                $end->translatedFormat('j'),
                $end->translatedFormat('F Y')
            );
        }

        return \sprintf(
            '%s %s - %s %s',
            $start->translatedFormat('j'),
            $start->translatedFormat('M'),
            $end->translatedFormat('j'),
            $end->translatedFormat('M Y')
        );
    }

    /**
     * Sanitize a period string so it can be used inside a filename.
     */
    private function sanitizeForFilename(string $period): string
    {
        return str_replace(' ', '_', $period);
    }

    /**
     * Build a streamed download response that writes the XLSX to a temp file
     * first (instead of php://output), avoiding raw binary leaking into the
     * console/test output.
     */
    private function buildStreamedResponse(callable $write, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($write) {
            $tempPath = $this->tempPath();

            try {
                $write($tempPath);

                $file = fopen($tempPath, 'rb');

                if ($file !== false) {
                    fpassthru($file);
                    fclose($file);
                }
            } finally {
                @unlink($tempPath);
            }
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Return a unique temporary file path for the export.
     */
    private function tempPath(): string
    {
        return tempnam(sys_get_temp_dir(), 'payslip_');
    }
}
