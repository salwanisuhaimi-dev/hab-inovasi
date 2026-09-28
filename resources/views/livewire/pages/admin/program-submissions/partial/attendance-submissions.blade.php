<?php

use App\Models\Program;
use App\Models\AttendanceSubmission;
use function Livewire\Volt\{layout, state, with, usesPagination};
use Illuminate\Support\Facades\Storage;

layout('layouts.app');
usesPagination();

state([
    'program' => fn (Program $program) => $program,
    'selectedAttendance' => null,
    'showModal' => false,
    'filterDept' => '',
]);

with([
    'attendances' => function() {
        return AttendanceSubmission::whereHas('submission', function($q) {
                $q->where('program_id', $this->program->id);
            })
            ->with([
                'submission.user',
                'department',
                'targetSubmission' // Points directly to ProjectSubmission
            ])
            ->when($this->filterDept, fn($q) => $q->where('dept_id', $this->filterDept))
            ->latest()
            ->paginate(15);
    },
    'departments' => fn() => \App\Models\Department::where('status', 'aktif')->orderBy('name')->get(),
]);

$updateStatus = function ($attendanceId, $status) {
    $attendance = AttendanceSubmission::findOrFail($attendanceId);
    $attendance->update(['status' => $status]);

    session()->flash('success', "Status kehadiran telah dikemaskini ke {$status}.");
};

$viewDetails = function ($id) {
    $this->selectedAttendance = AttendanceSubmission::with([
        'submission.user',
        'department',
        'targetSubmission'
    ])->find($id);

    $this->showModal = true;
};

$exportExcel = function () {
    return \App\Exports\SubmissionExport::download($this->program);
};

?>

