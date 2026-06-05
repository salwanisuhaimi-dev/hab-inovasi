<?php

use App\Models\Competition;
use function Livewire\Volt\{layout, state, with, usesFileUploads};

layout('layouts.app'); 
usesFileUploads();

state([
    'showModal' => false,
    'editing' => null,
    'name' => '',
    'slug' => '',
    'description' => '',
    'introduction' => '',
    'cycle' => 1,

    'main_objective' => '',
    'sub_objectives' => [['icon' => '💡', 'title' => '', 'desc' => '']],
    'prizes' => ['johan' => 0, 'naib_johan' => 0, 'ketiga' => 0],
    'requirements' => [['title' => '', 'desc' => '', 'is_allowed' => true]],
    'tracks' => [''], 
    'categories' => [''],
    'emojis' => ['💡', '🤝', '🎯', '🚀', '📝', '🏆', '✨', '🔍', '⚙️', '📊'],
]);

$edit = function (Competition $competition) {
    $this->editing = $competition->id;
    $this->showModal = true;

    $this->name = $competition->name;
    $this->slug = $competition->slug;
    $this->description = $competition->description;
    $this->introduction = $competition->introduction;
    $this->cycle = $competition->cycle;
    $this->status = $competition->status;

    $this->main_objective = $competition->objectives['main'] ?? '';
    $this->sub_objectives = $competition->objectives['items'] ?? [['icon' => '💡', 'title' => '', 'desc' => '']];
    
    $this->prizes = $competition->prizes ?? ['johan' => 0, 'naib_johan' => 0, 'ketiga' => 0];
    $this->requirements = $competition->requirements ?? [['title' => '', 'desc' => '', 'is_allowed' => true]];
    $this->tracks = $competition->tracks ?? [''];
    $this->categories = $competition->categories ?? [''];

};

with([
    'competitions' => fn() => Competition::latest()->get(),
]);

$addObjective = fn() => $this->sub_objectives[] = ['icon' => '💡', 'title' => '', 'desc' => ''];
$removeObjective = fn($index) => array_splice($this->sub_objectives, $index, 1);

$addRequirement = fn() => $this->requirements[] = ['title' => '', 'desc' => '', 'is_allowed' => true];
$removeRequirement = fn($index) => array_splice($this->requirements, $index, 1);

$addTrack = fn() => $this->tracks[] = '';
$removeTrack = fn($index) => array_splice($this->tracks, $index, 1);

$addCategory = fn() => $this->categories[] = '';
$removeCategory = fn($index) => array_splice($this->categories, $index, 1);


$save = function () {
    $this->validate([
        'name' => 'required|string|max:255',
        'cycle' => 'required|integer',
        'main_objective' => 'required',
    ]);

    $payload = [
        'name'          => $this->name,
        'slug'          => $this->slug ?: str($this->name)->slug(),
        'description'   => $this->description,
        'introduction'  => $this->introduction,
        'cycle'         => $this->cycle,
        
        'objectives'    => [
            'main'  => $this->main_objective,
            'items' => $this->sub_objectives
        ],
        'requirements'  => $this->requirements,
        'prizes'        => $this->prizes,
        'tracks'        => $this->tracks,
        'categories'    => $this->categories ?? [], // Jika ada
    ];

    if ($this->editing) {
        Competition::find($this->editing)->update($payload);
        session()->flash('message', 'Pertandingan berjaya dikemaskini!');
    } else {
        Competition::create($payload);
        session()->flash('message', 'Pertandingan berjaya disimpan!');
    }

    $this->reset(); 
    $this->showModal = false;
};

