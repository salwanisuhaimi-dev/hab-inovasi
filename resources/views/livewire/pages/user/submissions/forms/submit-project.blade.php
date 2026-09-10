<?php

use App\Models\Program;
use App\Models\Submission;
use App\Models\ProjectSubmission;
use function Livewire\Volt\{layout, state, usesFileUploads, with, mount};
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

layout('layouts.app');

usesFileUploads();

state([
    'program' => fn (Program $program) => $program,
    'department_id' => fn () => auth()->user()->department_id ?? '',
    'project_title' => '',
    'project_description' => '',
    'group_name' => '',
    'total_members' => '',
    'file_upload' => null,
    'submissionId' => null,
    'existing_submission' => null,
    'existing_pdf_path' => null,
]);

mount(function (Program $program, $submission_slug = null, Submission $submission = null) {
    // 1. Resolve submission either via route param or DB query
    if (!$submission || !$submission->exists) {
        $submission = Submission::where('user_id', auth()->id())
            ->where('program_id', $program->id)
            ->first();
    }

    // 2. Hydrate component if submission exists
    if ($submission && $submission->exists) {
        $this->existing_submission = $submission->load('projectDetail');
        $this->submissionId = $submission->id;

        $project = $submission->projectDetail;

        if ($project) {
            $this->department_id = $project->department_id ?? (auth()->user()->department_id ?? '');
            $this->project_title = $project->project_title ?? '';
            $this->project_description = $project->project_description ?? '';
            $this->group_name = $project->group_name ?? '';
            $this->total_members = $project->total_members ?? '';
            $this->existing_pdf_path = $project->file_path ?? null;
        }
    }
});

with([
    'departments' => fn() => \App\Models\Department::where('status', 'aktif')->orderBy('name')->get(),
]);

$submit = function () {
    $this->validate([
        'department_id' => $this->program->category_id == 5 ? 'nullable' : 'required|exists:departments,id',
        'project_title' => 'required|min:5|max:255',
        'project_description' => 'required|min:20',
        'group_name' => $this->program->category_id == 5 ? 'nullable' : 'required|min:3',
        'total_members' => $this->program->category_id == 5 ? 'nullable' : 'required|integer|min:1',
        'file_upload' => 'nullable|mimes:pdf,doc,docx,zip|max:10240',
    ]);

    // Check duplicate submit only when creating a new record
    if (!$this->submissionId) {
        $alreadySubmitted = Submission::where('user_id', auth()->id())
            ->where('program_id', $this->program->id)
            ->exists();

        if ($alreadySubmitted) {
            session()->flash('error', 'Anda telah menghantar penyertaan untuk program ini.');
            return $this->redirectRoute('user.submissions', navigate: true);
        }
    }

    $newFilePath = null;
    if ($this->file_upload) {
        $newFilePath = $this->file_upload->store('submissions', 'public');
    }

    try {
        DB::transaction(function () use ($newFilePath) {
            if ($this->submissionId) {
                // UPDATE EXISTING SUBMISSION
                $submission = Submission::findOrFail($this->submissionId);
                $project = $submission->projectDetail;

                if ($project) {
                    $updatePayload = [
                        'department_id'       => $this->program->category_id == 5 ? null : $this->department_id,
                        'project_title'       => $this->project_title,
                        'project_description' => $this->project_description,
                        'group_name'          => $this->program->category_id == 5 ? null : $this->group_name,
                        'total_members'       => $this->program->category_id == 5 ? null : $this->total_members,
                    ];

                    // Only overwrite and delete the old file if a new file was actually uploaded
                    if ($newFilePath) {
                        if ($project->file_path && Storage::disk('public')->exists($project->file_path)) {
                            Storage::disk('public')->delete($project->file_path);
                        }
                        $updatePayload['file_path'] = $newFilePath;
                    }

                    $project->update($updatePayload);
                } else {
                    ProjectSubmission::create([
                        'submission_id'       => $submission->id,
                        'department_id'       => $this->program->category_id == 5 ? null : $this->department_id,
                        'project_title'       => $this->project_title,
                        'project_description' => $this->project_description,
                        'group_name'          => $this->program->category_id == 5 ? null : $this->group_name,
                        'total_members'       => $this->program->category_id == 5 ? null : $this->total_members,
                        'file_path'           => $newFilePath,
                        'status'              => 'pending',
                    ]);
                }
            } else {
                // CREATE NEW SUBMISSION
                $submission = Submission::create([
                    'program_id' => $this->program->id,
                    'user_id'    => auth()->id(),
                ]);

                ProjectSubmission::create([
                    'submission_id'       => $submission->id,
                    'department_id'       => $this->program->category_id == 5 ? null : $this->department_id,
                    'project_title'       => $this->project_title,
                    'project_description' => $this->project_description,
                    'group_name'          => $this->program->category_id == 5 ? null : $this->group_name,
                    'total_members'       => $this->program->category_id == 5 ? null : $this->total_members,
                    'file_path'           => $newFilePath,
                    'status'              => 'pending',
                ]);
            }
        });

    } catch (\Throwable $e) {
        if ($newFilePath && Storage::disk('public')->exists($newFilePath)) {
            Storage::disk('public')->delete($newFilePath);
        }

        session()->flash('error', 'Gagal menyimpan penyertaan. Sila cuba lagi.');
        return;
    }

    $msg = $this->submissionId ? 'Penyertaan berjaya dikemaskini!' : 'Penyertaan berjaya dihantar!';
    session()->flash('success', $msg);
    return $this->redirectRoute('user.submissions', navigate: true);
};


