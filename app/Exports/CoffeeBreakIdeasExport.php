<?php
namespace App\Exports;

use App\Models\CoffeeBreakSession;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CoffeeBreakIdeasExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $sessionId;

    public function __construct($sessionId)
    {
        $this->sessionId = $sessionId;
    }


    public function collection()
    {
        $session = CoffeeBreakSession::find($this->sessionId);
        return $session ? $session->ideas : collect([]);
    }

    public function headings(): array
    {
        return [
            'Bil',
            'Tajuk Idea',
            'Kategori',
            'Bentuk',
            'Cadangan / Isu',
            'Tindakan Penyelesaian',
            'Tarikh'
        ];
    }

    public function map($idea): array
    {
        static $counter = 0;
        $counter++;

        return [
            $counter,
            $idea->title,
            ucwords(str_replace('_', ' ', $idea->category)),
            $idea->is_digital === 'digital' ? 'Digital' : 'Bukan Digital',
            $idea->suggestion ?: 'Tiada cadangan',
            $idea->action_taken ?: 'Belum ada tindakan',
            $idea->created_at->format('d/m/Y')
        ];
    }
}
