<?php

use App\Models\Program;
use App\Models\Submission;
use function Livewire\Volt\{layout, state, with, usesPagination};
use Illuminate\Support\Facades\Storage;
use App\Exports\SubmissionExport;
use Maatwebsite\Excel\Facades\Excel;

layout('layouts.app');
usesPagination();

state([
    'program' => fn (Program $program) => $program,
    'selectedSubmission' => null,
    'showModal' => false,
]);

with([
    'submissions' => fn() => Submission::where('program_id', $this->program->id)
        ->with(['user', 'projectDetail.department'])
        ->latest()
        ->paginate(15)
]);

$updateStatus = function ($submissionId, $status) {
    $submission = Submission::findOrFail($submissionId);

    if ($submission->projectDetail) {
        $submission->projectDetail->update(['status' => $status]);
    }

    session()->flash('success', "Status penyertaan telah dikemaskini ke {$status}.");
};

$viewDetails = function ($id) {
    $this->selectedSubmission = Submission::with(['user', 'projectDetail.department'])->find($id);
    $this->showModal = true;
};

$exportExcel = function () {
    // Ambil nama program type
    $programTypeName = $this->program->category->name ?? '';

    // Semak sama ada "Idea Inovasi" wujud dalam nama program type (tidak sensitif huruf besar/kecil)
    if (\Illuminate\Support\Str::contains($programTypeName, 'Pertandingan Idea Inovasi', ignoreCase: true)) {
        return \App\Exports\SubmissionExport::downloadIdea($this->program);
    }

    return \App\Exports\SubmissionExport::downloadProject($this->program);
};
?>