<div class="">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        @if (session()->has('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 font-bold rounded-2xl text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-3xl font-black text-gray-900 mt-2">{{ $program->title }}</h2>
                <p class="text-gray-500 font-medium">Senarai pengesahan & maklum balas kehadiran peserta.</p>
            </div>

            <div class="flex items-center gap-3">
                <div class="bg-purple-600 px-6 py-3 rounded-2xl text-white shadow-lg shadow-purple-100 text-center">
                    <p class="text-[10px] font-bold uppercase opacity-80 tracking-tighter">Jumlah Kehadiran</p>
                    <p class="text-2xl font-black">{{ $attendances->count() }}</p>
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
                <select wire:model.live="filterDept" class="w-full rounded-xl border-gray-200 bg-gray-50 text-xs font-bold text-gray-700 focus:border-purple-500 focus:ring-purple-500 p-3">
                    <option value="">-- Semua Bahagian --</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            @if($filterDept)
                <button wire:click="$set('filterDept', '')" class="text-xs font-bold text-purple-600 hover:underline">
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
                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400">Projek Sasaran</th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400 text-center">Lampiran</th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400">Status</th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400 text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($attendances as $item)
                            @php
                                $user = $item->submission->user ?? null;
                                $targetProject = $item->targetSubmission; // Directly accesses ProjectSubmission
                                $status = $item->status ?? 'pending';
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-700 font-bold text-xs uppercase flex-shrink-0">
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
                                        {{ $targetProject->department->name ?? 'N/A' }}
                                    </span>
                                </td>

                                <td class="px-6 py-6">
                                    @if($targetProject)
                                        <p class="font-bold text-gray-900 leading-tight text-sm">{{ $targetProject->project_title }}</p>
                                        <p class="text-xs text-purple-600 font-semibold mt-0.5">Kumpulan: {{ $targetProject->group_name ?? '-' }}</p>
                                    @else
                                        <span class="text-xs text-gray-400 italic">Tiada Projek Sasaran</span>
                                    @endif
                                </td>

                                <td class="px-6 py-6 text-center">
                                    @if($item->pdf_path)
                                        <a href="{{ Storage::disk('public')->url($item->pdf_path) }}" target="_blank"
                                           class="inline-flex items-center px-3 py-2 bg-purple-50 text-purple-700 hover:bg-purple-100 rounded-xl transition-all group">
                                            <svg class="w-4 h-4 mr-1.5 text-purple-600 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <span class="text-[10px] font-black uppercase tracking-wider">Borang</span>
                                        </a>
                                    @else
                                        <span class="text-[10px] text-gray-300 italic">Tiada Fail</span>
                                    @endif
                                </td>

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
                                            <button wire:click="updateStatus({{ $item->id }}, 'approved')"
                                                title="Sahkan Kehadiran"
                                                class="p-2 bg-green-50 text-green-600 rounded-xl hover:bg-green-600 hover:text-white transition-all shadow-sm">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            </button>
                                            <button wire:click="updateStatus({{ $item->id }}, 'rejected')"
                                                title="Tolak"
                                                class="p-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition-all shadow-sm">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        @else
                                            <button wire:click="updateStatus({{ $item->id }}, 'pending')"
                                                class="text-[10px] font-bold text-gray-400 hover:text-gray-900 underline self-center mr-2">Reset</button>
                                        @endif

                                        <button wire:click="viewDetails({{ $item->id }})"
                                            title="Lihat Perincian"
                                            class="p-2 bg-purple-50 text-purple-600 rounded-xl hover:bg-purple-600 hover:text-white transition-all shadow-sm">
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
                                    <p class="text-gray-400 font-bold uppercase tracking-widest text-sm">Tiada pengesahan kehadiran ditemui</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-10">
            {{ $attendances->links() }}
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

                @if($selectedAttendance)
                    @php
                        $mUser = $selectedAttendance->submission->user ?? null;
                        $mProject = $selectedAttendance->targetSubmission; // Accesses ProjectSubmission model directly
                    @endphp
                    <div>
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <span class="px-3 py-1 bg-purple-100 text-purple-700 text-[10px] font-black uppercase rounded-full">
                                    Status: {{ $selectedAttendance->status ?? 'pending' }}
                                </span>
                                <h3 class="text-xl font-black text-gray-900 mt-2">Perincian Maklum Balas Kehadiran</h3>
                            </div>
                            <button x-on:click="open = false" class="text-gray-400 hover:text-gray-600 p-2 rounded-xl bg-gray-50">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div class="p-4 bg-gray-50 rounded-2xl">
                                <p class="text-[10px] font-black uppercase text-gray-400 tracking-wider">Maklumat Peserta</p>
                                <p class="font-bold text-gray-900 text-base mt-1">{{ $mUser->name ?? 'N/A' }}</p>
                                <p class="text-xs text-gray-500">{{ $mUser->email ?? '' }}</p>
                                <p class="text-xs font-semibold text-purple-600 mt-2">
                                    Bahagian: {{ $targetProject->department->name ?? 'N/A' }}
                                </p>
                            </div>

                            @if($mProject)
                                <div class="p-4 bg-purple-50/50 border border-purple-100 rounded-2xl">
                                    <p class="text-[10px] font-black uppercase text-purple-400 tracking-wider">Projek Sasaran</p>
                                    <p class="font-bold text-purple-950 text-base mt-1">{{ $mProject->project_title }}</p>
                                    <p class="text-xs text-purple-700 mt-1">Kumpulan: {{ $mProject->group_name ?? '-' }}</p>
                                </div>
                            @endif

                            @if($selectedAttendance->pdf_path)
                                <div class="p-4 bg-gray-50 rounded-2xl flex items-center justify-between">
                                    <div>
                                        <p class="text-[10px] font-black uppercase text-gray-400 tracking-wider">Dokumen Kehadiran</p>
                                        <p class="text-xs font-bold text-gray-700 mt-0.5">Fail Borang Terlampir</p>
                                    </div>
                                    <a href="{{ Storage::disk('public')->url($selectedAttendance->pdf_path) }}" target="_blank"
                                       class="px-4 py-2 bg-purple-600 text-white rounded-xl text-xs font-bold hover:bg-purple-700 transition">
                                        Muat Turun Fail
                                    </a>
                                </div>
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
