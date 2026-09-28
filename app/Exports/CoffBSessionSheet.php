<?php

namespace App\Exports;

use App\Models\CoffeeBreakIdea;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CoffBSessionSheet implements FromQuery, WithTitle, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $session;
    private $rowNumber = 0;

    public function __construct($session)
    {
        $this->session = $session;
    }

    /**
     * Query all ideas belonging ONLY to this specific session ID
     */
    public function query()
    {
        return CoffeeBreakIdea::query()
            ->where('coffee_break_session_id', $this->session->id)
            ->latest();
    }

    /**
     * Excel Sheet Tab Title: Department Name only
     * (Excel tab names are capped at 31 chars max)
     */
    public function title(): string
    {
        $deptName = $this->session->department->name ?? 'Jabatan';

        return substr($deptName, 0, 31);
    }

    /**
     * Headers inside the session sheet
     */
    public function headings(): array
    {
        return [
            'No.',
            'Tajuk Idea',
            'Kategori',
            'Penerangan',
        ];
    }

    /**
     * Row values for each idea in this session
     */
    public function map($idea): array
    {
        $this->rowNumber++;

        // Format category: remove underscores & capitalize words (e.g. selain_inovasi -> Selain Inovasi)
        $formattedCategory = $idea->category
            ? Str::title(str_replace('_', ' ', $idea->category))
            : 'N/A';

        return [
            $this->rowNumber,
            $idea->title,
            $formattedCategory,
            $idea->suggestion,
        ];
    }

    /**
     * Apply Excel Sheet Styling: Heights, Colors, Center Alignment, and Text Wrapping
     */
    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();

        // 1. Set Header Row Height (Row 1)
        $sheet->getRowDimension(1)->setRowHeight(32);

        // 2. Set Row Heights for Data Rows
        for ($row = 2; $row <= $highestRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(28);
        }

        // 3. Enable Text Wrapping across all columns (A to D) & Vertically Center text
        $sheet->getStyle("A1:D{$highestRow}")
            ->getAlignment()
            ->setWrapText(true)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // 4. Center align 'No.' (Column A) and 'Kategori' (Column C)
        $sheet->getStyle("A2:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C2:C{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // 5. Header Styling (Teal Background + Bold White Text)
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0D9488'], // Teal-600
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                ],
            ],
        ];
    }
}
