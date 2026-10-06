<?php

use App\Models\Department;
use App\Models\Competition;
use App\Models\Archive;
use function Livewire\Volt\{layout, title, state, with, mount, updated};

layout('layouts.app');
title('Laporan Analitik Arkib Projek');

state([
    'selectedYear' => 2025,
    'selectedCompetitionId' => null,
    'selectedDeptId' => null,
    'years' => [2024, 2025, 2026]
]);

mount(function() {
    $firstComp = Competition::first();
    $this->selectedCompetitionId = $firstComp ? $firstComp->id : null;
    $this->selectedDeptId = request('dept_id') ? (int) request('dept_id') : null;
});

// Helper method defined as an arrow function bound to $this
$getChartData = function() {
    if (!$this->selectedCompetitionId) {
        return [];
    }

    $compId = (int) $this->selectedCompetitionId;
    $year = (int) $this->selectedYear;
    $deptId = $this->selectedDeptId;

    return Department::when($deptId, fn($q) => $q->where('id', $deptId))
        ->get()
        ->map(function ($dept) use ($compId, $year) {
            $total = Archive::where('department_id', $dept->id)
                ->whereHas('competitions', function ($q) use ($compId, $year) {
                    $q->where('competitions.id', $compId)
                      ->where('archive_competition.year', $year);
                })
                ->count();

            return [
                'dept'  => $dept->code ?? $dept->name,
                'total' => $total,
            ];
        })
        ->values()
        ->toArray();
};

updated([
    'selectedYear' => fn() => $this->dispatch('update-chart', data: $this->getChartData()),
    'selectedCompetitionId' => fn() => $this->dispatch('update-chart', data: $this->getChartData()),
]);

with([
    'competitionsList' => fn() => Competition::get(['id', 'name']),
    'barChartData' => fn() => $this->getChartData(),
    // Pass active competition model for the print banner
    'activeCompetition' => fn() => Competition::find($this->selectedCompetitionId),
]);

?>