<div class="py-2">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        @if (session()->has('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 font-bold rounded-2xl">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-3xl font-black text-gray-900 mt-2">{{ $program->title }}</h2>
                <p class="text-gray-500 font-medium">Senarai penyertaan bagi program ini.</p>
            </div>
            <div class="bg-blue-600 px-6 py-3 rounded-2xl text-white shadow-lg shadow-blue-100 text-center">
                <p class="text-[10px] font-bold uppercase opacity-80 tracking-tighter">Jumlah Penyertaan</p>
                <p class="text-2xl font-black">{{ $submissions->count() }}</p>
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

            <button wire:click="exportExcel"
                  type="button"
                  class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-2xl shadow-md shadow-emerald-100 transition duration-200">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                  </svg>
                  Eksport Excel
            </button>
        </div>



        <div class="bg-white overflow-hidden shadow-xl sm:rounded-[2.5rem] border border-gray-100">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400">Peserta / Kumpulan</th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400">Bahagian</th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400">Maklumat Projek</th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400 text-center">Dokumen</th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400">Status</th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400 text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($submissions as $sub)
                            @php
                                $project = $sub->projectDetail;
                                $status = $project->status ?? 'pending';
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-xs uppercase flex-shrink-0">
                                            {{ substr($sub->user->name ?? 'U', 0, 2) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900 leading-none">{{ $sub->user->name ?? 'N/A' }}</p>
                                            <p class="text-xs text-gray-500 mt-1 uppercase font-black tracking-tighter text-blue-600">
                                                {{ $project->group_name ?? '' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-6">
                                    <p class="text-xs text-gray-600 font-bold italic">
                                        {{ $project->department->name ?? $sub->user->department->code }}
                                    </p>
                                </td>

                                <td class="px-6 py-6">
                                    <p class="font-bold text-gray-900 leading-tight">{{ $project->project_title ?? 'Tiada Tajuk' }}</p>
                                    <p class="text-xs text-gray-400 mt-1 line-clamp-1 italic">{{ $project->project_description ?? '-' }}</p>
                                </td>
                                <td class="px-6 py-6 text-center">
                                    @if($project && $project->file_path)
                                        <a href="{{ Storage::disk('public')->url($project->file_path) }}" target="_blank"
                                           class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-all group">
                                            <svg class="w-4 h-4 mr-2 text-blue-600 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            <span class="text-xs font-bold uppercase tracking-widest">Buka Fail</span>
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
                                            <button wire:click="updateStatus({{ $sub->id }}, 'approved')"
                                                title="Luluskan"
                                                class="p-2 bg-green-50 text-green-600 rounded-xl hover:bg-green-600 hover:text-white transition-all shadow-sm">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            </button>
                                            <button wire:click="updateStatus({{ $sub->id }}, 'rejected')"
                                                title="Tolak"
                                                class="p-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition-all shadow-sm">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        @else
                                            <button wire:click="updateStatus({{ $sub->id }}, 'pending')"
                                                class="text-[10px] font-bold text-gray-400 hover:text-gray-900 underline self-center mr-2">Reset</button>
                                        @endif

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
                                <td colspan="6" class="px-6 py-20 text-center">
                                    <p class="text-gray-400 font-bold uppercase tracking-widest">Tiada penyertaan masuk lagi</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-10">
            {{ $submissions->links() }}
        </div>
    </div>

    {{-- Detail View Modal --}}
    <div x-data="{ open: @entangle('showModal') }"
         x-show="open"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">

        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" x-on:click="open = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-[2.5rem] shadow-xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-10">

                @if($selectedSubmission)
                    @php
                        $selectedProject = $selectedSubmission->projectDetail;
                    @endphp
                    <div>
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 text-[10px] font-black uppercase rounded-full">
                                    {{ $selectedProject->status ?? 'pending' }}
                                </span>
                                <h3 class="text-2xl font-black text-gray-900 mt-2">
                                    {{ $selectedProject->project_title ?? 'Tiada Tajuk' }}
                                </h3>
                                <p class="text-sm text-blue-600 font-bold uppercase tracking-tight mt-1">
                                    {{ $selectedProject->group_name ?? 'Individu' }}
                                    @if($selectedProject && $selectedProject->total_members)
                                        ({{ $selectedProject->total_members }} Ahli)
                                    @endif
                                </p>
                            </div>
                            <button x-on:click="open = false" class="text-gray-400 hover:text-gray-600 p-2 rounded-xl bg-gray-50">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <div class="space-y-6">
                            <div class="p-4 bg-gray-50 rounded-2xl flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="h-12 w-12 rounded-full bg-gray-200 flex items-center justify-center font-bold text-gray-600 uppercase flex-shrink-0">
                                        {{ substr($selectedSubmission->user->name ?? 'U', 0, 2) }}
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 font-bold uppercase tracking-widest">Dihantar Oleh</p>
                                        <p class="font-bold text-gray-900">{{ $selectedSubmission->user->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-500">{{ $selectedSubmission->user->email ?? '' }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500 font-bold uppercase tracking-widest">Bahagian</p>
                                    <p class="font-bold text-gray-900 text-sm">
                                        {{ $selectedProject->department->name ?? $selectedSubmission->user->department->name }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2 italic">Penerangan Projek</h4>
                                <div class="text-gray-700 leading-relaxed bg-blue-50/30 p-6 rounded-3xl border border-blue-50 text-sm whitespace-pre-line">
                                    {{ $selectedProject->project_description ?? 'Tiada penerangan.' }}
                                </div>
                            </div>

                            @if($selectedProject && $selectedProject->file_path)
                                <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                                    <span class="text-sm font-bold text-gray-600 italic">Dokumen Sokongan Terlampir</span>
                                    <a href="{{ Storage::disk('public')->url($selectedProject->file_path) }}" target="_blank"
                                       class="px-6 py-3 bg-gray-900 text-white rounded-xl font-bold text-xs hover:bg-blue-600 transition-all">
                                        Lihat Fail (PDF/DOC/ZIP)
                                    </a>
                                </div>
                            @endif
                        </div>

                        <div class="mt-8 flex gap-3">
                            <button x-on:click="open = false" class="flex-1 py-4 bg-gray-100 text-gray-600 hover:bg-gray-200 transition-all rounded-2xl font-bold text-sm">
                                Tutup
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
