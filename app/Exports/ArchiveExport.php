<?php

namespace App\Exports;

use App\Models\Archive;
use App\Models\Competition; // Added if you need to fetch the competition name by ID
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ArchiveExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithCustomStartCell, WithEvents
{
    protected array $filters;
    private int $rowNumber = 0;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function query()
    {
        $search = $this->filters['search'] ?? null;
        $selectedCompetition = $this->filters['selectedCompetition'] ?? null;
        $selectedYear = $this->filters['selectedYear'] ?? null;

        return Archive::query()
            ->select('archives.*')
            ->with(['department', 'competitions'])
            ->latest()
            ->when($search, function ($query) use ($search) {
                $query->where('project_name', 'like', '%' . $search . '%');
            })
            ->when($selectedCompetition || $selectedYear, function ($query) use ($selectedCompetition, $selectedYear) {
                $query->whereHas('competitions', function ($q) use ($selectedCompetition, $selectedYear) {
                    $q->when($selectedCompetition, function ($pivotQuery) use ($selectedCompetition) {
                        $pivotQuery->where('archive_competition.competition_id', $selectedCompetition);
                    })
                    ->when($selectedYear, function ($pivotQuery) use ($selectedYear) {
                        $pivotQuery->where('archive_competition.year', $selectedYear);
                    });
                });
            });
    }

    public function headings(): array
    {
        return [
            'No.',
            'Nama Kumpulan',
            'Nama Projek',
            'Penerangan',
            'Bahagian',
            'Pertandingan',
            'Tahun',
        ];
    }

    public function map($archive): array
    {
        $this->rowNumber++;

        $selectedCompetition = $this->filters['selectedCompetition'] ?? null;
        $selectedYear = $this->filters['selectedYear'] ?? null;

        $filteredCompetitions = $archive->competitions->when($selectedCompetition || $selectedYear, function ($collection) use ($selectedCompetition, $selectedYear) {
            return $collection->filter(function ($competition) use ($selectedCompetition, $selectedYear) {
                $matchComp = $selectedCompetition ? $competition->id == $selectedCompetition : true;
                $matchYear = $selectedYear ? $competition->pivot->year == $selectedYear : true;

                return $matchComp && $matchYear;
            });
        });

        return [
            $this->rowNumber,
            $archive->group_name,
            $archive->project_name,
            $archive->description ?? '-',
            $archive->department->name ?? '-',
            $filteredCompetitions->pluck('name')->implode(', ') ?: '-',
            $filteredCompetitions->pluck('pivot.year')->filter()->implode(', ') ?: '-',
        ];
    }

    /**
     * Build dynamic header title based on applied filters
     */
    private function getDynamicTitle(): string
    {
        $selectedCompetitionId = $this->filters['selectedCompetition'] ?? null;
        $selectedYear = $this->filters['selectedYear'] ?? null;

        // 1. Neither competition nor year is selected
        if (!$selectedCompetitionId && !$selectedYear) {
            return 'Projek-projek Inovasi';
        }

        // 2. Fetch competition name if selected
        $competitionName = null;
        if ($selectedCompetitionId) {
            $competitionName = Competition::find($selectedCompetitionId)?->name;
        }

        // 3. Construct dynamic title depending on selected values
        $titleParts = [];

        if ($competitionName) {
            $titleParts[] = 'Projek ' . $competitionName;
        } else {
            $titleParts[] = 'Projek-projek Inovasi';
        }

        if ($selectedYear) {
            $titleParts[] = $selectedYear;
        }

        return implode(' ', $titleParts);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Get dynamic title text
                $titleText = $this->getDynamicTitle();

                // Set title text and merge cells A1 to G1
                $sheet->setCellValue('A1', $titleText);
                $sheet->mergeCells('A1:G1');
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getRowDimension(1)->setRowHeight(40);
        $sheet->getRowDimension(3)->setRowHeight(35);

        $sheet->getColumnDimension('D')->setAutoSize(false)->setWidth(45);

        // Title styling
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['argb' => '1F2937'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Wrap text for data rows
        $sheet->getStyle('A3:' . $sheet->getHighestColumn() . $sheet->getHighestRow())
            ->getAlignment()
            ->setWrapText(true)
            ->setVertical(Alignment::VERTICAL_TOP);

        // Header style
        return [
            3 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => '0D9488'],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }
}
