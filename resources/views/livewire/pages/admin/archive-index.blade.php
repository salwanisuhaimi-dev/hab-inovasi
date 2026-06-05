<?php

use App\Models\Archive;
use App\Models\Competition;
use function Livewire\Volt\{layout, state, with, usesFileUploads};

layout('layouts.app'); 
usesFileUploads();

state([
    'showModal' => false,
    'editing' => null,
    'department_id' => '',
    'project_name' => '',
    'group_name' => '',
    'description' => '', 
    'track' => '',
    'thumbnail' => '',
    'video_link' => '',
    'selected_comps' => [],
    'showDetailModal' => false,  
    'viewingArchive' => null, 
]);

$edit = function (Archive $archive) {
    $this->editing = $archive->id;
    $this->department_id = $archive->department_id;
    $this->project_name = $archive->project_name;
    $this->group_name = $archive->group_name;
    $this->description = $archive->description;
    $this->track = $archive->track;
    $this->thumbnail = $archive->thumbnail;
    $this->video_link = $archive->video_link;

    $this->selected_comps = []; 
    foreach ($archive->competitions as $comp) {
        $this->selected_comps[$comp->id] = [
            'active' => true,
            'achievement' => $comp->pivot->achievement,
            'year' => $comp->pivot->year,
        ];
    }

    $this->showModal = true;
};

with([
    'archives' => fn() => Archive::with(['department', 'competitions'])->latest()->get(),
    'departments' => fn() => \App\Models\Department::where('status', 'aktif')->orderBy('name')->get(),
    'competitions' => fn() => Competition::where('status', 'aktif')->orderBy('name')->get(),
]);

$save = function () {
    $this->validate([
        'department_id' => 'required',
        'project_name' => 'required',
        'group_name' => 'required',
        'selected_comps' => 'required|array|min:1', 
    ]);

    $payload = [
        'department_id' => $this->department_id,
        'project_name' => $this->project_name,
        'group_name' => $this->group_name,
        'description' => $this->description,
        'track' => $this->track,
        'thumbnail' => $this->thumbnail,
        'video_link' => $this->video_link,
    ];

    if ($this->editing) {
        $archive = Archive::find($this->editing);
        $archive->update($payload);
    } else {
        $archive = Archive::create($payload);
    }

    $pivotData = [];
    foreach ($this->selected_comps as $compId => $data) {
        if (!empty($data['active'])) {
            $pivotData[$compId] = [
                'achievement' => $data['achievement'] ?? null,
                'year' => $data['year'] ?? null,
            ];
        }
    }

    $archive->competitions()->sync($pivotData);

    $this->reset(); 
    $this->showModal = false;
    session()->flash('message', 'Arkib berjaya dikemaskini!');
};

$openCreateModal = function() {
    $this->reset(); 
    $this->selected_comps = [];
    $this->showModal = true;
};

$viewDetails = function ($id) {
    $this->viewingArchive = Archive::with(['department', 'competitions'])->find($id);
    $this->showDetailModal = true;
};

?>

