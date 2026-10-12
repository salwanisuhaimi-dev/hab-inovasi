<?php

use App\Models\Program;
use App\Models\Submission;
use App\Models\Archive;
use App\Models\ArchiveCompetition;

use function Livewire\Volt\{layout, state, with, usesPagination};
use Illuminate\Support\Facades\Storage;
use App\Exports\SubmissionExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Carbon;

layout('layouts.app');
usesPagination();

state([
    'program' => fn (Program $program) => $program,
    'selectedSubmission' => null,
    'showModal' => false,
    'showBulkArchiveModal' => false,
    'juara_id' => '',
    'naib_juara_id' => '',
    'ketiga_id' => '',
    'programYear' => fn () => request()->route('program')
        ? (Carbon::parse(request()->route('program')->start_date)->year ?? Carbon::now()->year)
        : Carbon::now()->year,
]);

with([
    'submissions' => fn() => Submission::where('program_id', $this->program->id)
        ->with(['user', 'projectDetail.department'])
        ->latest()
        ->paginate(15),

    'allSubmissions' => fn() => Submission::where('program_id', $this->program->id)
                ->with(['user', 'projectDetail'])
                ->get(),
     // Check if archive records already exist for this competition & year
     'hasArchivedRecords' => function () {
                 $competitionId = $this->program->competition_id;
                 $year = $this->program->start_date
                     ? \Illuminate\Support\Carbon::parse($this->program->start_date)->year
                     : \Illuminate\Support\Carbon::now()->year;

                 return ArchiveCompetition::where('competition_id', $competitionId)
                     ->where('year', $year)
                     ->exists();
      }
]);



$updateStatus = function ($submissionId, $status) {
    $submission = Submission::findOrFail($submissionId);

    // Updates status directly in the `submissions` table
    $submission->update(['status' => $status]);

    // Refresh selected submission if modal is open for this record
    if ($this->selectedSubmission && $this->selectedSubmission->id === $submissionId) {
        $this->selectedSubmission->refresh();
    }

    session()->flash('success', "Status penyertaan telah dikemaskini.");
};

$viewDetails = function ($id) {
    $this->selectedSubmission = Submission::with(['user', 'projectDetail.department'])->find($id);
    $this->showModal = true;
};

$exportExcel = function () {
    $programTypeName = $this->program->category->name ?? '';

    if (\Illuminate\Support\Str::contains($programTypeName, 'Pertandingan Idea Inovasi', ignoreCase: true)) {
        return \App\Exports\SubmissionExport::downloadIdea($this->program);
    }

    return \App\Exports\SubmissionExport::downloadProject($this->program);
};

$openBulkArchiveModal = function () {
    $this->resetValidation();

    $competitionId = $this->program->competition_id;
    $year = $this->program->start_date
        ? Carbon::parse($this->program->start_date)->year
        : Carbon::now()->year;

    // 1. Reset values first
    $this->juara_id = '';
    $this->naib_juara_id = '';
    $this->ketiga_id = '';

    $allSubmissions = Submission::where('program_id', $this->program->id)
        ->with(['user', 'projectDetail'])
        ->get();

    $archivedRecords = ArchiveCompetition::where('competition_id', $competitionId)
        ->where('year', $year)
        ->with('archive')
        ->get();

    // Temporary variables to hold matched IDs before binding
    $juaraId = '';
    $naibJuaraId = '';
    $ketigaId = '';

    foreach ($archivedRecords as $record) {
        if (!$record->archive) continue;

        $matchedSub = $allSubmissions->first(function ($sub) use ($record) {
            $projectTitle = trim($sub->projectDetail->project_title ?? '');
            $groupName = trim($sub->projectDetail->group_name ?? $sub->user->name ?? '');

            return ($projectTitle !== '' && $projectTitle === trim($record->archive->project_name))
                || ($groupName !== '' && $groupName === trim($record->archive->group_name));
        });

        if ($matchedSub) {
            if ($record->achievement === 'Juara') {
                $juaraId = (string) $matchedSub->id;
            } elseif ($record->achievement === 'Naib Juara') {
                $naibJuaraId = (string) $matchedSub->id;
            } elseif ($record->achievement === 'Tempat Ketiga') {
                $ketigaId = (string) $matchedSub->id;
            }
        }
    }

    // 2. Assign values simultaneously as string types
    $this->juara_id = $juaraId;
    $this->naib_juara_id = $naibJuaraId;
    $this->ketiga_id = $ketigaId;

    $this->showBulkArchiveModal = true;
};