<div class="p-6 bg-gray-50 min-h-screen print:bg-transparent print:p-0">
    <style>
        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm;
            }

            html, body, .min-h-screen {
                background-color: white !important;
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
            }

            /* Allow colored headers, badges, and zebra rows in print */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .print-hide-overlay, .print\:hidden {
                display: none !important;
            }

            .print-stack-wrapper {
                display: block !important;
                width: 100% !important;
            }

            .chart-container {
                position: relative !important;
                height: 220px !important;
                max-height: 220px !important;
                width: 100% !important;
                margin-bottom: 12px !important;
                display: block !important;
            }

            .chart-container canvas {
                max-height: 220px !important;
                width: 100% !important;
            }

            /* Table print container */
            .print-table-wrapper {
                display: block !important;
                width: 100% !important;
                margin-top: 8px !important;
                clear: both !important;
                border: 1px solid #e2e8f0 !important;
            }

            .print-table-wrapper table {
                width: 100% !important;
                table-layout: auto !important;
            }

            table th, table td {
                padding: 3px 8px !important;
                font-size: 10px !important;
                line-height: 1.2 !important;
           }

           tr {
              page-break-inside: avoid !important;
           }
       }
    </style>

    <div class="max-w-6xl mx-auto space-y-6 print:space-y-3">
        <!-- Header & Print Action Button -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 print:mb-2">
            <div>
                <h2 class="text-lg font-semibold text-gray-700 mb-1 print:text-base print:font-bold">Analisis Penglibatan Tertinggi Bahagian</h2>
                <p class="text-sm text-gray-500 print:text-xs">Laporan penyertaan mengikut bahagian bagi pertandingan dan tahun yang dipilih.</p>
            </div>

            <!-- Butang Cetak (Sembunyi sewaktu dicetak) -->
            <button
                type="button"
                onclick="window.print()"
                class="print:hidden inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 shadow-sm transition-all cursor-pointer"
            >
                <svg class="w-4 h-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m0 0a48.1 48.1 0 0 1 10.5 0m-10.5 0V3.375c0-.621.504-1.125 1.125-1.125h8.25c.621 0 1.125.504 1.125 1.125v3.656M5.25 12h.008v.008H5.25V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
                <span>Cetak Laporan</span>
            </button>
        </div>

        <!-- 1. PRINT-ONLY HEADER (Hanya muncul dalam printout) -->
        <div class="hidden print:block border-b border-gray-300 pb-2 mb-3">
            <p class="text-xs font-semibold text-gray-800 uppercase tracking-wider">
                @if($activeCompetition || $selectedYear)
                    Kategori: <span class="font-bold text-black">{{ $activeCompetition->name ?? 'Projek-projek Inovasi' }}</span>
                    @if($selectedYear)
                        | Tahun: <span class="font-bold text-black">{{ $selectedYear }}</span>
                    @endif
                @else
                    Projek-projek Inovasi
                @endif
            </p>
        </div>

        <!-- 2. SCREEN-ONLY INTERACTIVE FILTERS (Sembunyi sewaktu cetak) -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 space-y-4 print:hidden">
            <div class="flex flex-wrap gap-6 items-center">
                <!-- Penapis 1: Tahun -->
                <div class="space-y-1.5">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Tahun</span>
                    <div class="flex items-center space-x-1 bg-gray-100 p-1 rounded-lg">
                        @foreach($years as $year)
                            <button
                                type="button"
                                wire:click="$set('selectedYear', {{ (int)$year }})"
                                class="px-3 py-1.5 text-xs font-semibold rounded-md transition-all {{ (int)$selectedYear === (int)$year ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}"
                            >
                                {{ $year }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Penapis 2: Jenis Pertandingan -->
                <div class="space-y-1.5">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Kategori Pertandingan</span>
                    <div class="flex flex-wrap gap-1 bg-gray-100 p-1 rounded-lg">
                        @foreach($competitionsList as $comp)
                            <button
                                type="button"
                                wire:click="$set('selectedCompetitionId', {{ (int)$comp->id }})"
                                class="px-3 py-1.5 text-xs font-semibold rounded-md transition-all {{ (int)$selectedCompetitionId === (int)$comp->id ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:text-indigo-600 hover:bg-white/50' }}"
                            >
                                {{ $comp->name }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Wrapper Stack -->
        <div class="print-stack-wrapper space-y-6 print:space-y-2">
            <!-- 1. CARTA BAR -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 print:p-0 print:border-none print:shadow-none">
                <div
                    wire:ignore
                    x-data="{
                        chart: null,
                        hasData: true,
                        renderChart(dataRaw) {
                            if (!dataRaw || dataRaw.length === 0) {
                                this.hasData = false;
                                if (this.chart) this.chart.destroy();
                                return;
                            }

                            this.hasData = true;

                            this.$nextTick(() => {
                                if (this.chart) this.chart.destroy();

                                const labels = dataRaw.map(item => item.dept);
                                const totals = dataRaw.map(item => item.total);

                                this.chart = new Chart(this.$refs.canvas, {
                                    type: 'bar',
                                    data: {
                                        labels: labels,
                                        datasets: [{
                                            label: 'Jumlah Penyertaan',
                                            data: totals,
                                            backgroundColor: '#4f46e5',
                                            borderRadius: 3,
                                            barThickness: 16
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        scales: {
                                            x: {
                                                ticks: { font: { size: 9 } }
                                            },
                                            y: {
                                                beginAtZero: true,
                                                ticks: { precision: 0, font: { size: 9 } }
                                            }
                                        }
                                    }
                                });
                            });
                        }
                    }"
                    x-init="renderChart(@js($barChartData))"
                    x-on:update-chart.window="renderChart($event.detail.data)"
                    class="h-96 w-full relative chart-container"
                >
                    <div wire:loading class="print-hide-overlay absolute inset-0 bg-white/60 backdrop-blur-xs flex items-center justify-center z-10 rounded-xl">
                        <span class="text-sm font-semibold text-indigo-600 animate-pulse">Memuatkan data penapis...</span>
                    </div>

                    <div x-show="hasData" class="w-full h-full">
                        <canvas x-ref="canvas"></canvas>
                    </div>

                    <div x-show="!hasData" class="h-full flex flex-col items-center justify-center text-gray-400 italic text-sm">
                        Tiada sebarang rekod penyertaan untuk kombinasi penapis ini.
                    </div>
                </div>
            </div>

            <!-- JADUAL RINGKASAN DATA (COLORED) -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden print-table-wrapper print:shadow-none">

                <!-- Table Header Bar -->
                <div class="p-4 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between print:p-2 print:bg-slate-100">
                    <h3 class="text-sm font-bold text-slate-800 print:text-[10px]">Senarai Penglibatan Mengikut Bahagian</h3>
                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold text-indigo-700 bg-indigo-100/80 rounded-md print:text-[9px] print:p-0.5 print:bg-transparent">
                        Jumlah Bahagian: {{ count($barChartData) }}
                    </span>
                </div>

                <div class="w-full overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <!-- Colored Table Column Headers (Indigo Accent) -->
                            <tr class="bg-indigo-600 text-white text-xs font-bold uppercase tracking-wider print:bg-indigo-700">
                                <th scope="col" class="py-2.5 px-3 w-12 text-center print:w-10">No.</th>
                                <th scope="col" class="py-2.5 px-3">Bahagian / Jabatan</th>
                                <th scope="col" class="py-2.5 px-3 text-right w-32 print:w-24">Jumlah Penyertaan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($barChartData as $index => $row)
                                <!-- Alternating Zebra Stripe Rows ($loop->even) -->
                                <tr class="{{ $loop->even ? 'bg-slate-50/60' : 'bg-white' }} hover:bg-indigo-50/40 transition-colors">
                                    <td class="py-1.5 px-3 text-center text-slate-400 font-mono text-xs print:text-[9px]">{{ $index + 1 }}</td>
                                    <td class="py-1.5 px-4 font-semibold text-slate-800 print:text-[9.5px]">{{ $row['dept'] }}</td>
                                    <td class="py-1.5 px-4 text-right print:text-[9.5px]">
                                        @if($row['total'] > 0)
                                            <!-- Indigo Badge for Non-Zero Counts -->
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-md print:border-none print:p-0 print:bg-transparent">
                                                {{ $row['total'] }}
                                            </span>
                                        @else
                                            <!-- Subtle Grey Zero -->
                                            <span class="text-slate-400 font-normal text-xs">0</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-slate-400 italic text-xs">
                                        Tiada data untuk dipaparkan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(count($barChartData) > 0)
                            <tfoot>
                                <!-- Colored Summary Footer -->
                                <tr class="bg-slate-100/80 font-bold text-slate-900 border-t-2 border-slate-300 print:bg-slate-100">
                                    <td colspan="2" class="py-2 px-4 text-right uppercase text-xs text-slate-600 print:text-[9px]">
                                        Jumlah Keseluruhan
                                    </td>
                                    <td class="py-2 px-4 text-right text-indigo-700 font-extrabold text-sm print:text-[10px]">
                                        {{ array_sum(array_column($barChartData, 'total')) }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