<div class="p-6">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h2 class="text-2xl font-black text-gray-900">Senarai Projek</h2>
            <p class="text-sm text-gray-500">Urus dan pantau semua projek anda di sini.</p>
        </div>
        <button wire:click="openCreateModal" class="flex items-center px-5 py-2.5 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-100">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Tambah Projek
        </button>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-50 text-green-700 rounded-xl border border-green-100 font-bold">
            {{ session('message') }}
        </div>
    @endif

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
<table class="w-full text-left border-collapse">
    <thead class="bg-gray-50/80 backdrop-blur-md sticky top-0 z-10">
        <tr>
            <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Projek</th>
            <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Kumpulan</th>
            <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Pertandingan</th>
            <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-center">Media</th>
            <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-right">Tindakan</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-50">
        @forelse($archives as $archive)
            <tr class="hover:bg-blue-50/30 transition-colors group">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl overflow-hidden border border-gray-100 bg-gray-50 flex-shrink-0 shadow-sm">
                            @if($archive->thumbnail)
                                <img src="{{ asset('storage/' . $archive->thumbnail) }}" 
                                        alt="{{ $archive->project_name }}"
                                        class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-slate-50 text-slate-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-col min-w-0">
                            <div class="font-bold text-gray-900 transition tracking-tight leading-tight">
                                {{ $archive->project_name }}
                            </div>
            
                            @if($archive->description)
                            <p class="text-[11px] text-gray-500 font-medium leading-relaxed mt-1 line-clamp-2">
                                {{ Str::words($archive->description, 20, '...') }}
                            </p>
                            @endif
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-[11px] text-gray-500 font-medium flex items-center mt-1">
                        {{ $archive->group_name }}
                    </div>
                    <span class="text-xs font-bold text-blue-600 bg-blue-50 px-3 py-1 rounded-full">

                        {{ $archive->department->code ?? 'N/A' }}
                    </span>
                </td>

                <td class="px-6 py-4">
                @if($archive->competitions->count() > 0)
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-gray-700 truncate max-w-[120px]">
                            {{ $archive->competitions->first()->name }}
                        </span>
                    @if($archive->competitions->count() > 1)
                        <span class="flex-shrink-0 bg-blue-100 text-blue-600 text-[10px] font-black px-2 py-0.5 rounded-full">
                            +{{ $archive->competitions->count() - 1 }}
                        </span>
                    @endif
                    </div>
                @else
                    <span class="text-gray-300 text-xs italic">Tiada data</span>
                @endif
                    <span class="text-[10px] font-bold bg-amber-50 text-amber-700 px-2 py-0.5 rounded border border-amber-100 uppercase">
                        TREK {{ $archive->track }}
                    </span>
                </td>
                <td class="px-6 py-4 text-center">
                    @if($archive->video_link)
                        <div class="flex flex-col items-center group/link">
                            <a href="{{ $archive->video_link }}" 
                                target="_blank" 
                                class="relative flex items-center justify-center w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all duration-300 shadow-sm"
                                title="Buka Pautan">
                
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.828a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>

                                <span class="absolute -top-8 scale-0 group-hover/link:scale-100 transition-all bg-gray-900 text-white text-[9px] font-black px-2 py-1 rounded shadow-lg uppercase tracking-widest whitespace-nowrap z-20">
                                    Lihat Pautan
                                </span>
                            </a>
                        </div>
                    @else
                        <div class="flex flex-col items-center opacity-30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-tighter mt-1">Tiada</span>
                        </div>
                    @endif
                </td>

                <td class="px-6 py-4 text-right whitespace-nowrap">
                    <button wire:click="viewDetails({{ $archive->id }})" 
                        class="p-2 hover:bg-slate-100 rounded-xl transition text-gray-400 hover:text-slate-900 group/view relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
        
                        <span class="absolute -top-8 left-1/2 -translate-x-1/2 scale-0 group-hover/view:scale-100 transition-all bg-gray-900 text-white text-[9px] font-black px-2 py-1 rounded shadow-lg uppercase tracking-widest whitespace-nowrap z-30">
                            Lihat Detail
                        </span>
                    </button>
                    <button wire:click="edit({{ $archive->id }})" class="p-2 hover:bg-blue-50 rounded-xl transition text-gray-400 hover:text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </button>
                    <button wire:click="confirmDelete({{ $archive->id }})" class="p-2 hover:bg-red-50 rounded-xl transition text-gray-400 hover:text-red-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </td>
            </tr>
        @empty
            @endforelse
    </tbody>
</table>
</div>

    @if($showModal)
        <div class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" wire:click="$set('showModal', false)">                    
                </div>
                <div class="inline-block align-bottom bg-white rounded-[2.5rem] text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full p-10">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-12 h-12 bg-blue-100 rounded-2xl flex items-center justify-center text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </div>
                    <div>
                    <h3 class="text-2xl font-black text-gray-900 tracking-tight">
                        {{ $editing ? 'Kemaskini Arkib' : 'Tambah Arkib Baru' }}
                    </h3>
                    <p class="text-sm text-gray-500 font-medium">Sila isi maklumat projek inovasi di bawah.</p>
                </div>
            </div>
            
            <form wire:submit.prevent="save" class="space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Nama Projek</label>
                    <input type="text" wire:model="project_name" placeholder="Contoh: Sistem AI Pengesanan Hama"
                        class="w-full rounded-2xl border-gray-100 bg-gray-50 focus:border-blue-500 focus:ring-blue-500 p-4 font-bold text-gray-900">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Kumpulan</label>
                        <input type="text" wire:model="group_name" class="w-full rounded-2xl border-gray-100 bg-gray-50 p-4">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Bahagian / Jabatan</label>
                        <select wire:model="department_id" class="w-full rounded-2xl border-gray-100 bg-gray-50 p-4 font-bold text-gray-700">
                            <option value="">Pilih Bahagian</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Trek</label>
                    <input type="text" wire:model="track" placeholder="cth: Keselamatan"
                        class="w-full rounded-2xl border-gray-100 bg-gray-50 focus:border-blue-500 focus:ring-blue-500 p-4 font-bold text-gray-900">
                </div>

                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase mb-1">Penerangan Projek</label>
                        <textarea wire:model="description" rows="5" 
                            class="w-full rounded-2xl border-gray-200 bg-gray-50 focus:border-blue-500 focus:ring-blue-500 p-4">
                        </textarea>
                </div>

                <div class="space-y-4">
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">Senarai Pertandingan & Pencapaian</label>
                    <div class="space-y-3 bg-gray-50 p-4 rounded-[2rem] border border-gray-100 max-h-[300px] overflow-y-auto">
                    @foreach($competitions as $comp)
                        <div class="flex flex-col gap-3 p-4 bg-white rounded-2xl border border-gray-100 shadow-sm">
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" wire:model="selected_comps.{{ $comp->id }}.active" class="rounded text-blue-600">
                                <span class="font-bold text-gray-900 text-sm">{{ $comp->name }}</span>
                            </label>

                            <div x-show="$wire.selected_comps[{{ $comp->id }}]?.active" 
                                class="grid grid-cols-2 gap-3 pl-7">
                                <input type="text" 
                                        wire:model="selected_comps.{{ $comp->id }}.achievement" 
                                        placeholder="Pencapaian (cth: Juara)"
                                        class="text-xs p-2 rounded-lg border-gray-100 bg-gray-50 focus:ring-blue-500">
                    
                                <input type="number" 
                                        wire:model="selected_comps.{{ $comp->id }}.year" 
                                        placeholder="Tahun"
                                        class="text-xs p-2 rounded-lg border-gray-100 bg-gray-50 focus:ring-blue-500">
                            </div>
                        </div>
                    @endforeach
                    </div>
                </div>

                <div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Link Video (URL)</label>
                        <input type="url" wire:model="video_link" placeholder="https://youtube.com/..."
                            class="w-full rounded-2xl border-gray-100 bg-gray-50 p-4">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Thumbnail Projek (Path/Upload)</label>
                    <input type="text" wire:model="thumbnail" placeholder="folder/imej.jpg"
                        class="w-full rounded-2xl border-gray-100 bg-gray-50 p-4">
                </div>

                <!--<div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Penerangan Projek</label>
                    <textarea wire:model="description" rows="4" 
                        class="w-full rounded-2xl border-gray-100 bg-gray-50 focus:border-blue-500 focus:ring-blue-500 p-4"></textarea>
                </div>-->
                <div class="pt-4 flex gap-3">
                    <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition">
                         {{ $editing ? 'Simpan Perubahan' : 'Simpan Projek' }}                            
                    </button>
                    <button type="button" wire:click="$set('showModal', false)" class="flex-1 bg-gray-100 text-gray-600 py-3 rounded-xl font-bold hover:bg-gray-200 transition">Batal</button>
                </div>
            </form>
        </div>
    @endif

