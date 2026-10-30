<?php

use App\Models\Program;
use App\Models\QuizSubmission;
use App\Models\Winner;
use function Livewire\Volt\{layout, state, with, usesPagination};

layout('layouts.app');
usesPagination();

state([
    'program' => fn (Program $program) => $program,
    'selectedQuiz' => null,
    'showModal' => false,
    'filterDept' => '',

    // Announcement Modal States
    'showAnnounceModal' => false,
    'selectedWinners' => [], // Map: [submission_id => 'Juara' / 'Naib Juara' / etc]
]);

with([
    'hasAnnouncedWinners' => fn() => Winner::whereHas('submission', function($q) {
        $q->where('program_id', $this->program->id);
    })->exists(),

    'quizSubmissions' => function() {
        return QuizSubmission::whereHas('submission', function($q) {
                $q->where('program_id', $this->program->id);
            })
            ->with([
                'submission.user.department'
            ])
            ->when($this->filterDept, function($q) {
                $q->whereHas('submission.user', function($userQuery) {
                    $userQuery->where('department_id', $this->filterDept);
                });
            })
            ->orderBy('score', 'desc')
            ->orderBy('time_taken', 'asc')
            ->paginate(15);
    },
    'departments' => fn() => \App\Models\Department::where('status', 'aktif')->orderBy('name')->get(),
]);

$viewDetails = function ($id) {
    $this->selectedQuiz = QuizSubmission::with([
        'submission.user.department'
    ])->find($id);

    $this->showModal = true;
};

// Buka dan auto-pilih top 3 pemenang mengikut submission->program_id
$openAnnounceModal = function () {
    $this->selectedWinners = [];

    // 1. Semak sekiranya pemenang program ini sudah disimpan di database
    $existingWinners = Winner::whereHas('submission', function($q) {
        $q->where('program_id', $this->program->id);
    })->get();

    if ($existingWinners->isNotEmpty()) {
        foreach ($existingWinners as $winner) {
            $this->selectedWinners[$winner->submission_id] = $winner->label;
        }
    } else {
        // 2. Auto-ranking: Cari QuizSubmission melalui submission->program_id (Markah tertinggi, masa terpantas)
        $topQuizSubmissions = QuizSubmission::whereHas('submission', function($q) {
                $q->where('program_id', $this->program->id);
            })
            ->orderBy('score', 'desc')
            ->orderBy('time_taken', 'asc')
            ->take(3)
            ->get();

        $defaultLabels = ['Juara', 'Naib Juara', 'Tempat Ketiga'];

        foreach ($topQuizSubmissions as $index => $qSub) {
            if (isset($defaultLabels[$index]) && $qSub->submission_id) {
                $this->selectedWinners[$qSub->submission_id] = $defaultLabels[$index];
            }
        }
    }

    $this->showAnnounceModal = true;
};

$toggleWinner = function ($submissionId, $defaultLabel = 'Juara') {
    if (isset($this->selectedWinners[$submissionId])) {
        unset($this->selectedWinners[$submissionId]);
    } else {
        $this->selectedWinners[$submissionId] = $defaultLabel;
    }
};

$updateWinnerLabel = function ($submissionId, $label) {
    if (isset($this->selectedWinners[$submissionId])) {
        $this->selectedWinners[$submissionId] = $label;
    }
};

$saveWinners = function () {
    if (empty($this->selectedWinners)) {
        $this->addError('winners_selection', 'Sila pilih sekurang-kurangnya seorang pemenang.');
        return;
    }

    $positionMap = [
        'Juara' => 1,
        'Naib Juara' => 2,
        'Tempat Ketiga' => 3,
        'Saguhati' => 4,
    ];

    // DB Transaction ensures no duplicates and safe overwriting
    \Illuminate\Support\Facades\DB::transaction(function () use ($positionMap) {

        // 1. Delete all existing winners tied to this program's submissions first
        Winner::whereHas('submission', function ($q) {
            $q->where('program_id', $this->program->id);
        })->delete();

        // 2. Insert the fresh or updated winner list
        foreach ($this->selectedWinners as $submissionId => $label) {
            Winner::create([
                'submission_id' => $submissionId,
                'label'         => $label,
                'position'      => $positionMap[$label] ?? 5,
            ]);
        }
    });

    $this->showAnnounceModal = false;
    session()->flash('success', 'Keputusan pemenang kuiz berjaya dikemaskini!');
};
?>

