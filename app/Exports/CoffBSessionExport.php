<?php

namespace App\Exports;

use App\Models\CoffeeBreakSession;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CoffBSessionExport implements WithMultipleSheets
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Generate sheets based on sessions that match the date and department filters
     */
    public function sheets(): array
    {
        $sheets = [];

        $selectedDepartment = $this->filters['selectedDepartment'] ?? null;
        $dateFrom = $this->filters['date_from'] ?? null;
        $dateTo = $this->filters['date_to'] ?? null;

        // 1. Filter sessions according to department and date range
        $sessions = CoffeeBreakSession::with('department')
            ->when($selectedDepartment, function ($query) use ($selectedDepartment) {
                $query->where('department_id', $selectedDepartment);
            })
            ->when($dateFrom, function ($query) use ($dateFrom) {
                $query->whereDate('date_created', '>=', $dateFrom); // Adjust to 'created_at' if needed
            })
            ->when($dateTo, function ($query) use ($dateTo) {
                $query->whereDate('date_created', '<=', $dateTo); // Adjust to 'created_at' if needed
            })
            ->latest('date_created')
            ->get();

        // 2. Loop through each matched session and generate its sheet (with all its ideas)
        foreach ($sessions as $session) {
            $sheets[] = new CoffBSessionSheet($session);
        }

        return $sheets;
    }
}
