<?php

/**
 * SPDX-FileCopyrightText: 2026 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Modules\Reports\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MatrixReportExport
{
    public static function label(array $column, string $period): string
    {
        $from = MatrixReportBuilder::date($column['start']);
        $to = MatrixReportBuilder::date($column['end']);
        if ($period === 'year') {
            return $from->format('M Y');
        }
        return $from == $to ? $from->format('d M') : $from->format('d M') . ' – ' . $to->format('d M');
    }

    public static function html(array $matrix, array $rows, string $period, bool $pdf = false): string
    {
        $esc = static fn ($s) => htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $format = static fn ($minutes) => $minutes === 0 ? '—' : rtrim(rtrim(number_format($minutes / 60, 2, '.', ''), '0'), '.');
        $title = 'Table · ' . ucfirst($matrix['mode']);
        $columns = $matrix['columns'];
        $groups = $pdf ? array_chunk(array_keys($columns), 10) : [array_keys($columns)];
        $html = '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>' . $esc($title) . '</title>'
            . '<style>@page{margin:24pt}body{font-family:"DejaVu Sans",sans-serif;color:#172b42;font-size:10px}'
            . 'h1{font-size:20px;margin:0 0 8px}p{color:#53657a}table{border-collapse:collapse;width:100%;table-layout:auto}'
            . 'th,td{border:1px solid #dbe3ec;padding:7px 5px;text-align:right;overflow-wrap:break-word}'
            . 'th{background:#edf3f9}thead{display:table-header-group}tr{page-break-inside:avoid}'
            . '.name{text-align:left;width:180px}.child .name{padding-left:20px;color:#52657a}'
            . '.company{background:#e3eef9;font-weight:bold}.parent{font-weight:bold}.total{background:#f3f6fa}'
            . '.section{page-break-before:always}.section:first-child{page-break-before:auto}'
            . ($pdf ? '' : '@media screen{body{padding:24px}.wrap{overflow:auto}table{min-width:'
                . (220 + count($columns) * 90) . 'px}}')
            . '</style></head><body>';
        foreach ($groups as $group) {
            $html .= '<section class="section"><h1>' . $esc($title) . '</h1><p>'
                . $esc($matrix['start'] . ' – ' . $matrix['end']) . ' · Hours · '
                . $esc(count($rows) - 1) . ' visible rows</p>';
            if (count($groups) > 1) {
                $html .= '<p>Columns: ' . $esc(self::label($columns[$group[0]], $period)) . ' – '
                    . $esc(self::label($columns[end($group)], $period)) . '. Total covers the full selected period.</p>';
            }
            $html .= '<div class="wrap"><table><thead><tr><th class="name">' . $esc(ucfirst($matrix['mode'])) . '</th>';
            foreach ($group as $index) {
                $html .= '<th>' . $esc(self::label($columns[$index], $period)) . '</th>';
            }
            $html .= '<th>Total</th></tr></thead><tbody>';
            foreach ($rows as $row) {
                $class = $row['id'] === 'company' ? 'company' : ($row['depth'] ? 'child' : 'parent');
                $html .= '<tr class="' . $class . '"><th scope="row" class="name">' . $esc($row['name']) . '</th>';
                foreach ($group as $index) {
                    $html .= '<td>' . $format($row['cells'][$index]) . '</td>';
                }
                $html .= '<td class="total">' . $format($row['total']) . '</td></tr>';
            }
            $html .= '</tbody></table></div><p>Expanded rows are breakdowns of their parent; do not add them twice. '
                . 'Values are hours, rounded to two decimals. Totals are calculated from exact minutes.</p></section>';
        }
        return $html . '</body></html>';
    }

    public static function create(array $matrix, array $expanded, string $period, string $format): array
    {
        $rows = MatrixReportBuilder::visibleRows($matrix, $expanded);
        if (count($rows) > 5000 || ($format === 'pdf' && count($rows) * count($matrix['columns']) > 20000)) {
            throw new \InvalidArgumentException('Too many visible rows to export. Collapse some rows or select a shorter period.');
        }
        $filename = 'done-time-allocation-' . $matrix['mode'] . '-' . $matrix['start'];
        if ($format === 'html') {
            $content = self::html($matrix, $rows, $period);
            $mime = 'text/html;charset=utf-8';
        } elseif ($format === 'pdf') {
            $options = new Options();
            $options->set('isRemoteEnabled', false);
            $options->set('isPhpEnabled', false);
            $options->set('isJavascriptEnabled', false);
            $options->set('defaultFont', 'DejaVu Sans');
            $pdf = new Dompdf($options);
            $pdf->setPaper('A4', 'landscape');
            $pdf->loadHtml(self::html($matrix, $rows, $period, true), 'UTF-8');
            $pdf->render();
            $canvas = $pdf->getCanvas();
            $canvas->page_text(730, 578, 'Page {PAGE_NUM} / {PAGE_COUNT}',
                $pdf->getFontMetrics()->getFont('Helvetica'), 8, [0.3, 0.4, 0.5]);
            $content = $pdf->output();
            $mime = 'application/pdf';
        } elseif ($format === 'xlsx') {
            $book = new Spreadsheet();
            $sheet = $book->getActiveSheet();
            $sheet->setTitle('Table');
            $sheet->setCellValue('A1', 'Table · ' . ucfirst($matrix['mode']));
            $sheet->setCellValue('A2', $matrix['start'] . ' – ' . $matrix['end'] . ' · Hours');
            $headers = [ucfirst($matrix['mode'])];
            foreach ($matrix['columns'] as $column) {
                $headers[] = self::label($column, $period);
            }
            $headers[] = 'Total';
            $sheet->fromArray($headers, null, 'A4');
            $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
            $sheet->mergeCells('A1:' . $lastColumn . '1');
            $sheet->mergeCells('A2:' . $lastColumn . '2');
            foreach ($rows as $index => $row) {
                $line = $index + 5;
                // Explicit text prevents spreadsheet formula injection from entity names.
                $sheet->setCellValueExplicit('A' . $line, $row['name'], DataType::TYPE_STRING);
                $sheet->getStyle('A' . $line)->getAlignment()->setIndent($row['depth']);
                foreach (array_merge($row['cells'], [$row['total']]) as $col => $minutes) {
                    $sheet->setCellValue([$col + 2, $line], $minutes / 60);
                }
                if ($row['depth']) {
                    $sheet->getRowDimension($line)->setOutlineLevel(1);
                } else {
                    $sheet->getStyle('A' . $line . ':' . $lastColumn . $line)->getFont()->setBold(true);
                }
            }
            $lastRow = count($rows) + 4;
            $sheet->getStyle('B5:' . $lastColumn . $lastRow)->getNumberFormat()->setFormatCode('0.##');
            $sheet->getStyle('A4:' . $lastColumn . '5')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFE3EEF9');
            $sheet->getColumnDimension('A')->setWidth(38);
            for ($i = 2; $i <= count($headers); $i++) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(17);
            }
            $sheet->freezePane('B6');
            $sheet->getPageSetup()->setOrientation('landscape')->setFitToWidth(1)->setFitToHeight(0);
            $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(4, 4);
            $file = tmpfile();
            try {
                (new Xlsx($book))->save(stream_get_meta_data($file)['uri']);
                rewind($file);
                $content = stream_get_contents($file);
            } finally {
                fclose($file);
                $book->disconnectWorksheets();
            }
            $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        } else {
            throw new \InvalidArgumentException('Invalid export format');
        }
        return ['filename' => $filename . '.' . $format, 'mime' => $mime, 'content' => base64_encode($content)];
    }
}