$processBulkArchive = function () {
    $this->validate([
        'juara_id'      => 'required|different:naib_juara_id|different:ketiga_id',
        'naib_juara_id' => 'required|different:juara_id|different:ketiga_id',
        'ketiga_id'     => 'required|different:juara_id|different:naib_juara_id',
    ], [
        'juara_id.required'      => 'Sila pilih Juara.',
        'naib_juara_id.required' => 'Sila pilih Naib Juara.',
        'ketiga_id.required'     => 'Sila pilih Tempat Ketiga.',
        '*.different'            => 'Pemenang yang sama tidak boleh dipilih lebih daripada sekali.',
    ]);

    DB::transaction(function () {
        $submissions = Submission::where('program_id', $this->program->id)
            ->with(['user', 'projectDetail'])
            ->get();

        $competitionId = $this->program->competition_id;
        $year = $this->programYear;

        foreach ($submissions as $sub) {
            // Determine achievement
            $achievement = 'Penyertaan';
            if ((string)$sub->id === (string)$this->juara_id) {
                $achievement = 'Juara';
            } elseif ((string)$sub->id === (string)$this->naib_juara_id) {
                $achievement = 'Naib Juara';
            } elseif ((string)$sub->id === (string)$this->ketiga_id) {
                $achievement = 'Tempat Ketiga';
            }

            $projectName = $sub->projectDetail->project_title ?? 'Tiada Tajuk';
            $groupName = $sub->projectDetail->group_name
                ?? $sub->user->name
                ?? 'N/A';

            // 1. Update or create archive record by project_name & group_name
            $archive = Archive::updateOrCreate(
                [
                    'project_name' => $projectName,
                    'group_name'   => $groupName,
                ],
                [
                    'description'   => $sub->projectDetail->project_description ?? null,
                    'department_id' => $sub->projectDetail->department_id ?? null,
                ]
            );

            // 2. Update or create competition archive record by archive_id & competition_id
            ArchiveCompetition::updateOrCreate(
                [
                    'archive_id'     => $archive->id,
                    'competition_id' => $competitionId,
                ],
                [
                    'achievement'    => $achievement,
                    'year'           => $year,
                ]
            );
        }
    });

    $this->showBulkArchiveModal = false;
    session()->flash('success', "Keputusan pemenang berjaya disimpan.");
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
                <p class="text-2xl font-black">{{ $submissions->total() }}</p>
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

            <div class="flex items-center gap-3">
                 <button wire:click="openBulkArchiveModal"
                      type="button"
                      class="inline-flex items-center gap-2 px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-2xl shadow-md shadow-amber-100 transition duration-200">
                      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                          <path d="M19 4h-3V3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v1H5a2 2 0 0 0-2 2v2a4 4 0 0 0 4 4h.26A7.002 7.002 0 0 0 11 17.92V19H8a1 1 0 0 0 0 2h8a1 1 0 0 0 0-2h-3v-1.08A7.002 7.002 0 0 0 16.74 12H17a4 4 0 0 0 4-4V6a2 2 0 0 0-2-2zM5 8V6h2v4.17A2 2 0 0 1 5 8zm14 0a2 2 0 0 1-2 2.17V6h2v2z"/>
                      </svg>
                      {{ $hasArchivedRecords ? 'Kemaskini Pemenang' : 'Umumkan Pemenang' }}
                  </button>
                  <button wire:click="exportExcel"
                        type="button"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-2xl shadow-md shadow-emerald-100 transition duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Eksport Excel
                  </button>
            </div>
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
                                $status = $sub->status ?? 'pending';
                            @endphp
                            <tr wire:key="submission-row-{{ $sub->id }}" class="hover:bg-gray-50/50 transition-colors">
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
                                        {{ $project->department->name ?? $sub->user->department->code ?? '-' }}
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
                                        {{ $selectedProject->department->name ?? $selectedSubmission->user->department->name ?? '-' }}
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

    <div x-data="{ open: @entangle('showBulkArchiveModal') }"
     x-show="open"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">

    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" x-on:click="open = false"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-[2.5rem] shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-8">
            <div class="text-center mb-6">
                <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 4h-3V3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v1H5a2 2 0 0 0-2 2v2a4 4 0 0 0 4 4h.26A7.002 7.002 0 0 0 11 17.92V19H8a1 1 0 0 0 0 2h8a1 1 0 0 0 0-2h-3v-1.08A7.002 7.002 0 0 0 16.74 12H17a4 4 0 0 0 4-4V6a2 2 0 0 0-2-2zM5 8V6h2v4.17A2 2 0 0 1 5 8zm14 0a2 2 0 0 1-2 2.17V6h2v2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-black text-gray-900">Keputusan Pertandingan</h3>
                <p class="text-xs text-gray-500 mt-1">Pilih pemenang bagi pertandingan ini. Penyertaan lain adalah automatik sebagai <strong>Penyertaan</strong>.</p>
            </div>

            <div class="space-y-4">
                <!-- Juara Field -->
                <div>
                    <label class="block text-xs font-black text-amber-600 uppercase tracking-widest mb-1.5">🥇 Juara <span class="text-red-500">*</span></label>
                    <select wire:model.live="juara_id" class="w-full rounded-2xl border-gray-200 text-sm font-bold text-gray-800 focus:ring-amber-500 focus:border-amber-500">
                        <option value="">-- Pilih Juara --</option>
                        @foreach($allSubmissions as $sub)
                            <option value="{{ $sub->id }}">
                                {{ $sub->projectDetail->project_title ?? 'Tiada Tajuk' }} ({{ $sub->projectDetail->group_name ?? $sub->user->name ?? 'N/A' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Naib Juara Field -->
                <div>
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-1.5">🥈 Naib Juara <span class="text-red-500">*</span></label>
                    <select wire:model.live="naib_juara_id" class="w-full rounded-2xl border-gray-200 text-sm font-bold text-gray-800 focus:ring-amber-500 focus:border-amber-500">
                        <option value="">-- Pilih Naib Juara --</option>
                        @foreach($allSubmissions as $sub)
                            @if((string)$sub->id !== (string)$juara_id)
                                <option value="{{ $sub->id }}">
                                    {{ $sub->projectDetail->project_title ?? 'Tiada Tajuk' }} ({{ $sub->projectDetail->group_name ?? $sub->user->name ?? 'N/A' }})
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <!-- Tempat Ketiga Field -->
                <!-- Tempat Ketiga Field -->
                <div>
                    <label class="block text-xs font-black text-amber-700 uppercase tracking-widest mb-1.5">🥉 Tempat Ketiga <span class="text-red-500">*</span></label>
                    <select wire:model.live="ketiga_id" class="w-full rounded-2xl border-gray-200 text-sm font-bold text-gray-800 focus:ring-amber-500 focus:border-amber-500">
                        <option value="">-- Pilih Tempat Ketiga --</option>
                        @foreach($allSubmissions as $sub)
                            @if((string)$sub->id !== (string)$juara_id && (string)$sub->id !== (string)$naib_juara_id)
                                <option value="{{ $sub->id }}" {{ (string)$sub->id === (string)$ketiga_id ? 'selected' : '' }}>
                                    {{ $sub->projectDetail->project_title ?? 'Tiada Tajuk' }} ({{ $sub->projectDetail->group_name ?? $sub->user->name ?? 'N/A' }})
                                </option>
                            @endif
                        @endforeach
                    </select>
                    @error('ketiga_id') <span class="text-xs text-red-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="mt-8 flex gap-3">
                <button wire:click="processBulkArchive"
                        wire:confirm="Adakah anda pasti? Semua penyertaan akan diarkibkan dan disimpan ke rekod sejarah."
                        class="flex-1 py-3.5 bg-amber-500 hover:bg-amber-600 text-white rounded-2xl font-bold text-sm shadow-md shadow-amber-100 transition-all">
                    Simpan Keputusan & Arkib
                </button>
                <button x-on:click="open = false" class="py-3.5 px-5 bg-gray-100 text-gray-600 hover:bg-gray-200 rounded-2xl font-bold text-sm transition-all">
                    Batal
                </button>
            </div>
        </div>
    </div>
</div>

</div>
