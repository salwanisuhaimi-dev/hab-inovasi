<?php

namespace App\Exports;

use App\Models\Archive;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ArchiveExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected array $filters;
    private int $rowNumber = 0;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
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
            'Nama Projek',
            'Deskripsi',
            'Jabatan',
            'Pertandingan',
            'Tahun Pertandingan',
            'Tarikh Cipta',
        ];
    }

    public function map($archive): array
    {
        $this->rowNumber++;

        $selectedCompetition = $this->filters['selectedCompetition'] ?? null;
        $selectedYear = $this->filters['selectedYear'] ?? null;

        // Filter koleksi competitions berdasarkan nilai dropdown jika ada
        $filteredCompetitions = $archive->competitions->when($selectedCompetition || $selectedYear, function ($collection) use ($selectedCompetition, $selectedYear) {
            return $collection->filter(function ($competition) use ($selectedCompetition, $selectedYear) {
                $matchComp = $selectedCompetition ? $competition->id == $selectedCompetition : true;
                $matchYear = $selectedYear ? $competition->pivot->year == $selectedYear : true;

                return $matchComp && $matchYear;
            });
        });

        return [
            $this->rowNumber,
            $archive->project_name,
            $archive->description ?? '-',
            $archive->department->name ?? '-',
            $filteredCompetitions->pluck('name')->implode(', ') ?: '-',
            $filteredCompetitions->pluck('pivot.year')->filter()->implode(', ') ?: '-',
            $archive->created_at?->format('d/m/Y') ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // 1. Tinggi baris Header
        $sheet->getRowDimension(1)->setRowHeight(35);

        // 2. Lebar khas untuk kolum Deskripsi
        $sheet->getColumnDimension('C')->setAutoSize(false)->setWidth(45);

        // 3. Text wrap untuk semua sel
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . $sheet->getHighestRow())
            ->getAlignment()
            ->setWrapText(true)
            ->setVertical(Alignment::VERTICAL_TOP);

        // 4. Style Header
        return [
            1 => [
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
