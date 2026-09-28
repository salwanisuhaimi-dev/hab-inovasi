<?php

use App\Models\Department;
use App\Models\CoffeeBreakIdea;
use function Livewire\Volt\{layout, title, with};

layout('layouts.app');
title('Laporan Analitik Coff-B');

with([
  'selectedPeriodName' => function() {
      $month = request('month');
      $year  = request('year', date('Y'));

      if (!$month) {
          return "Keseluruhan Tahun {$year}";
      }

      $labels = [
          'h1' => "Separuh Pertama (Jan - Jun) {$year}",
          'h2' => "Separuh Kedua (Jul - Dis) {$year}",
          'q1' => "Suku Pertama (Q1) {$year}",
          'q2' => "Suku Kedua (Q2) {$year}",
          'q3' => "Suku Ketiga (Q3) {$year}",
          'q4' => "Suku Keempat (Q4) {$year}",
      ];

      if (isset($labels[$month])) {
          return $labels[$month];
      }

      if (is_numeric($month) && $month >= 1 && $month <= 12) {
          $monthName = date('F', mktime(0, 0, 0, (int)$month, 1));
          return "Bulan {$monthName} {$year}";
      }

      return "Tahun {$year}";
  },

    'reportData' => function() {
        $selectedMonth = request('month'); // 'q1', 'h1', '01', or null
        $selectedYear = request('year', date('Y'));
        $selectedDeptId = request('dept_id');

        $query = CoffeeBreakIdea::whereHas('session', function($q) use ($selectedDeptId, $selectedYear, $selectedMonth) {

            if ($selectedDeptId) {
                $q->where('department_id', $selectedDeptId);
            }

            $q->whereYear('date_created', $selectedYear);

            if ($selectedMonth) {
                if (str_starts_with($selectedMonth, 'q')) {
                    $quarters = [
                        'q1' => [1, 2, 3],
                        'q2' => [4, 5, 6],
                        'q3' => [7, 8, 9],
                        'q4' => [10, 11, 12],
                    ];
                    $months = $quarters[$selectedMonth] ?? [];
                    $q->whereIn(\DB::raw('MONTH(date_created)'), $months);
                }
                else if (str_starts_with($selectedMonth, 'h')) {
                    $halves = [
                        'h1' => [1, 2, 3, 4, 5, 6],
                        'h2' => [7, 8, 9, 10, 11, 12],
                    ];
                    $months = $halves[$selectedMonth] ?? [];
                    $q->whereIn(\DB::raw('MONTH(date_created)'), $months);
                }
                else {
                    $q->whereMonth('date_created', $selectedMonth);
                }
            }
        });

        $allIdeas = $query->with('session')->get();

        if (!$selectedDeptId) {
            return collect([[
                'name' => 'KESELURUHAN',
                'stats' => [
                    'Inovasi (Digital)' => $allIdeas->where('category', 'inovasi')->where('is_digital', 'digital')->count(),
                    'Inovasi (Bukan Digital)' => $allIdeas->where('category', 'inovasi')->where('is_digital', 'non-digital')->count(),
                    'Selain Inovasi (Digital)' => $allIdeas->where('category', 'selain_inovasi')->where('is_digital', 'digital')->count(),
                    'Selain Inovasi (Bukan Digital)' => $allIdeas->where('category', 'selain_inovasi')->where('is_digital', 'non-digital')->count(),
                ],
                'total' => $allIdeas->count()
            ]])->filter(fn($item) => $item['total'] > 0);
        }

        return Department::where('id', $selectedDeptId)->get()->map(function($dept) use ($allIdeas) {
            $deptIdeas = $allIdeas->filter(function($idea) use ($dept) {
                return $idea->session->department_id == $dept->id;
            });

            return [
                'name' => $dept->name,
                'stats' => [
                    'Inovasi (Digital)' => $deptIdeas->where('category', 'inovasi')->where('is_digital', 'digital')->count(),
                    'Inovasi (Bukan Digital)' => $deptIdeas->where('category', 'inovasi')->where('is_digital', 'non-digital')->count(),
                    'Selain Inovasi (Digital)' => $deptIdeas->where('category', 'selain_inovasi')->where('is_digital', 'digital')->count(),
                    'Selain Inovasi (Bukan Digital)' => $deptIdeas->where('category', 'selain_inovasi')->where('is_digital', 'non-digital')->count(),
                ],
                'total' => $deptIdeas->count()
            ];
        })->filter(fn($item) => $item['total'] > 0);
    },

    'barChartData' => function() {
        $selectedMonth  = request('month');
        $selectedYear   = request('year', date('Y'));
        $selectedDeptId = request('dept_id');

        return Department::when($selectedDeptId, fn($q) => $q->where('id', $selectedDeptId))
            ->get()
            ->map(function($dept) use ($selectedMonth, $selectedYear) {
                $ideas = CoffeeBreakIdea::whereHas('session', function($q) use ($dept, $selectedYear, $selectedMonth) {
                        $q->where('department_id', $dept->id)
                          ->whereYear('date_created', $selectedYear);

                        if ($selectedMonth) {
                            if (str_starts_with($selectedMonth, 'q')) {
                                $quarters = [
                                    'q1' => [1, 2, 3],
                                    'q2' => [4, 5, 6],
                                    'q3' => [7, 8, 9],
                                    'q4' => [10, 11, 12],
                                ];
                                $q->whereIn(\DB::raw('MONTH(date_created)'), $quarters[$selectedMonth] ?? []);
                            }
                            elseif (str_starts_with($selectedMonth, 'h')) {
                                $halves = [
                                    'h1' => [1, 2, 3, 4, 5, 6],
                                    'h2' => [7, 8, 9, 10, 11, 12],
                                ];
                                $q->whereIn(\DB::raw('MONTH(date_created)'), $halves[$selectedMonth] ?? []);
                            }
                            else {
                                $q->whereMonth('date_created', $selectedMonth);
                            }
                        }
                    })
                    ->get();

                return [
                    'dept'           => $dept->code ?? $dept->name,
                    'inovasi'        => $ideas->where('category', 'inovasi')->count(),
                    'selain_inovasi' => $ideas->where('category', 'selain_inovasi')->count(),
                    'total'          => $ideas->count()
                ];
            });
    },

    'departments' => fn() => Department::orderBy('name')->get(),

    'availableYears' => function() {
        $currentYear = date('Y');
        return range($currentYear, $currentYear - 2);
    }

]);

