<?php

use function Livewire\Volt\{state, mount, layout};
use App\Models\CoffeeBreakSession;

layout('layouts.print');

state([
    'session' => null,
    'ideas' => []
]);

mount(function (CoffeeBreakSession $session) {
    $this->session = $session;
    $this->ideas = $session->ideas;
});

?>

<div class="p-10 bg-white text-slate-900 text-sm min-h-screen">

    <style>
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .print-card {
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }
    </style>

    <script src="https://cdn.tailwindcss.com"></script>

    <div class="mb-8 border-b-2 border-slate-900 pb-4">
        <h2 class="text-3xl font-black uppercase tracking-tight text-slate-900">Laporan Idea Coff-B</h2>
        <p class="text-slate-500 font-medium italic mt-1">
            {{ $session->department->name }} | Tempat: {{ $session->location }} | Tarikh Sesi: {{ \Carbon\Carbon::parse($session->date_created)->format('d F Y') }}
        </p>
    </div>

    <div class="space-y-6">
        @forelse($ideas as $index => $idea)
            <div class="print-card border border-slate-300 rounded-xl p-5 bg-slate-50/50">

                <div class="flex justify-between items-start border-b border-slate-200 pb-2 mb-3">
                    <h3 class="font-bold text-base text-slate-900">
                        <span class="text-slate-400 mr-1">#{{ $index + 1 }}</span>
                        {{ $idea->title }}
                    </h3>
                    <div class="flex gap-2">
                        <span class="bg-slate-200 text-slate-800 text-xs font-bold px-2.5 py-1 rounded-md uppercase tracking-wider">
                            {{ str_replace('_', ' ', $idea->category) }}
                        </span>
                        <span class="{{ $idea->is_digital === 'digital' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800' }} text-xs font-bold px-2.5 py-1 rounded-md uppercase tracking-wider">
                            {{ $idea->is_digital === 'digital' ? 'Digital' : 'Bukan Digital' }}
                        </span>
                    </div>
                </div>

                <div class="mb-4 bg-white border-l-4 border-amber-500 p-3 rounded-r-lg">
                    <h4 class="text-xs font-bold text-amber-700 uppercase tracking-wider mb-1">Cadangan / Masalah:</h4>
                    <p class="text-slate-700 italic leading-relaxed">
                        {{ $idea->suggestion ?: 'Tiada cadangan dikemukakan.' }}
                    </p>
                </div>

                <div class="bg-white border-l-4 border-emerald-500 p-3 rounded-r-lg">
                    <h4 class="text-xs font-bold text-emerald-700 uppercase tracking-wider mb-1">Tindakan Penyelesaian:</h4>
                    <p class="text-slate-700 leading-relaxed">
                        {{ $idea->action_taken ?: 'Belum ada tindakan diambil.' }}
                    </p>
                </div>

            </div>
        @empty
            <p class="text-slate-500 italic text-center py-8">Tiada data idea direkodkan untuk sesi ini.</p>
        @endforelse
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</div>
