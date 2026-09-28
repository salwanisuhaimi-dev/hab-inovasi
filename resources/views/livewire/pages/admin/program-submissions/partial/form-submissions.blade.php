<?php

use App\Models\Program;
use App\Models\Submission;
use App\Models\GeneralSubmission;
use function Livewire\Volt\{layout, state, with, usesPagination};
use Illuminate\Support\Facades\Storage;

layout('layouts.app');
usesPagination();

state([
    'program' => fn (Program $program) => $program,
    'selectedGeneral' => null,
    'showModal' => false,
    'filterDept' => '',
]);

with([
    'generalSubmissions' => function() {
        return GeneralSubmission::whereHas('submission', function($q) {
                $q->where('program_id', $this->program->id);
            })
            ->with([
                'submission.program',
                'submission.user.department'
            ])
            ->when($this->filterDept, function($q) {
                $q->whereHas('submission.user', function($userQuery) {
                    $userQuery->where('department_id', $this->filterDept);
                });
            })
            ->latest()
            ->paginate(15);
    },
    'departments' => fn() => \App\Models\Department::where('status', 'aktif')->orderBy('name')->get(),
]);

$updateStatus = function ($submissionId, $status) {
    $submission = Submission::findOrFail($submissionId);
    $submission->update(['status' => $status]);

    session()->flash('success', "Status penyertaan telah dikemaskini ke {$status}.");
};

$viewDetails = function ($id) {
    $this->selectedGeneral = GeneralSubmission::with([
        'submission.program',
        'submission.user.department'
    ])->find($id);

    $this->showModal = true;
};

?>

