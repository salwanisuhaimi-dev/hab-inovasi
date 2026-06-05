<?php

use App\Models\Submission;
use function Livewire\Volt\{layout, state, usesFileUploads};

layout('layouts.app');

usesFileUploads();

state([
    'submission' => fn (Submission $submission) => $submission,
    'project_title' => fn (Submission $submission) => $submission->project_title,
    'project_description' => fn (Submission $submission) => $submission->project_description,
    'group_name' => fn (Submission $submission) => $submission->group_name,
    'file_upload' => null, 
    'existing_file' => fn (Submission $submission) => $submission->file_path,
]);

$update = function () {
    if ($this->submission->user_id !== auth()->id() || $this->submission->status !== 'pending') {
        abort(403);
    }

    $this->validate([
        'project_title' => 'required|min:5|max:255',
        'project_description' => 'required|min:20',
        'group_name' => 'required',
        'file_upload' => 'nullable|mimes:pdf,doc,docx,zip|max:10240',
    ]);

    $data = [
        'project_title' => $this->project_title,
        'project_description' => $this->project_description,
        'group_name' => $this->group_name,
    ];

    if ($this->file_upload) {
        $data['file_path'] = $this->file_upload->store('submissions', 'public');
    }

    $this->submission->update($data);

    session()->flash('success', 'Penyertaan berjaya dikemaskini!');
    return $this->redirectRoute('user.submissions', navigate: true);
};

?>

<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        
        <div class="mb-8 flex justify-between items-end">
            <div>
                <h2 class="text-3xl font-black text-gray-900 tracking-tight">Kemaskini Projek</h2>
                <p class="text-blue-600 font-bold text-xs uppercase mt-1">{{ $submission->program->title }}</p>
            </div>
            <a href="{{ route('user.submissions') }}" class="text-sm font-bold text-gray-500 hover:text-gray-700">Kembali</a>
        </div>

        <div class="bg-white overflow-hidden shadow-xl sm:rounded-[2rem] border border-gray-100">
            <form wire:submit.prevent="update" class="p-8 md:p-12 space-y-6">
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2 italic">Tajuk Projek</label>
                    <input type="text" wire:model="project_title" 
                        class="w-full rounded-2xl border-gray-200 bg-gray-50 focus:border-blue-500 focus:ring-blue-500 p-4 font-semibold">
                    @error('project_title') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2 italic">Penerangan Ringkas</label>
                    <textarea wire:model="project_description" rows="5" 
                        class="w-full rounded-2xl border-gray-200 bg-gray-50 focus:border-blue-500 focus:ring-blue-500 p-4"></textarea>
                    @error('project_description') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2 italic">Nama Kumpulan</label>
                    <input type="text" wire:model="group_name" 
                        class="w-full rounded-2xl border-gray-200 bg-gray-50 focus:border-blue-500 focus:ring-blue-500 p-4 font-semibold">
                    @error('group_name') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
                </div>

                <div x-data="{ uploading: false, progress: 0 }"
                    x-on:livewire-upload-start="uploading = true"
                    x-on:livewire-upload-finish="uploading = false"
                    x-on:livewire-upload-progress="progress = $event.detail.progress">
                    
                    <label class="block text-sm font-bold text-gray-700 mb-2 italic">Tukar Dokumen (Biarkan kosong jika tiada perubahan)</label>
                    
                    @if($existing_file)
                        <div class="mb-4 p-3 bg-blue-50 rounded-xl flex items-center gap-2 border border-blue-100">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                            <span class="text-xs font-bold text-blue-700 uppercase tracking-tighter">Fail Semasa Tersimpan</span>
                        </div>
                    @endif

                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-200 border-dashed rounded-3xl hover:border-blue-400">
                        <div class="space-y-1 text-center">
                            <div class="flex text-sm text-gray-600">
                                <label class="relative cursor-pointer bg-white rounded-md font-bold text-blue-600">
                                    <span>Muat naik fail baru</span>
                                    <input type="file" wire:model="file_upload" class="sr-only">
                                </label>
                            </div>
                        </div>
                    </div>

                    <div x-show="uploading" class="mt-4">
                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-600" :style="`width: ${progress}%` text-align: center"></div>
                        </div>
                    </div>

                    @if ($file_upload)
                        <div class="mt-4 p-3 bg-green-50 rounded-xl border border-green-100">
                            <span class="text-xs font-bold text-green-700 uppercase italic">Fail Baru Sedia: {{ $file_upload->getClientOriginalName() }}</span>
                        </div>
                    @endif
                </div>

                <div class="pt-6">
                    <button type="submit" wire:loading.attr="disabled"
                        class="w-full flex justify-center py-4 px-6 border border-transparent rounded-2xl shadow-sm text-sm font-black uppercase tracking-widest text-white bg-gray-900 hover:bg-blue-600 transition-all">
                        <span wire:loading.remove>Simpan Perubahan</span>
                        <span wire:loading>Mengemaskini...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>