@if($showDetailModal && $viewingArchive)
<div class="fixed inset-0 z-[150] overflow-y-auto" x-transition>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity" wire:click="$set('showDetailModal', false)"></div>
        <div class="relative bg-white rounded-[3rem] shadow-2xl max-w-2xl w-full overflow-hidden transform transition-all p-0">
            <div class="relative h-48 bg-slate-100">
                @if($viewingArchive->thumbnail)
                    <img src="{{ asset('storage/' . $viewingArchive->thumbnail) }}" class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-50 text-blue-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-20 h-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                @endif
                
                <button wire:click="$set('showDetailModal', false)" class="absolute top-6 right-6 p-2 bg-white/20 backdrop-blur-md text-white rounded-full hover:bg-white hover:text-slate-900 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <div class="p-10 -mt-12 relative bg-white rounded-t-[3rem]">
                <div class="mb-8">
                    <span class="inline-block px-4 py-1.5 rounded-full bg-blue-50 text-blue-600 text-[10px] font-black uppercase tracking-widest mb-4">
                        {{ $viewingArchive->department->name ?? 'Tiada Bahagian' }}
                    </span>
                    <h2 class="text-3xl font-black text-slate-900 leading-tight">
                        {{ $viewingArchive->project_name }}
                    </h2>
                    <span class="text-[10px] font-bold bg-amber-50 text-amber-700 px-2 py-0.5 rounded border border-amber-100 uppercase">
                        TREK {{ $archive->track }}
                    </span>
                </div>

                <div class="mb-10 text-slate-600 leading-relaxed">
                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 text-left">Mengenai Projek</h4>
                    <p class="text-sm bg-slate-50 p-6 rounded-3xl border border-slate-100">
                        {{ $viewingArchive->description ?? 'Tiada penerangan disediakan untuk projek ini.' }}
                    </p>
                </div>

                <div class="mb-10 text-left">
                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Sejarah Pencapaian</h4>
                    <div class="grid grid-cols-1 gap-3">
                        @forelse($viewingArchive->competitions as $comp)
                            <div class="flex items-center justify-between p-5 bg-white border border-slate-100 rounded-2xl shadow-sm">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center text-amber-500">
                                        🏆
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 text-sm">{{ $comp->name }}</div>
                                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">{{ $comp->pivot->year }}</div>
                                    </div>
                                </div>
                                <div class="bg-amber-50 text-amber-700 px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest border border-amber-100">
                                    {{ $comp->pivot->achievement }}
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-6 text-slate-300 italic text-sm">Tiada rekod pertandingan ditemui.</div>
                        @endforelse
                    </div>
                </div>

                @if($viewingArchive->video_link)
                    <div class="flex items-center justify-between pt-6 border-t border-slate-50">
                        <span class="text-xs font-bold text-slate-400">Pautan Bahan:</span>
                        <a href="{{ $viewingArchive->video_link }}" target="_blank" 
                           class="flex items-center gap-2 px-6 py-3 bg-slate-900 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-blue-600 transition shadow-lg shadow-slate-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            Buka Lampiran
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

</div>