?>

<div class="p-8 max-w-7xl mx-auto min-h-screen bg-slate-50/50">
    {{-- Load Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
    @media print {
        /* 1. Page Setup with Balanced Top & Side Margins */
        @page {
            size: A4 portrait;
            margin-top: 15mm !important; /* Top margin gap for page header */
            margin-bottom: 12mm !important;
            margin-left: 8mm !important;
            margin-right: 8mm !important;
        }

        /* 2. Global White Background Reset */
        *, ::before, ::after {
            box-sizing: border-box !important;
        }

        html, body, div, main, .min-h-screen {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #ffffff !important;
            background-color: #ffffff !important;
            color: #000000 !important;
            overflow: visible !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* 3. Hide Non-Print UI Controls */
        nav, sidebar, header, .no-print, button, form, input, select {
            display: none !important;
        }

        /* 4. Full-Width Container Expansion */
        .max-w-7xl, .grid, .print-card, .bg-white {
            width: 100% !important;
            max-width: 100% !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            box-shadow: none !important;
        }

        /* 5. Print Card Expansion & Top Gap Margin */
        .print-card {
            display: block !important;
            break-before: page !important;
            page-break-before: always !important;
            break-inside: avoid !important;
            page-break-inside: avoid !important;
            width: 100% !important;
            padding: 1.5rem !important;
            margin-top: 10mm !important; /* Margin top gap for cards & breaks */
            margin-bottom: 0 !important;
            background: #ffffff !important;
        }

        /* First card doesn't force a preceding page break */
        .print-card:first-of-type {
            break-before: auto !important;
            page-break-before: auto !important;
            margin-top: 4mm !important;
        }

        /* Override grey slate backgrounds */
        .bg-slate-50\/50, .bg-slate-50, .bg-slate-100 {
            background: #ffffff !important;
            background-color: #ffffff !important;
        }

        /* 6. Chart Height */
        canvas {
            max-width: 100% !important;
            max-height: 200px !important;
        }

        /* 7. Tables Layout */
        .overflow-x-auto, .overflow-hidden {
            overflow: visible !important;
            width: 100% !important;
        }

        table {
            width: 100% !important;
            border-collapse: collapse !important;
            margin-top: 0.5rem !important;
        }

        th, td {
            padding: 6px 8px !important;
            font-size: 10px !important;
            line-height: 1.3 !important;
        }

        tr {
            break-inside: avoid !important;
            page-break-inside: avoid !important;
        }

        tfoot {
            display: table-row-group !important;
        }
    }
    </style>

    {{-- Header --}}
    <div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <a href="{{ url()->previous() }}" wire:navigate class="no-print px-5 py-2.5 bg-white rounded-2xl border border-slate-200 text-slate-600 text-xs font-black uppercase tracking-widest hover:bg-slate-50 transition shadow-sm">
                &larr; Kembali
            </a>
            <div>
                <h2 class="text-3xl font-black text-slate-900 tracking-tight uppercase">Analisis Idea Coff-B</h2>
                <p class="text-sm text-slate-500 font-medium italic">Pecahan kategori idea mengikut setiap bahagian jabatan <span class="font-bold text-slate-700">{{ $selectedPeriodName }}</span> .</p>
            </div>
        </div>

        <button onclick="window.print()" class=" no-print px-8 py-3 bg-slate-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-800 transition shadow-xl shadow-slate-200 cursor-pointer">
            Cetak Laporan
        </button>
    </div>

    {{-- Filter Form --}}
    <form method="GET" action="{{ url()->current() }}" class="no-print flex flex-wrap gap-4 items-end bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 my-8">
        {{-- Filter Bahagian --}}
        <div class="flex-1 min-w-[200px]">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-2">Pilih Bahagian</label>
            <select name="dept_id" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-500">
                 <option value="">Semua Bahagian</option>
                 @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('dept_id') == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                 @endforeach
            </select>
        </div>

        {{-- Filter Bulan --}}
        <div class="flex-1 min-w-[200px]">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-2">Pilih Bulan</label>
            <select name="month" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-500">
                <option value="">Keseluruhan Tahun</option>

                {{-- Separuh Tahun --}}
                <optgroup label="Separuh Tahun (Half Year)">
                    <option value="h1" {{ request('month') == 'h1' ? 'selected' : '' }}>Separuh Pertama (Jan - Jun)</option>
                    <option value="h2" {{ request('month') == 'h2' ? 'selected' : '' }}>Separuh Kedua (Jul - Dis)</option>
                </optgroup>

                {{-- Suku Tahun --}}
                <optgroup label="Suku Tahun (Quarter)">
                    <option value="q1" {{ request('month') == 'q1' ? 'selected' : '' }}>Suku Pertama (Q1)</option>
                    <option value="q2" {{ request('month') == 'q2' ? 'selected' : '' }}>Suku Kedua (Q2)</option>
                    <option value="q3" {{ request('month') == 'q3' ? 'selected' : '' }}>Suku Ketiga (Q3)</option>
                    <option value="q4" {{ request('month') == 'q4' ? 'selected' : '' }}>Suku Keempat (Q4)</option>
                </optgroup>

                <optgroup label="Bulanan">
                    @foreach(range(1, 12) as $m)
                        @php $mVal = sprintf('%02d', $m); @endphp
                        <option value="{{ $mVal }}" {{ request('month') == $mVal ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                        </option>
                    @endforeach
               </optgroup>
          </select>
        </div>

        {{-- Filter Tahun --}}
        <div class="flex-1 min-w-[150px]">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-2">Pilih Tahun</label>
            <select name="year" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-500">
                @foreach($availableYears as $year)
                    <option value="{{ $year }}" {{ request('year', date('Y')) == $year ? 'selected' : '' }}>
                        {{ $year }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex gap-2">
             <button type="submit" class="bg-slate-900 text-white px-8 py-3 rounded-2xl text-sm font-bold hover:bg-black transition-all cursor-pointer">
                Tapis
             </button>
             <a href="{{ url()->current() }}" class="bg-slate-100 text-slate-500 px-5 py-3 rounded-2xl text-sm font-bold hover:bg-slate-200 transition-all text-center">
                Reset
             </a>
        </div>
    </form>

    {{-- Grid Carta Pie --}}
    <div class="grid grid-cols-1 gap-8">
        @foreach($reportData as $index => $data)
            @php
                $total = (int)$data['total'];

                $inovasiDigital = (int)($data['stats']['Inovasi (Digital)'] ?? 0);
                $inovasiBukanDigital = (int)($data['stats']['Inovasi (Bukan Digital)'] ?? 0);
                $selainInovasiDigital = (int)($data['stats']['Selain Inovasi (Digital)'] ?? 0);
                $selainInovasiBukanDigital = (int)($data['stats']['Selain Inovasi (Bukan Digital)'] ?? 0);

                $pInovasiDigital = $total > 0 ? round(($inovasiDigital / $total) * 100, 1) : 0;
                $pInovasiBukanDigital = $total > 0 ? round(($inovasiBukanDigital / $total) * 100, 1) : 0;
                $pSelainInovasiDigital = $total > 0 ? round(($selainInovasiDigital / $total) * 100, 1) : 0;
                $pSelainInovasiBukanDigital = $total > 0 ? round(($selainInovasiBukanDigital / $total) * 100, 1) : 0;
            @endphp

            <div class="print-card bg-white p-8 rounded-[3rem] border border-slate-100 shadow-sm min-h-[450px] flex flex-col w-full">
                <h4 class="text-[11px] font-black text-slate-900 uppercase tracking-widest mb-6 text-center border-b pb-4">
                    {{ $data['name'] }}
                </h4>

                <div class="flex-1 relative flex items-center justify-center bg-slate-50/50 rounded-[2rem] p-4 print:bg-transparent">
                    <div id="debug-{{ $index }}" class="absolute text-[10px] text-slate-400 font-bold uppercase animate-pulse">
                        Menunggu Carta...
                    </div>

                    <div class="w-full h-80 print:h-64">
                        <canvas id="chart-{{ $index }}"></canvas>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left text-slate-600">
                            <thead class="text-[10px] text-slate-400 uppercase bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <th scope="col" class="py-2 px-3">Kategori</th>
                                    <th scope="col" class="py-2 px-3 text-center">Jumlah</th>
                                    <th scope="col" class="py-2 px-3 text-right">Peratus (%)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 font-medium">
                                <tr>
                                    <td class="py-2 px-3 flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-[#3b82f6]"></span>
                                        Inovasi (Digital)
                                    </td>
                                    <td class="py-2 px-3 text-center">{{ $inovasiDigital }}</td>
                                    <td class="py-2 px-3 text-right font-semibold text-slate-700">{{ $pInovasiDigital }}%</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-[#22d3ee]"></span>
                                        Inovasi (Bukan Digital)
                                    </td>
                                    <td class="py-2 px-3 text-center">{{ $inovasiBukanDigital }}</td>
                                    <td class="py-2 px-3 text-right font-semibold text-slate-700">{{ $pInovasiBukanDigital }}%</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-[#f97316]"></span>
                                        Selain Inovasi (Digital)
                                    </td>
                                    <td class="py-2 px-3 text-center">{{ $selainInovasiDigital }}</td>
                                    <td class="py-2 px-3 text-right font-semibold text-slate-700">{{ $pSelainInovasiDigital }}%</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-[#fbbf24]"></span>
                                        Selain Inovasi (Bukan Digital)
                                    </td>
                                    <td class="py-2 px-3 text-center">{{ $selainInovasiBukanDigital }}</td>
                                    <td class="py-2 px-3 text-right font-semibold text-slate-700">{{ $pSelainInovasiBukanDigital }}%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                        Jumlah keseluruhan: {{ $total }} Idea
                    </span>
                </div>

                <script>
                    (function() {
                        const render = () => {
                            const canvas = document.getElementById('chart-{{ $index }}');
                            const debugText = document.getElementById('debug-{{ $index }}');
                            if (!canvas || typeof Chart === 'undefined') return;

                            const rawData = [
                                {{ $inovasiDigital }},
                                {{ $inovasiBukanDigital }},
                                {{ $selainInovasiDigital }},
                                {{ $selainInovasiBukanDigital }}
                            ];

                            if (rawData.every(val => val === 0)) {
                                if (debugText) debugText.innerText = 'Tiada Data';
                                return;
                            }

                            if (Chart.getChart(canvas)) {
                                Chart.getChart(canvas).destroy();
                            }

                            new Chart(canvas, {
                                type: 'pie',
                                data: {
                                    labels: ['Inovasi (D)', 'Inovasi (BD)', 'Selain Inovasi (D)', 'Selain Inovasi (BD)'],
                                    datasets: [{
                                        data: rawData,
                                        backgroundColor: ['#3b82f6', '#22d3ee', '#f97316', '#fbbf24'],
                                        borderWidth: 6,
                                        borderColor: '#ffffff',
                                        hoverOffset: 20
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    layout: { padding: { bottom: 10 } },
                                    plugins: {
                                        legend: { display: false },
                                        tooltip: {
                                            backgroundColor: '#0f172a',
                                            padding: 12,
                                            cornerRadius: 12,
                                            callbacks: {
                                                label: function(context) {
                                                    let value = context.raw || 0;
                                                    let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                                    let percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                                    return ` ${context.label}: ${value} (${percentage}%)`;
                                                }
                                            }
                                        }
                                    }
                                }
                            });
                            if (debugText) debugText.style.display = 'none';
                        };
                        setTimeout(render, 300);
                    })();
                </script>
            </div>
        @endforeach
    </div>

    {{-- Section Bar Chart --}}
    <div class="print-card bg-white p-8 rounded-[3rem] border border-slate-100 shadow-sm min-h-[450px] flex flex-col w-full mt-8">
        <div class="mb-8">
            <h4 class="text-sm font-black text-slate-900 uppercase tracking-widest">Perbandingan Kategori Idea Mengikut Bahagian</h4>
            <p class="text-xs text-slate-400 font-medium italic">Analisis jumlah Inovasi berbanding Selain Inovasi bagi setiap bahagian.</p>
        </div>

        <div class="h-96 w-full">
            <canvas id="groupedBarChart"></canvas>
        </div>
    </div>

    {{-- Section Table --}}
    <div class="print-card bg-white p-8 rounded-[3rem] border border-slate-100 shadow-sm mt-8">
        <div class="mb-6 flex justify-between items-center px-2">
            <div>
                <h4 class="text-sm font-black text-slate-900 uppercase tracking-widest">Jadual Statistik Bahagian</h4>
                <p class="text-[10px] text-slate-400 font-medium italic">Data terperinci bagi tempoh yang dipilih</p>
            </div>
        </div>

        <div class="mt-6 pt-4 border-t border-slate-100 overflow-x-auto print:overflow-visible">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 print:bg-slate-100">
                        <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Bahagian</th>
                        <th class="p-4 text-[10px] font-black text-blue-500 uppercase tracking-widest border-b border-slate-100 text-center">Idea Inovasi</th>
                        <th class="p-4 text-[10px] font-black text-orange-500 uppercase tracking-widest border-b border-slate-100 text-center">Selain Inovasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($barChartData as $row)
                        <tr class="hover:bg-slate-50/50 transition-colors print:break-inside-avoid">
                            <td class="p-4">
                                <span class="text-sm font-bold text-slate-700 uppercase tracking-tight">{{ $row['dept'] }}</span>
                            </td>
                            <td class="p-4 text-center">
                                <span class="inline-flex items-center justify-center min-w-[32px] h-8 bg-blue-50 text-blue-600 rounded-lg text-sm font-black print:bg-transparent">
                                    {{ $row['inovasi'] }}
                                </span>
                            </td>
                            <td class="p-4 text-center">
                                <span class="inline-flex items-center justify-center min-w-[32px] h-8 bg-orange-50 text-orange-600 rounded-lg text-sm font-black print:bg-transparent">
                                    {{ $row['selain_inovasi'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="p-12 text-center">
                                <div class="flex flex-col items-center">
                                    <span class="text-slate-300 mb-2">Tiada data dijumpai untuk tempoh ini</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($barChartData) > 0)
                <tfoot class="bg-slate-50/80 font-black print:bg-slate-100">
                    <tr class="print:break-inside-avoid">
                        <td class="p-4 text-[10px] uppercase text-slate-500">Jumlah Keseluruhan</td>
                        <td class="p-4 text-center text-blue-600">{{ collect($barChartData)->sum('inovasi') }}</td>
                        <td class="p-4 text-center text-orange-600">{{ collect($barChartData)->sum('selain_inovasi') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Script Bar Chart --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('groupedBarChart');
            if (!ctx) return;

            const rawData = @json($barChartData);

            if (Chart.getChart(ctx)) {
                Chart.getChart(ctx).destroy();
            }

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: rawData.map(item => item.dept),
                    datasets: [
                        {
                            label: 'Inovasi',
                            data: rawData.map(item => item.inovasi),
                            backgroundColor: '#3b82f6',
                            borderRadius: 8,
                            barPercentage: 0.8,
                            categoryPercentage: 0.6
                        },
                        {
                            label: 'Selain Inovasi',
                            data: rawData.map(item => item.selain_inovasi),
                            backgroundColor: '#f97316',
                            borderRadius: 8,
                            barPercentage: 0.8,
                            categoryPercentage: 0.6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                font: { family: 'sans-serif', size: 11, weight: '700' }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 12,
                            cornerRadius: 12
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: { stepSize: 1 }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        });
    </script>

    @if($reportData->isEmpty())
        <div class="py-20 flex flex-col items-center justify-center opacity-30">
            <div class="text-6xl mb-4">📊</div>
            <p class="font-black uppercase tracking-widest text-xs">Tiada data untuk dianalisis</p>
        </div>
    @endif
</div>