$openCreateModal = function() {
    $this->reset([
        'editing', 'name', 'slug', 'description', 'introduction', 
        'cycle', 'main_objective', 'sub_objectives', 'prizes', 
        'requirements', 'tracks', 'categories',
    ]);
    
    // Set default value balik selepas reset
    $this->sub_objectives = [['icon' => '💡', 'title' => '', 'desc' => '']];
    $this->requirements = [['title' => '', 'desc' => '', 'is_allowed' => true]];
    $this->tracks = [''];
    $this->categories = [''];
    
    $this->showModal = true;
};
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h2 class="text-2xl font-black text-gray-900">Senarai Pertandingan</h2>
            <p class="text-sm text-gray-500">Urus dan pantau semua pertandingan di sini.</p>
        </div>
        <button wire:click="openCreateModal" class="flex items-center px-5 py-2.5 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-100">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Tambah Pertandingan
        </button>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-50 text-green-700 rounded-xl border border-green-100 font-bold">
            {{ session('message') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Nama</th>
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Status</th>
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest text-right">Tindakan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($competitions as $competition)
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900">{{ $competition->name }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900">{{ $competition->status }}</div>
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <div class="flex justify-end gap-3">
                                <button wire:click="edit({{ $competition->id }})" 
                                    class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors group" 
                                    title="Edit Pertandingan">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>

                                <button wire:click="delete({{ $competition->id }})" 
                                    wire:confirm="Adakah anda pasti mahu memadam pertandingan ini?"
                                    class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                    title="Padam Pertandingan">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>                    
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-400 italic">Tiada pertandingan ditemui. Sila tambah pertandingan baru.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showModal', false)"></div>
                
                <div class="inline-block align-bottom bg-white rounded-[2.5rem] text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border-4 border-white">
                    <h3 class="text-xl font-black text-gray-900 mb-6 my-8 mx-8">
                        {{ $editing ? 'Kemaskini Pertandingan' : 'Tambah Pertandingan Baru' }}
                    </h3>
                    <form wire:submit.prevent="save" class="space-y-8 max-h-[80vh] overflow-y-auto px-4 pb-10">
                        <section class="space-y-4">
                            <h3 class="text-sm font-black text-blue-600 uppercase tracking-widest border-b pb-2">1. Maklumat Asas</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="col-span-2 md:col-span-1">
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Nama Pertandingan</label>
                                    <input type="text" wire:model="name" class="w-full rounded-xl border-gray-200">
                                </div>
                                <div class="col-span-2 md:col-span-1">
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Slug (URL)</label>
                                    <input type="text" wire:model="slug" class="w-full rounded-xl border-gray-200" placeholder="auto-generate jika kosong">
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Slogan</label>
                                    <input type="text" wire:model="description" class="w-full rounded-xl border-gray-200">
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Pengenalan</label>
                                    <textarea wire:model="introduction" rows="4" class="w-full rounded-xl border-gray-200"></textarea>
                                </div>
                            </div>
                        </section>

                       <section class="space-y-4 bg-gray-50 p-6 rounded-[2rem] border border-gray-100">
                            <div class="flex justify-between items-center border-b border-gray-200 pb-3">
                                <h3 class="text-sm font-black text-blue-600 uppercase tracking-widest">2. Objektif Pertandingan</h3>
                                <button type="button" wire:click="addObjective" class="text-xs font-bold bg-blue-600 text-white px-4 py-2 rounded-full hover:bg-blue-700 transition">
                                    + Tambah Sub-Objektif
                                </button>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1 ml-2">Mesej Utama (Slogan Objektif)</label>
                                <input type="text" wire:model="main_objective" class="w-full rounded-2xl border-gray-200 bg-white focus:ring-blue-500 shadow-sm" placeholder="Contoh: Meningkatkan produktiviti melalui sistem penyampaian kreatif">
                            </div>
    
                            <div class="space-y-4">
                            @foreach($sub_objectives as $index => $obj)
                                <div class="flex gap-4 items-start bg-white p-4 rounded-2xl shadow-sm border border-gray-100 relative group">
                                    <div class="relative" x-data="{ open: false }">
                                        <button type="button" @click="open = !open" class="w-14 h-14 flex items-center justify-center bg-gray-50 border-2 border-dashed border-gray-200 rounded-2xl hover:border-blue-400 hover:bg-blue-50 transition-all">
                                            <span class="text-2xl">
                                                {{ $sub_objectives[$index]['icon'] ?: '💡' }}
                                            </span>
                                        </button>
                                        <div x-show="open" 
                                            @click.away="open = false" x-transition class="absolute z-[60] top-full left-0 mt-2 p-3 bg-white shadow-2xl rounded-2xl border border-gray-100 w-52" style="display: none;">
                                            <div class="grid grid-cols-4 gap-2">
                                                @foreach($emojis as $emoji)
                                                    <button 
                                                        type="button" 
                                                        wire:click="$set('sub_objectives.{{ $index }}.icon', '{{ $emoji }}')"
                                                        @click="open = false"
                                                        class="w-10 h-10 flex items-center justify-center hover:bg-blue-50 rounded-lg text-xl transition-colors">
                                                        {{ $emoji }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex-1 grid grid-cols-1 gap-2">
                                        <input type="text" 
                                            wire:model="sub_objectives.{{ $index }}.title" 
                                            class="w-full rounded-xl border-gray-200 text-sm font-bold focus:ring-blue-500" 
                                            placeholder="Tajuk Objektif (cth: Inovasi Kreatif)">
                    
                                        <input type="text" 
                                            wire:model="sub_objectives.{{ $index }}.desc" 
                                            class="w-full rounded-xl border-gray-200 text-xs text-gray-500 focus:ring-blue-500" 
                                            placeholder="Penerangan ringkas objektif...">
                                    </div>

                                    <button type="button" 
                                        wire:click="removeObjective({{ $index }})" 
                                        class="mt-3 p-2 text-red-300 hover:text-red-500 transition-colors"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="Wait, let's just use × for simplicity 😅">×</path></svg>
                                        <span class="text-2xl font-light">×</span>
                                    </button>
                                </div>
                            @endforeach
                            </div>
                        </section>
                        <section class="space-y-4">
                            <h3 class="text-sm font-black text-blue-600 uppercase tracking-widest border-b pb-2">3. Syarat Penyertaan</h3>
                            <div class="space-y-3">
                                @foreach($requirements as $index => $req)
                                    <div class="flex gap-3 items-center bg-gray-50 p-3 rounded-2xl">
                                        <select wire:model="requirements.{{ $index }}.is_allowed" class="rounded-lg border-gray-200 text-xs">
                                            <option value="1">Dibenarkan (Tick)</option>
                                            <option value="0">Dilarang (Cross)</option>
                                        </select>
                                        <div class="flex-1 space-y-2">
                                            <input type="text" wire:model="requirements.{{ $index }}.title" class="w-full rounded-lg border-gray-200 text-sm" placeholder="Nama Syarat">
                                            <input type="text" wire:model="requirements.{{ $index }}.desc" class="w-full rounded-lg border-gray-200 text-xs" placeholder="Penerangan syarat">
                                        </div>
                                        <button type="button" wire:click="removeRequirement({{ $index }})" class="text-red-400 hover:text-red-600 px-2">×</button>
                                    </div>
                                @endforeach
                                <button type="button" wire:click="addRequirement" class="text-xs font-bold text-blue-600 hover:underline">+ Tambah Syarat</button>
                            </div>
                        </section>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <section class="space-y-4">
                                <h3 class="text-sm font-black text-blue-600 uppercase tracking-widest border-b pb-2">4. Hadiah (RM)</h3>
                                <div class="space-y-3">
                                    <input type="number" wire:model="prizes.johan" class="w-full rounded-xl border-gray-200 text-sm" placeholder="Johan">
                                    <input type="number" wire:model="prizes.naib_johan" class="w-full rounded-xl border-gray-200 text-sm" placeholder="Naib Johan">
                                    <input type="number" wire:model="prizes.ketiga" class="w-full rounded-xl border-gray-200 text-sm" placeholder="Ketiga">
                                </div>
                            </section>

                            <section class="space-y-4">
                                <h3 class="text-sm font-black text-blue-600 uppercase tracking-widest border-b pb-2">5. Bidang (Tracks)</h3>
                                <div class="space-y-2">
                                    @foreach($tracks as $index => $track)
                                        <div class="flex gap-2">
                                            <input type="text" wire:model="tracks.{{ $index }}" class="flex-1 rounded-xl border-gray-200 text-sm" placeholder="Nama Bidang">
                                            <button type="button" wire:click="removeTrack({{ $index }})" class="text-red-400">×</button>
                                        </div>
                                    @endforeach
                                    <button type="button" wire:click="addTrack" class="text-xs font-bold text-blue-600 hover:underline">+ Tambah Bidang</button>
                                </div>
                            </section>

                            <section class="space-y-4">
                                <h3 class="text-sm font-black text-blue-600 uppercase tracking-widest border-b pb-2">6. Kategori</h3>
                                <div class="space-y-2">
                                    @foreach($categories as $index => $category)
                                        <div class="flex gap-2">
                                            <input type="text" wire:model="categories.{{ $index }}" class="flex-1 rounded-xl border-gray-200 text-sm" placeholder="Nama Kategori">
                                            <button type="button" wire:click="removeCategory({{ $index }})" class="text-red-400">×</button>
                                        </div>
                                    @endforeach
                                    <button type="button" wire:click="addCategory" class="text-xs font-bold text-blue-600 hover:underline">+ Tambah Kategori</button>
                                </div>
                            </section>
                        </div>

                        <div class="pt-6 border-t flex gap-3 sticky bottom-0 bg-white">
                            <button type="submit" class="flex-1 bg-blue-600 text-white py-4 rounded-2xl font-black uppercase tracking-widest hover:bg-blue-700 transition shadow-xl shadow-blue-200">
                                {{ $editing ? 'Simpan Perubahan' : 'Tambah Pertandingan Baru' }}
                            </button>
                            <button type="button" wire:click="$set('showModal', false)" class="px-8 bg-gray-100 text-gray-500 py-4 rounded-2xl font-bold hover:bg-gray-200 transition">
                                Batal
                            </button>
                        </div>
                    </form>                
                </div>
            </div>
        </div>
    @endif
</div>