$downloadDocument = function () {
    /** @var Program $program */
    $program = $this->program;

    $publication = $program->formPublication ?? null;

    if (!$publication || !$publication->pdf_paths) {
        session()->flash('error', 'Dokumen tidak dijumpai.');
        return;
    }

    $paths = $publication->pdf_paths;
    $fileName = is_array($paths) ? ($paths[0] ?? null) : $paths;

    if (!$fileName) {
        session()->flash('error', 'Nama fail tidak sah.');
        return;
    }

    $relativePath = ltrim($fileName, '/');
    if (!Str::startsWith($relativePath, 'publications/')) {
        $relativePath = 'publications/' . $relativePath;
    }

    if (!Storage::disk('public')->exists($relativePath)) {
        session()->flash('error', 'Fail tiada dalam storan.');
        return;
    }

    $extension = pathinfo($relativePath, PATHINFO_EXTENSION) ?: 'pdf';

    $title = $publication->title ?? $program->title ?? 'Unknown';
    $downloadName = Str::slug($title) . '.' . $extension;

    return Storage::disk('public')->download($relativePath, $downloadName);
};

$downloadSecondDocument = function () {
    /** @var Program $program */
    $program = $this->program;

    $secondDoc = $program->publication ?? null;

    if (!$secondDoc || !$secondDoc->pdf_paths) {
        session()->flash('error', 'Dokumen rujukan tidak dijumpai.');
        return;
    }

    $paths = $secondDoc->pdf_paths;
    $fileName = is_array($paths) ? ($paths[0] ?? null) : $paths;

    if (!$fileName) return;

    $relativePath = ltrim($fileName, '/');
    if (!Str::startsWith($relativePath, 'publications/')) {
        $relativePath = 'publications/' . $relativePath;
    }

    if (!Storage::disk('public')->exists($relativePath)) {
        session()->flash('error', 'Fail rujukan tiada dalam storan.');
        return;
    }

    $extension = pathinfo($relativePath, PATHINFO_EXTENSION) ?: 'pdf';
    $title = $secondDoc->title ?? 'dokumen-rujukan';

    return Storage::disk('public')->download($relativePath, Str::slug($title) . '.' . $extension);
};

?>

