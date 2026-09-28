<?php

use App\Models\Program;
use App\Models\QuizSubmission;
use function Livewire\Volt\{layout, state, with, usesPagination};

layout('layouts.app');
usesPagination();

state([
    'program' => fn (Program $program) => $program,
    'selectedQuiz' => null,
    'showModal' => false,
    'filterDept' => '',
]);

with([
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

?>

<div>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-3xl font-black text-gray-900 mt-2">{{ $program->title }}</h2>
                <p class="text-gray-500 font-medium">Senarai Keputusan Kuiz & Kedudukan Peserta.</p>
            </div>

            <div class="flex items-center gap-3">
                <div class="bg-blue-600 px-6 py-3 rounded-2xl text-white shadow-lg shadow-blue-100 text-center">
                    <p class="text-[10px] font-bold uppercase opacity-80 tracking-tighter">Jumlah Penyertaan Kuiz</p>
                    <p class="text-2xl font-black">{{ $quizSubmissions->count() }}</p>
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
        </div>

        {{-- Main Table --}}
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
                                $isTop3 = $loop->index < 3;

                                $rowClass = '';
                                $badge = '';

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

    {{-- Details Inspection Modal --}}
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
