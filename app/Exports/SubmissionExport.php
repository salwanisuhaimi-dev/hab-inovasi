<?php

namespace App\Exports;

use App\Models\Program;
use App\Models\Submission;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use DOMDocument;
use ZipArchive;

class SubmissionExport
{
    /**
     * Download formatted Excel spreadsheet (.xlsx) with Title Header.
     */
     public static function downloadIdea(Program $program)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // -------------------------------------------------------------
        // 1. TITLE BLOCK (Row 1)
        // -------------------------------------------------------------
        $titleText = 'Penyertaan ' . \Illuminate\Support\Str::title($program->title);

        $sheet->setCellValue('A1', $titleText);
        $sheet->mergeCells('A1:D1'); // Merge across the 4 columns
        $sheet->getRowDimension(1)->setRowHeight(40);

        // Title Styling
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold'  => true,
                'size'  => 14,
                'color' => ['rgb' => '1E293B'], // Dark Gray
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'], // Light Gray Accent
            ]
        ]);

        // Row 2 is an empty spacer row
        $sheet->getRowDimension(2)->setRowHeight(15);

        // -------------------------------------------------------------
        // 2. TABLE HEADERS (Row 3)
        // -------------------------------------------------------------
        $headers = [
            'Nama',
            'Bahagian',
            'Tajuk Projek',
            'Penerangan Projek',
        ];

        foreach ($headers as $colIndex => $headerTitle) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($columnLetter . '3', $headerTitle);
        }

        // Header Row Height & Styling
        $sheet->getRowDimension(3)->setRowHeight(35);
        $sheet->getStyle('A3:D3')->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 11,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F2937'], // Dark Gray/Blue
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
        ]);

        // -------------------------------------------------------------
        // 3. POPULATE DATA (Row 4 onwards)
        // -------------------------------------------------------------
        $submissions = Submission::where('program_id', $program->id)
            ->with(['user.department', 'projectDetail.department'])
            ->latest()
            ->get();

        $row = 4;
        foreach ($submissions as $sub) {
            $p = $sub->projectDetail;

            $sheet->setCellValue('A' . $row, $sub->user->name ?? 'N/A');
            $sheet->setCellValue('B' . $row, $p->department->name ?? $sub->user->department->name ?? 'N/A');
            $sheet->setCellValue('C' . $row, $p->project_title ?? 'N/A');
            $sheet->setCellValue('D' . $row, $p->project_description ?? 'N/A');

            $row++;
        }

        // Align data rows
        if ($row > 4) {
            $sheet->getStyle('A4:D' . ($row - 1))->applyFromArray([
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ]
            ]);
        }

        // -------------------------------------------------------------
        // 4. COLUMN D DIMENSIONS & TEXT WRAPPING
        // -------------------------------------------------------------
        $sheet->getColumnDimension('D')->setAutoSize(false);
        $sheet->getColumnDimension('D')->setWidth(55); // Custom width for Penerangan Projek

        if ($row > 4) {
            $lastDataRow = $row - 1;
            $sheet->getStyle("D4:D{$lastDataRow}")->getAlignment()->setWrapText(true);
        }

        // Auto-fit columns A, B, C
        foreach (['A', 'B', 'C'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // -------------------------------------------------------------
        // 5. STREAM DOWNLOAD
        // -------------------------------------------------------------
        $fileName = 'penyertaan-individu-' . \Illuminate\Support\Str::slug($program->title) . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

     public static function downloadProject(Program $program)
         {
             $spreadsheet = new Spreadsheet();
             $sheet = $spreadsheet->getActiveSheet();

             // -------------------------------------------------------------
             // 1. TITLE BLOCK (Row 1)
             // -------------------------------------------------------------
             $titleText = 'PENYERTAAN ' . \Illuminate\Support\Str::upper($program->title);

             $sheet->setCellValue('A1', $titleText);
             $sheet->mergeCells('A1:H1'); // Merge across all 8 columns
             $sheet->getRowDimension(1)->setRowHeight(40);

             // Title Styling (Bold, Size 14, Centered)
             $sheet->getStyle('A1')->applyFromArray([
                 'font' => [
                     'bold' => true,
                     'size' => 14,
                     'color' => ['rgb' => '1E293B'], // Dark Gray
                 ],
                 'alignment' => [
                     'horizontal' => Alignment::HORIZONTAL_CENTER,
                     'vertical'   => Alignment::VERTICAL_CENTER,
                 ],
                 'fill' => [
                     'fillType'   => Fill::FILL_SOLID,
                     'startColor' => ['rgb' => 'E2E8F0'], // Light Gray Accent
                 ]
             ]);

             // Row 2 is an empty spacer row
             $sheet->getRowDimension(2)->setRowHeight(15);

             // -------------------------------------------------------------
             // 2. TABLE HEADERS (Row 3)
             // -------------------------------------------------------------
             $headers = [
                 'Nama Kumpulan',
                 'Bahagian',
                 'Tajuk Projek',
                 'Penerangan Projek',
                 'Bil',
                 'Nama Ahli',
                 'No. Telefon',
                 'Emel',
             ];

             foreach ($headers as $colIndex => $headerTitle) {
                 $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
                 $sheet->setCellValue($columnLetter . '3', $headerTitle);
             }

             // Header Row Height & Styling
             $sheet->getRowDimension(3)->setRowHeight(35);
             $sheet->getStyle('A3:H3')->applyFromArray([
                 'font' => [
                     'bold'  => true,
                     'color' => ['rgb' => 'FFFFFF'],
                     'size'  => 11,
                 ],
                 'fill' => [
                     'fillType'   => Fill::FILL_SOLID,
                     'startColor' => ['rgb' => '1F2937'], // Dark Gray/Blue
                 ],
                 'alignment' => [
                     'horizontal' => Alignment::HORIZONTAL_CENTER,
                     'vertical'   => Alignment::VERTICAL_CENTER,
                     'wrapText'   => true,
                 ],
             ]);

             // -------------------------------------------------------------
             // 3. POPULATE DATA & MERGE PROJECT CELLS (Row 4 onwards)
             // -------------------------------------------------------------
             $submissions = Submission::where('program_id', $program->id)
                 ->with(['user.department', 'projectDetail.department'])
                 ->latest()
                 ->get();

             $currentRow = 4; // Data now starts at Row 4

             foreach ($submissions as $sub) {
                 $p = $sub->projectDetail;

                 $members = ($p && $p->file_path)
                     ? self::extractDocxMembers($p->file_path)
                     : [];

                 if (empty($members)) {
                     $members = [[
                         'name'  => $sub->user->name ?? 'N/A',
                         'phone' => '-',
                         'email' => $sub->user->email ?? 'N/A',
                     ]];
                 }

                 $memberCount = count($members);
                 $startRow = $currentRow;
                 $endRow = $startRow + $memberCount - 1;

                 foreach ($members as $index => $member) {
                     $row = $startRow + $index;

                     // Fill Project Level Details on the first member row
                     if ($index === 0) {
                         $sheet->setCellValue('A' . $row, $p->group_name ?? 'Individu');
                         $sheet->setCellValue('B' . $row, $p->department->name ?? $sub->user->department->name ?? 'N/A');
                         $sheet->setCellValue('C' . $row, $p->project_title ?? 'N/A');
                         $sheet->setCellValue('D' . $row, $p->project_description ?? 'N/A');
                     }

                     // Split member rows
                     $sheet->setCellValue('E' . $row, $index + 1);
                     $sheet->setCellValue('F' . $row, $member['name']);

                     // Force phone number as text string
                     $sheet->setCellValueExplicit(
                         'G' . $row,
                         $member['phone'],
                         \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                     );

                     $sheet->setCellValue('H' . $row, $member['email']);

                     // REMOVED fixed setRowHeight(22) to allow dynamic line wrapping height
                 }

                 // Merge Project-level Columns across member rows
                 if ($memberCount > 1) {
                     $sheet->mergeCells("A{$startRow}:A{$endRow}");
                     $sheet->mergeCells("B{$startRow}:B{$endRow}");
                     $sheet->mergeCells("C{$startRow}:C{$endRow}");
                     $sheet->mergeCells("D{$startRow}:D{$endRow}");
                 }

                 // Align merged project details vertically centered
                 $sheet->getStyle("A{$startRow}:H{$endRow}")->applyFromArray([
                     'alignment' => [
                         'vertical'   => Alignment::VERTICAL_CENTER,
                         'horizontal' => Alignment::HORIZONTAL_LEFT,
                     ]
                 ]);

                 $currentRow = $endRow + 1;
             }

             // -------------------------------------------------------------
             // COLUMN D DIMENSIONS & TEXT WRAPPING
             // -------------------------------------------------------------
             $sheet->getColumnDimension('D')->setAutoSize(false);
             $sheet->getColumnDimension('D')->setWidth(55); // Custom width for Column D

             // Enable text wrapping for Column D across all populated data rows
             if ($currentRow > 4) {
                 $lastDataRow = $currentRow - 1;
                 $sheet->getStyle("D4:D{$lastDataRow}")->getAlignment()->setWrapText(true);
             }

             // Auto-fit column widths for A-C and E-H
             foreach (range('A', 'H') as $col) {
                 if ($col !== 'D') {
                     $sheet->getColumnDimension($col)->setAutoSize(true);
                 }
             }

             // -------------------------------------------------------------
             // 4. STREAM DOWNLOAD
             // -------------------------------------------------------------
             $fileName = 'penyertaan-' . \Illuminate\Support\Str::slug($program->title) . '.xlsx';

             return response()->streamDownload(function () use ($spreadsheet) {
                 $writer = new Xlsx($spreadsheet);
                 $writer->save('php://output');
             }, $fileName, [
                 'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                 'Cache-Control' => 'max-age=0',
             ]);
         }
    /**
     * Extracts participant details from Word doc table.
     */
    private static function extractDocxMembers(?string $filePath): array
    {
        if (!$filePath) return [];

        $cleanPath = ltrim($filePath, '/');
        $cleanPath = str_replace(['public/', 'storage/'], '', $cleanPath);

        if (!Storage::disk('public')->exists($cleanPath)) {
            $fullPath = storage_path('app/public/' . $cleanPath);
            if (!file_exists($fullPath)) return [];
        } else {
            $fullPath = Storage::disk('public')->path($cleanPath);
        }

        if (strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)) !== 'docx') return [];

        $zip = new ZipArchive();
        if ($zip->open($fullPath) === true) {
            $xmlData = $zip->getFromName('word/document.xml');
            $zip->close();

            if ($xmlData) {
                $dom = new DOMDocument();
                libxml_use_internal_errors(true);
                $dom->loadXML($xmlData);
                libxml_clear_errors();

                $members = [];
                $rows = $dom->getElementsByTagName('tr');

                foreach ($rows as $row) {
                    $rowText = trim(preg_replace('/\s+/', ' ', $row->textContent));

                    if (preg_match('/(TAJUK PROJEK|RINGKASAN|PENGESAHAN|LAPORAN|OBJEKTIF)/i', $rowText)) {
                        break;
                    }

                    if (preg_match('/\(\d+\)/', $rowText)) {
                        $cells = $row->getElementsByTagName('tc');
                        $cellData = [];

                        foreach ($cells as $cell) {
                            $text = trim(preg_replace('/\s+/', ' ', $cell->textContent));
                            if (!empty($text) && !preg_match('/(Nama Peserta|Nama Kumpulan|Bahagian|No\.? Telefon|E-?Mel|BUTIRAN)/i', $text)) {
                                $cellData[] = $text;
                            }
                        }

                        $nameCandidate = '';
                        $phone = '-';
                        $email = '-';

                        foreach ($cellData as $data) {
                            $cleanData = trim(preg_replace('/^\(\d+\)\s*/', '', $data));

                            if (empty($cleanData)) continue;

                            if (filter_var($cleanData, FILTER_VALIDATE_EMAIL)) {
                                $email = $cleanData;
                            }
                            elseif (preg_match('/^[0-9\-\+\s]{7,15}$/', $cleanData)) {
                                $phoneNum = preg_replace('/[^0-9]/', '', $cleanData);
                                if (strpos($phoneNum, '0') !== 0) {
                                    $phoneNum = '0' . $phoneNum;
                                }
                                $phone = $phoneNum;
                            }
                            elseif (!is_numeric($cleanData) && strlen($cleanData) > 2) {
                                $isDepartment = preg_match('/(BAHAGIAN|JABATAN|SEKSYEN|UNIT|KEMENTERIAN|MAJLIS|PEJABAT|CAWANGAN|PUSAT|SEKOLAH|INSTITUT|LEMBAGA)/i', $cleanData);

                                if (!$isDepartment && (empty($nameCandidate) || preg_match('/^\(\d+\)/', $data))) {
                                    $nameCandidate = $cleanData;
                                }
                            }
                        }

                        if (!empty($nameCandidate)) {
                            $members[] = [
                                'name'  => $nameCandidate,
                                'phone' => $phone,
                                'email' => $email,
                            ];
                        }
                    }
                }

                return $members;
            }
        }

        return [];
    }
}