<div>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        @if (session()->has('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 font-bold rounded-2xl text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-3xl font-black text-gray-900 mt-2">{{ $program->title }}</h2>
                <div class="mt-1 flex items-center gap-2">
                    <span class="text-gray-500 font-medium text-sm">Format Penyertaan:</span>
                    <span class="px-3 py-0.5 bg-blue-100 text-blue-700 text-[10px] font-black uppercase rounded-full">
                      {{ match($program->other_submission_format) {
                          'notes' => 'Jawapan Ringkas',
                          'upload_form' => 'Muat Naik Borang',
                          'external_link' => 'Pautan Luar',
                          default => 'Umum',
                      } }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="bg-blue-600 px-6 py-3 rounded-2xl text-white shadow-lg shadow-blue-100 text-center">
                    <p class="text-[10px] font-bold uppercase opacity-80 tracking-tighter">Jumlah Penyertaan</p>
                    <p class="text-2xl font-black">{{ $generalSubmissions->count() }}</p>
                </div>
            </div>
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

        {{-- Main Table --}}
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-[2.5rem] border border-gray-100">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400">Peserta</th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400">Bahagian</th>

                            {{-- Dynamic Columns Based on Format --}}
                            @if($program->other_submission_format === 'notes')
                                <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400">Jawapan / Catatan</th>
                            @elseif($program->other_submission_format === 'upload_form')
                                <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400 text-center">Dokumen Borang</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400">Catatan Tambahan</th>
                            @elseif($program->other_submission_format === 'external_link')
                                <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400">Pautan Luar Program</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400">Catatan</th>
                            @else
                                <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400">Maklumat Penyerahan</th>
                            @endif

                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400">Status</th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400 text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($generalSubmissions as $item)
                            @php
                                $submission = $item->submission;
                                $user = $submission->user ?? null;
                                $status = $submission->status ?? 'pending';
                                $format = $program->other_submission_format;
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-xs uppercase flex-shrink-0">
                                            {{ substr($user->name ?? 'U', 0, 2) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900 leading-none">{{ $user->name ?? 'N/A' }}</p>
                                            <p class="text-xs text-gray-400 mt-1">{{ $user->email ?? '' }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-6">
                                    <span class="text-xs text-gray-700 font-bold">
                                        {{ $user->department->name ?? 'N/A' }}
                                    </span>
                                </td>

                                {{-- FORMAT: NOTES ONLY --}}
                                @if($format === 'notes')
                                    <td class="px-6 py-6">
                                        <p class="text-xs text-gray-700 line-clamp-2 max-w-md leading-relaxed">
                                            {{ $item->notes ?? '-' }}
                                        </p>
                                    </td>

                                {{-- FORMAT: UPLOAD FORM --}}
                                @elseif($format === 'upload_form')
                                    <td class="px-6 py-6 text-center">
                                        @if($item->file_path)
                                            <a href="{{ Storage::disk('public')->url($item->file_path) }}" target="_blank"
                                               class="inline-flex items-center px-3 py-2 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-xl transition-all group">
                                                <svg class="w-4 h-4 mr-1.5 text-blue-600 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                <span class="text-[10px] font-black uppercase tracking-wider">Muat Turun Borang</span>
                                            </a>
                                        @else
                                            <span class="text-[10px] text-gray-300 italic">Tiada Fail</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-6">
                                        <p class="text-xs text-gray-600 line-clamp-1 italic max-w-xs">
                                            {{ $item->notes ?? '-' }}
                                        </p>
                                    </td>

                                {{-- FORMAT: EXTERNAL LINK --}}
                                @elseif($format === 'external_link')
                                    <td class="px-6 py-6">
                                        @if($program->submission_external_link)
                                            <a href="{{ $program->submission_external_link }}" target="_blank"
                                               class="inline-flex items-center text-xs font-bold text-blue-600 hover:underline">
                                                <span>Buka Pautan Borang</span>
                                                <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                            </a>
                                        @else
                                            <span class="text-xs text-gray-400 italic">Tiada Pautan</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-6">
                                        <p class="text-xs text-gray-600 line-clamp-1 italic max-w-xs">
                                            {{ $item->notes ?? '-' }}
                                        </p>
                                    </td>
                                @endif

                                <td class="px-6 py-6">
                                    @php
                                        $colors = [
                                            'pending' => 'bg-yellow-100 text-yellow-700',
                                            'approved' => 'bg-green-100 text-green-700',
                                            'rejected' => 'bg-red-100 text-red-700',
                                        ][$status] ?? 'bg-gray-100 text-gray-700';
                                    @endphp
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase {{ $colors }}">
                                        {{ $status }}
                                    </span>
                                </td>

                                <td class="px-6 py-6 text-right">
                                    <div class="flex justify-end gap-2">
                                        @if($status === 'pending')
                                            <button wire:click="updateStatus({{ $submission->id }}, 'approved')"
                                                title="Luluskan"
                                                class="p-2 bg-green-50 text-green-600 rounded-xl hover:bg-green-600 hover:text-white transition-all shadow-sm">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            </button>
                                            <button wire:click="updateStatus({{ $submission->id }}, 'rejected')"
                                                title="Tolak"
                                                class="p-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition-all shadow-sm">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        @else
                                            <button wire:click="updateStatus({{ $submission->id }}, 'pending')"
                                                class="text-[10px] font-bold text-gray-400 hover:text-gray-900 underline self-center mr-2">Reset</button>
                                        @endif

                                        <button wire:click="viewDetails({{ $item->id }})"
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
                                <td colspan="6" class="px-6 py-20 text-center">
                                    <p class="text-gray-400 font-bold uppercase tracking-widest text-sm">Tiada penyertaan am ditemui</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-10">
            {{ $generalSubmissions->links() }}
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

                @if($selectedGeneral)
                    @php
                        $mSubmission = $selectedGeneral->submission;
                        $mUser = $mSubmission->user ?? null;
                        $mFormat = $program->other_submission_format;
                    @endphp
                    <div>
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 text-[10px] font-black uppercase rounded-full">
                                    Status: {{ $mSubmission->status ?? 'pending' }}
                                </span>
                                <h3 class="text-xl font-black text-gray-900 mt-2">Perincian Penyerahan</h3>
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

                            {{-- MODAL CONTENT FOR NOTES --}}
                            @if($mFormat === 'notes' && $selectedGeneral->notes)
                                <div class="p-4 bg-blue-50/50 border border-blue-100 rounded-2xl">
                                    <p class="text-[10px] font-black uppercase text-blue-400 tracking-wider mb-1">Jawapan Peserta</p>
                                    <p class="text-sm text-gray-800 leading-relaxed whitespace-pre-line">{{ $selectedGeneral->notes }}</p>
                                </div>
                            @endif

                            {{-- MODAL CONTENT FOR UPLOAD FORM --}}
                            @if($mFormat === 'upload_form')
                                @if($selectedGeneral->file_path)
                                    <div class="p-4 bg-gray-50 rounded-2xl flex items-center justify-between border border-gray-100">
                                        <div>
                                            <p class="text-[10px] font-black uppercase text-gray-400 tracking-wider">Borang Dimuat Naik</p>
                                            <p class="text-xs font-bold text-gray-700 mt-0.5">{{ basename($selectedGeneral->file_path) }}</p>
                                        </div>
                                        <a href="{{ Storage::disk('public')->url($selectedGeneral->file_path) }}" target="_blank"
                                           class="px-4 py-2 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-700 transition">
                                            Buka Fail
                                        </a>
                                    </div>
                                @endif

                                @if($selectedGeneral->notes)
                                    <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                        <p class="text-[10px] font-black uppercase text-gray-400 tracking-wider mb-1">Catatan Tambahan</p>
                                        <p class="text-xs text-gray-700 leading-relaxed whitespace-pre-line">{{ $selectedGeneral->notes }}</p>
                                    </div>
                                @endif
                            @endif

                            {{-- MODAL CONTENT FOR EXTERNAL LINK --}}
                            @if($mFormat === 'external_link')
                                @if($program->submission_external_link)
                                    <div class="p-4 bg-blue-50/50 border border-blue-100 rounded-2xl flex items-center justify-between">
                                        <div>
                                            <p class="text-[10px] font-black uppercase text-blue-400 tracking-wider">Pautan Borang</p>
                                            <p class="text-xs font-bold text-blue-900 mt-0.5">Borang Luar Program</p>
                                        </div>
                                        <a href="{{ $program->submission_external_link }}" target="_blank"
                                           class="px-4 py-2 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-700 transition">
                                            Buka Pautan
                                        </a>
                                    </div>
                                @endif

                                @if($selectedGeneral->notes)
                                    <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                        <p class="text-[10px] font-black uppercase text-gray-400 tracking-wider mb-1">Catatan Peserta</p>
                                        <p class="text-xs text-gray-700 leading-relaxed whitespace-pre-line">{{ $selectedGeneral->notes }}</p>
                                    </div>
                                @endif
                            @endif
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