<div>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        {{-- Flash Success Alert --}}
        @if (session()->has('success'))
            <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center justify-between">
                <span>🎉 {{ session('success') }}</span>
                <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">✕</button>
            </div>
        @endif

        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-3xl font-black text-gray-900 mt-2">{{ $program->title }}</h2>
                <p class="text-gray-500 font-medium">Senarai Keputusan Kuiz & Kedudukan Peserta.</p>
            </div>

            <div class="flex items-center gap-3">
                <div class="bg-blue-600 px-6 py-3 rounded-2xl text-white shadow-lg shadow-blue-100 text-center">
                    <p class="text-[10px] font-bold uppercase opacity-80 tracking-tighter">Jumlah Penyertaan Kuiz</p>
                    <p class="text-2xl font-black">{{ $quizSubmissions->total() }}</p>
                </div>
            </div>
        </div>

        {{-- Filter Bar --}}
        <div class="mb-6 bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="w-full sm:w-72">
                <select wire:model.live="filterDept" class="w-full rounded-xl border-gray-200 bg-gray-50 text-xs font-bold text-gray-700 focus:border-blue-500 focus:ring-blue-500 p-3">
                    <option value="">-- Semua Bahagian --</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            @if($filterDept)
                <button wire:click="$set('filterDept', '')" class="text-xs font-bold text-blue-600 hover:underline">
                    Set Semula Tapis
                </button>
            @endif
        </div>

        <div class="mb-6 flex items-center justify-between">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('admin.dashboard') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold rounded-xl transition duration-200 group">
                <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali
            </a>

            <button wire:click="openAnnounceModal"
                    type="button"
                    class="inline-flex items-center gap-2 px-5 py-2.5 {{ $hasAnnouncedWinners ? 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-200' : 'bg-amber-500 hover:bg-amber-600 shadow-amber-200' }} text-white text-xs font-bold rounded-2xl shadow-md transition duration-200">

                @if($hasAnnouncedWinners)
                    {{-- Ikon Pensel / Kemaskini --}}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Kemaskini Pemenang
                @else
                    {{-- Ikon Piala / Pengumuman Baru --}}
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 4h-3V3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v1H5a2 2 0 0 0-2 2v2a4 4 0 0 0 4 4h.26A7.002 7.002 0 0 0 11 17.92V19H8a1 1 0 0 0 0 2h8a1 1 0 0 0 0-2h-3v-1.08A7.002 7.002 0 0 0 16.74 12H17a4 4 0 0 0 4-4V6a2 2 0 0 0-2-2zM5 8V6h2v4.17A2 2 0 0 1 5 8zm14 0a2 2 0 0 1-2 2.17V6h2v2z"/>
                    </svg>
                    Umumkan Pemenang
                @endif
            </button>
        </div>

        {{-- Jadual Utama Senarai Peserta --}}
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-[2.5rem] border border-gray-100">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400">Peserta</th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400">Bahagian</th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400 text-center">Jumlah Soalan</th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400 text-center">Betul</th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400 text-center">Markah</th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400 text-center">Masa Diambil</th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400 text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($quizSubmissions as $sub)
                            @php
                                $user = $sub->submission->user ?? null;
                                $isTop3 = $loop->index < 3 && $quizSubmissions->currentPage() === 1;

                                $rowClass = '';
                                $badge = '';

                                if ($quizSubmissions->currentPage() === 1) {
                                    if ($loop->index === 0) {
                                        $rowClass = 'bg-amber-50/60 hover:bg-amber-50 border-l-4 border-amber-500';
                                        $badge = '🥇 ';
                                    } elseif ($loop->index === 1) {
                                        $rowClass = 'bg-slate-50/80 hover:bg-slate-100 border-l-4 border-slate-400';
                                        $badge = '🥈 ';
                                    } elseif ($loop->index === 2) {
                                        $rowClass = 'bg-orange-50/60 hover:bg-orange-100 border-l-4 border-orange-400';
                                        $badge = '🥉 ';
                                    } else {
                                        $rowClass = 'hover:bg-gray-50/50';
                                    }
                                } else {
                                    $rowClass = 'hover:bg-gray-50/50';
                                }
                            @endphp

                            <tr class="{{ $rowClass }} transition-colors">
                                <td class="px-6 py-6">
                                    <div class="flex items-center gap-3">
                                        @if($badge)
                                            <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded bg-white shadow-sm border border-gray-100 text-gray-700">
                                                {{ $badge }}
                                            </span>
                                        @endif
                                        <div class="h-10 w-10 rounded-full {{ $isTop3 ? 'bg-white shadow-sm ring-1 ring-black/5' : 'bg-blue-100' }} flex items-center justify-center text-blue-700 font-bold text-xs uppercase flex-shrink-0">
                                            {{ substr($user->name ?? 'U', 0, 2) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900 leading-none">{{ $user->name ?? 'N/A' }}</p>
                                            <p class="text-xs text-gray-400 mt-1">{{ $user->email ?? '' }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-6">
                                    <p class="text-xs text-gray-600 font-bold">
                                        {{ $user->department->name ?? 'N/A' }}
                                    </p>
                                </td>

                                <td class="px-6 py-6 text-center">
                                    <p class="font-bold text-gray-900 text-sm">{{ $sub->total_questions }}</p>
                                </td>

                                <td class="px-6 py-6 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-50 text-green-700 border border-green-100">
                                        {{ $sub->correct_answers }}
                                    </span>
                                </td>

                                <td class="px-6 py-6 text-center">
                                    <p class="font-black {{ $isTop3 ? 'text-amber-600 text-base' : 'text-blue-600 text-sm' }}">{{ $sub->score }}%</p>
                                </td>

                                <td class="px-6 py-6 text-center text-xs text-gray-500 font-mono">
                                    @if(is_numeric($sub->time_taken))
                                        {{ floor($sub->time_taken / 60) > 0 ? floor($sub->time_taken / 60) . 'm ' : '' }}{{ $sub->time_taken % 60 }}s
                                    @else
                                        {{ $sub->time_taken }}
                                    @endif
                                </td>

                                <td class="px-6 py-6 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="viewDetails({{ $sub->id }})"
                                            title="Lihat Perincian"
                                            class="p-2 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white transition-all shadow-sm">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-20 text-center">
                                    <p class="text-gray-400 font-bold uppercase tracking-widest text-sm">Tiada penyertaan kuiz ditemui</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-10">
            {{ $quizSubmissions->links() }}
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL 1: ANNOUNCE WINNERS SELECTION & CONFIRMATION MODAL                  --}}
    {{-- ========================================================================= --}}
    <div x-data="{ open: @entangle('showAnnounceModal') }"
         x-show="open"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">

        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-900/75 backdrop-blur-xs" x-on:click="open = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-[2.5rem] shadow-2xl sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-8 border border-gray-100">

                {{-- Header --}}
                <div class="flex justify-between items-start mb-6 pb-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-amber-100 text-amber-700 rounded-2xl">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19 4h-3V3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v1H5a2 2 0 0 0-2 2v2a4 4 0 0 0 4 4h.26A7.002 7.002 0 0 0 11 17.92V19H8a1 1 0 0 0 0 2h8a1 1 0 0 0 0-2h-3v-1.08A7.002 7.002 0 0 0 16.74 12H17a4 4 0 0 0 4-4V6a2 2 0 0 0-2-2zM5 8V6h2v4.17A2 2 0 0 1 5 8zm14 0a2 2 0 0 1-2 2.17V6h2v2z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="px-2.5 py-0.5 bg-amber-100 text-amber-800 text-[10px] font-black uppercase rounded-full">
                                Pengesahan Keputusan
                            </span>
                            <h3 class="text-xl font-black text-gray-900 mt-1">Umumkan Pemenang Kuiz</h3>
                            <p class="text-xs text-gray-400 font-medium">3 pemenang teratas telah dipilih secara automatik mengikut markah dan masa terpantas.</p>
                        </div>
                    </div>
                    <button x-on:click="open = false" class="text-gray-400 hover:text-gray-600 p-2 rounded-xl bg-gray-50">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                @error('winners_selection')
                    <div class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-semibold">
                        {{ $message }}
                    </div>
                @enderror

                {{-- Dapatkan senarai semua peserta melalui submission->program_id --}}
                @php
                    $allRanked = \App\Models\QuizSubmission::whereHas('submission', function($q) use ($program) {
                            $q->where('program_id', $program->id);
                        })
                        ->with(['submission.user.department'])
                        ->orderBy('score', 'desc')
                        ->orderBy('time_taken', 'asc')
                        ->get();
                @endphp

                <div class="border border-gray-100 rounded-2xl overflow-hidden max-h-[50vh] overflow-y-auto shadow-xs">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 sticky top-0 border-b border-gray-100 text-gray-400 font-black uppercase text-[10px]">
                            <tr>
                                <th class="p-3 text-center w-12">Pilih</th>
                                <th class="p-3 text-center w-12">Ked.</th>
                                <th class="p-3">Peserta</th>
                                <th class="p-3 text-center">Markah</th>
                                <th class="p-3 text-center">Masa</th>
                                <th class="p-3 text-center w-40">Label Anugerah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 font-medium">
                            @forelse($allRanked as $idx => $rItem)
                                @php
                                    $subId = $rItem->submission_id;
                                    $isPicked = isset($selectedWinners[$subId]);
                                    $u = $rItem->submission->user ?? null;
                                @endphp
                                <tr class="hover:bg-amber-50/30 transition-colors {{ $isPicked ? 'bg-amber-50/60' : '' }}">
                                    <td class="p-3 text-center">
                                        <input type="checkbox"
                                               wire:click="toggleWinner({{ $subId }})"
                                               {{ $isPicked ? 'checked' : '' }}
                                               class="w-4 h-4 text-amber-500 rounded border-gray-300 focus:ring-amber-400 cursor-pointer">
                                    </td>
                                    <td class="p-3 text-center font-black text-gray-400">
                                        #{{ $idx + 1 }}
                                    </td>
                                    <td class="p-3">
                                        <div class="font-bold text-gray-900">{{ $u->name ?? 'N/A' }}</div>
                                        <div class="text-[10px] text-gray-400">{{ $u->department->name ?? '-' }}</div>
                                    </td>
                                    <td class="p-3 text-center font-black text-amber-600">
                                        {{ $rItem->score }}%
                                    </td>
                                    <td class="p-3 text-center font-mono text-gray-500">
                                        {{ floor($rItem->time_taken / 60) > 0 ? floor($rItem->time_taken / 60) . 'm ' : '' }}{{ $rItem->time_taken % 60 }}s
                                    </td>
                                    <td class="p-3 text-center">
                                        @if($isPicked)
                                            <select wire:change="updateWinnerLabel({{ $subId }}, $event.target.value)"
                                                    class="py-1 px-2.5 rounded-lg border border-amber-300 bg-white text-gray-800 text-xs font-bold focus:border-amber-500 focus:ring-0">
                                                <option value="Juara" {{ $selectedWinners[$subId] === 'Juara' ? 'selected' : '' }}>🥇 Juara</option>
                                                <option value="Naib Juara" {{ $selectedWinners[$subId] === 'Naib Juara' ? 'selected' : '' }}>🥈 Naib Juara</option>
                                                <option value="Tempat Ketiga" {{ $selectedWinners[$subId] === 'Tempat Ketiga' ? 'selected' : '' }}>🥉 Tempat Ketiga</option>
                                                <option value="Saguhati" {{ $selectedWinners[$subId] === 'Saguhati' ? 'selected' : '' }}>🎖️ Saguhati</option>
                                            </select>
                                        @else
                                            <span class="text-gray-300 text-[11px] italic">Tidak Dipilih</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-gray-400 font-bold">Tiada data penyertaan untuk diumumkan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Footer Controls --}}
                <div class="mt-6 flex items-center justify-between pt-4 border-t border-gray-100">
                    <p class="text-xs text-gray-500 font-medium">
                        Jumlah Pemenang Dipilih: <span class="font-bold text-amber-600">{{ count($selectedWinners) }}</span>
                    </p>
                    <div class="flex items-center gap-2">
                        <button x-on:click="open = false" type="button" class="px-5 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-bold transition">
                            Batal
                        </button>
                        <button wire:click="saveWinners" type="button" class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold shadow-md shadow-amber-200 transition">
                            Sahkan & Umumkan
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL 2: DETAILS INSPECTION MODAL                                         --}}
    {{-- ========================================================================= --}}
    <div x-data="{ open: @entangle('showModal') }"
         x-show="open"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">

        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" x-on:click="open = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-[2.5rem] shadow-xl sm:my-8 sm:align-middle sm:max-w-xl sm:w-full sm:p-8">

                @if($selectedQuiz)
                    @php
                        $mUser = $selectedQuiz->submission->user ?? null;
                    @endphp
                    <div>
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 text-[10px] font-black uppercase rounded-full">
                                    Keputusan Kuiz
                                </span>
                                <h3 class="text-xl font-black text-gray-900 mt-2">Perincian Jawapan Peserta</h3>
                            </div>
                            <button x-on:click="open = false" class="text-gray-400 hover:text-gray-600 p-2 rounded-xl bg-gray-50">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div class="p-4 bg-gray-50 rounded-2xl flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center font-bold text-blue-700 uppercase flex-shrink-0 text-xs">
                                        {{ substr($mUser->name ?? 'U', 0, 2) }}
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black uppercase text-gray-400 tracking-wider">Peserta</p>
                                        <p class="font-bold text-gray-900 text-sm leading-tight">{{ $mUser->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-500">{{ $mUser->email ?? '' }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] font-black uppercase text-gray-400 tracking-wider">Bahagian</p>
                                    <p class="font-bold text-gray-900 text-xs">
                                        {{ $mUser->department->name ?? 'N/A' }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-4 bg-blue-50/50 border border-blue-100 rounded-2xl text-center">
                                    <p class="text-[10px] font-black uppercase text-blue-400 tracking-wider">Markah Keseluruhan</p>
                                    <p class="text-2xl font-black text-blue-700 mt-1">{{ $selectedQuiz->score }}%</p>
                                </div>
                                <div class="p-4 bg-gray-50 border border-gray-100 rounded-2xl text-center">
                                    <p class="text-[10px] font-black uppercase text-gray-400 tracking-wider">Masa Diambil</p>
                                    <p class="text-2xl font-black text-gray-800 mt-1 font-mono">
                                        {{ \Carbon\CarbonInterval::seconds((int) $selectedQuiz->time_taken)->cascade()->forHumans(['short' => true]) }}
                                    </p>
                                </div>
                            </div>

                            <div class="p-4 bg-gray-50 rounded-2xl space-y-2">
                                <div class="flex justify-between text-xs">
                                    <span class="font-bold text-gray-500">Jumlah Soalan:</span>
                                    <span class="font-black text-gray-900">{{ $selectedQuiz->total_questions }} Soalan</span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="font-bold text-gray-500">Jawapan Betul:</span>
                                    <span class="font-black text-green-600">{{ $selectedQuiz->correct_answers }} Soalan</span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="font-bold text-gray-500">Jawapan Salah:</span>
                                    <span class="font-black text-red-500">{{ $selectedQuiz->total_questions - $selectedQuiz->correct_answers }} Soalan</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex gap-3">
                            <button x-on:click="open = false" class="flex-1 py-3.5 bg-gray-100 text-gray-600 hover:bg-gray-200 transition-all rounded-2xl font-bold text-xs">
                                Tutup
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