<div class="py-2">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
      <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
              <h2 class="text-3xl font-black text-gray-900 tracking-tight">
                  {{ $submissionId ? 'Kemaskini Penyertaan' : 'Hantar Penyertaan' }}
              </h2>
              <div class="mt-2 flex items-center gap-2">
                  <span class="px-3 py-1 bg-blue-100 text-blue-700 text-[10px] font-black uppercase rounded-full">
                      {{ $program->title }}
                  </span>
                  @if($submissionId)
                      <span class="px-3 py-1 bg-amber-100 text-amber-700 text-[10px] font-black uppercase rounded-full">
                          Kemaskini
                      </span>
                  @endif
              </div>
          </div>

          <div class="flex flex-col items-start md:items-end">
              @if (session()->has('error'))
                  <div class="mb-2 text-sm text-red-600">
                      {{ session('error') }}
                  </div>
              @endif

              <div class="flex flex-wrap items-center gap-2">
                  @if($program->formPublication && $program->formPublication->pdf_paths)
                      <button
                          wire:click="downloadDocument"
                          type="button"
                          class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-md shadow-sm transition">
                          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                          </svg>
                          Dokumen/ Borang
                      </button>
                  @endif

                  @if(isset($program->publication) && $program->publication?->pdf_paths)
                      <button
                          wire:click="downloadSecondDocument"
                          type="button"
                          class="inline-flex items-center px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white text-sm font-medium rounded-md shadow-sm transition">
                          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                          </svg>
                          Garis Panduan
                      </button>
                  @endif
              </div>
          </div>
      </div>

        <div class="bg-white overflow-hidden shadow-xl sm:rounded-[2rem] border border-gray-100">
            <form wire:submit.prevent="submit" class="p-8 md:p-12 space-y-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2 italic">
                      @if($program->category_id == 5)
                            Tajuk Idea / Cadangan Inovasi
                      @else
                            Tajuk Projek
                      @endif
                    </label>
                    <input type="text" wire:model="project_title"
                        class="w-full rounded-2xl border-gray-200 bg-gray-50 focus:border-blue-500 focus:ring-blue-500 p-4 font-semibold"
                        placeholder="Contoh: Sistem Smart Parking Universiti">
                    @error('project_title') <p class="text-red-500 text-xs mt-2 font-medium">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2 italic">Penerangan Ringkas</label>
                    <textarea wire:model="project_description" rows="5"
                        class="w-full rounded-2xl border-gray-200 bg-gray-50 focus:border-blue-500 focus:ring-blue-500 p-4"
                        placeholder="Terangkan projek anda lebih lanjut..."></textarea>
                    @error('project_description') <p class="text-red-500 text-xs mt-2 font-medium">{{ $message }}</p> @enderror
                </div>

                @if($program->category_id != 5)
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2 italic">Nama Kumpulan</label>
                        <input type="text" wire:model="group_name"
                            class="w-full rounded-2xl border-gray-200 bg-gray-50 focus:border-blue-500 focus:ring-blue-500 p-4 font-semibold"
                            placeholder="Contoh: The Grea8">
                        @error('group_name') <p class="text-red-500 text-xs mt-2 font-medium">{{ $message }}</p> @enderror
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2 italic">Bilangan Ahli Kumpulan</label>
                        <select wire:model="total_members"
                              class="w-full rounded-2xl border-gray-200 bg-gray-50 focus:border-blue-500 focus:ring-blue-500 p-4">
                              <option value="">Pilih Bilangan Ahli (2 - 10 Orang)</option>

                              @foreach(range(2, 10) as $number)
                                    <option value="{{ $number }}">{{ $number }} Orang</option>
                              @endforeach
                        </select>
                        @error('total_members') <p class="text-red-500 text-xs mt-2 font-medium">{{ $message }}</p> @enderror
                   </div>

                    <div class="mt-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2 italic">Bahagian</label>
                        <select wire:model="department_id"
                            class="w-full rounded-2xl border-gray-200 bg-gray-50 focus:border-blue-500 focus:ring-blue-500 p-4">
                            <option value="">-- Pilih Bahagian --</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                    </div>

                    <div
                        x-data="{ uploading: false, progress: 0 }"
                        x-on:livewire-upload-start="uploading = true"
                        x-on:livewire-upload-finish="uploading = false"
                        x-on:livewire-upload-error="uploading = false"
                        x-on:livewire-upload-progress="progress = $event.detail.progress">
                        <label class="block text-sm font-bold text-gray-700 mb-2 italic">Borang/ Dokumen Sokongan</label>

                        @if($existing_pdf_path)
                            <div class="mb-3 p-3 bg-blue-50 rounded-xl flex items-center justify-between border border-blue-100">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <span class="text-xs font-bold text-blue-700">Fail Wujud: {{ basename($existing_pdf_path) }}</span>
                                </div>
                                <a href="{{ Storage::disk('public')->url($existing_pdf_path) }}" target="_blank" class="text-xs text-blue-600 underline font-semibold">Lihat Fail</a>
                            </div>
                        @endif

                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-200 border-dashed rounded-3xl hover:border-blue-400 transition-colors">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600 justify-center">
                                    <label class="relative cursor-pointer bg-white rounded-md font-bold text-blue-600 hover:text-blue-500">
                                        <span>{{ $existing_pdf_path ? 'Tukar fail baharu' : 'Muat naik fail' }}</span>
                                        <input type="file" wire:model="file_upload" class="sr-only">
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500 italic">PDF, DOC, ZIP up to 10MB</p>
                            </div>
                        </div>

                        <div x-show="uploading" class="mt-4">
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-600 transition-all duration-300" :style="`width: ${progress}%`"></div>
                            </div>
                            <p class="text-[10px] text-gray-500 mt-1 font-bold">Uploading: <span x-text="progress"></span>%</p>
                        </div>

                        @if ($file_upload)
                            <div class="mt-4 p-3 bg-green-50 rounded-xl flex items-center gap-2 border border-green-100">
                                <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"></path></svg>
                                <span class="text-xs font-bold text-green-700">Fail Baharu Dipilih: {{ $file_upload->getClientOriginalName() }}</span>
                            </div>
                        @endif
                        @error('file_upload') <p class="text-red-500 text-xs mt-2 font-medium">{{ $message }}</p> @enderror
                   </div>
                @endif

                <div class="pt-6">
                    <button type="submit" wire:loading.attr="disabled"
                        class="w-full flex justify-center py-4 px-6 border border-transparent rounded-2xl shadow-sm text-sm font-black uppercase tracking-widest text-white bg-gray-900 hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all disabled:opacity-50">
                        <span wire:loading.remove>{{ $submissionId ? 'Kemaskini Sekarang' : 'Hantar Sekarang' }}</span>
                        <span wire:loading class="italic">Memproses...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
