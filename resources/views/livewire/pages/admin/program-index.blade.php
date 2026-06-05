<?php

use App\Models\Program;
use function Livewire\Volt\{layout, state, with, usesFileUploads};

layout('layouts.app'); 
usesFileUploads();

state([
    'showModal' => false,
    'editing' => null,
    'title' => '',
    'category_id' => '',
    'start_date' => '',
    'end_date' => '',
    'start_time' => '',
    'end_time' => '',   
    'location' => '',
    'description' => '',
    'prize' => '',
    'deadline' => '',   
]);

$edit = function (Program $program) {
    $this->editing = $program->id;
    $this->title = $program->title;
    $this->category_id = $program->category_id;
    $this->start_date = $program->start_date;
    $this->end_date = $program->end_date;
    $this->start_time = $program->start_time;
    $this->end_time = $program->end_time;
    $this->location = $program->location;
    $this->description = $program->description;
    $this->prize = $program->prize;
    $this->deadline = $program->deadline;
    
    $this->showModal = true;
};

with([
    'programs' => fn() => Program::latest()->get(),
    'categories' => fn() => \App\Models\ProgramType::where('is_active', '1')->orderBy('name')->get(),

]);

$save = function () {
    $data = $this->validate([
        'title' => 'required',
        'category_id' => 'required',
        'start_date' => 'required|date',
        'end_date' => 'required|date',
        'deadline' => 'required|date', 
        'end_date' => 'nullable|date|after_or_equal:start_date',
    ]);

    $payload = [
        'title' => $this->title,
        'start_date' => $this->start_date,
        'end_date' => $this->end_date ?: null,
        'start_time' => $this->start_time,
        'end_time' => $this->end_time,
        'location' => $this->location,
        'description' => $this->description,
        'prize' => $this->prize,
        'deadline' => $this->deadline,
    ];

    if ($this->editing) {
        // Update data sedia ada
        Program::find($this->editing)->update($payload);
        session()->flash('message', 'Program berjaya dikemaskini!');
    } else {
        // Simpan data baru
        Program::create($payload);
        session()->flash('message', 'Program berjaya disimpan!');
    }

    $this->reset(); 
    $this->showModal = false;
    session()->flash('message', 'Program berjaya disimpan!');
};

$openCreateModal = function() {
    $this->reset(['editing', 'title', 'start_date', 'end_date', 'start_time', 'end_time', 'location', 'description', 'prize', 'deadline']);
    $this->showModal = true;
}

?>

<div class="p-6">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h2 class="text-2xl font-black text-gray-900">Senarai Program</h2>
            <p class="text-sm text-gray-500">Urus dan pantau semua program inovasi anda di sini.</p>
        </div>
        <button wire:click="openCreateModal" class="flex items-center px-5 py-2.5 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-100">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Tambah Program
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
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Program</th>
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Kategori</th>
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Tarikh</th>
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Masa</th>
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Hadiah</th>
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest text-right">Tindakan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($programs as $program)
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900">{{ $program->title }}</div>
                            <div class="text-xs text-gray-500 truncate w-48">{{ $program->description }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900">{{ $program->category->name }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-700 font-medium">{{ \Carbon\Carbon::parse($program->start_date)->format('d M Y') }}</div>
                            <div class="text-xs text-gray-400">
                                @if($program->end_date)
                                    {{ \Carbon\Carbon::parse($program->start_date)->format('d M') }} - {{ \Carbon\Carbon::parse($program->end_date)->format('d M Y') }}
                                @else
                                    {{ \Carbon\Carbon::parse($program->start_date)->format('d M Y') }}
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-xs text-gray-500 mt-1 flex items-center">
                                {{ \Carbon\Carbon::parse($program->start_time)->format('h:i A') }} 
                                @if($program->end_time)
                                    - {{ \Carbon\Carbon::parse($program->end_time)->format('h:i A') }}
                                @endif
                            </div>                        
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 bg-yellow-50 text-yellow-700 text-xs font-bold rounded-full border border-yellow-100">
                                {{ $program->prize }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <div class="flex justify-end gap-3">
                                <button wire:click="edit({{ $program->id }})" 
                                    class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors group" 
                                    title="Edit Program">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>

                                <button wire:click="delete({{ $program->id }})" 
                                    wire:confirm="Adakah anda pasti mahu memadam program ini?"
                                    class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                    title="Padam Program">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>                    
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-400 italic">Tiada program ditemui. Sila tambah program baru.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showModal', false)"></div>
                
                <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-8">
                    <h3 class="text-xl font-black text-gray-900 mb-6">
                        {{ $editing ? 'Kemaskini Program' : 'Tambah Program Baru' }}
                    </h3>
                    
                    <form wire:submit.prevent="save" class="space-y-4">
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase mb-1">Tajuk Program</label>
                            <input type="text" wire:model="title" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-bold text-gray-700 mb-2 italic">Kategori</label>
                            <select wire:model="department_id" 
                                class="w-full rounded-2xl border-gray-200 bg-gray-50 focus:border-blue-500 focus:ring-blue-500 p-4">
                                <option value="">Pilih Kategori</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                            </select>
                            @error('department_id') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>



                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1 tracking-wider">Tarikh Mula</label>
                                <input type="date" wire:model="start_date" 
                                class="w-full rounded-xl border-gray-200 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                @error('start_date') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1 tracking-wider">Tarikh Tamat</label>
                                <input type="date" wire:model="end_date" 
                                class="w-full rounded-xl border-gray-200 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                @error('end_date') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase mb-1">Masa Mula</label>
                                <input type="time" wire:model="start_time" class="w-full rounded-xl border-gray-200">
                            </div>

                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase mb-1">Masa Akhir</label>
                                <input type="time" wire:model="end_time" class="w-full rounded-xl border-gray-200">
                            </div>

                        </div>
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase mb-1">Tarikh Tutup</label>
                            <input type="date" wire:model="deadline" class="w-full rounded-xl border-gray-200">
                        </div>

                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase mb-1">Lokasi</label>
                            <input type="text" wire:model="location" placeholder="Contoh: Dewan Mezzanine" class="w-full rounded-xl border-gray-200">
                        </div>


                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase mb-1">Hadiah Utama</label>
                            <input type="text" wire:model="prize" placeholder="Contoh: RM1,000" class="w-full rounded-xl border-gray-200">
                        </div>

                        <div class="pt-4 flex gap-3">
                            <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition">
                                {{ $editing ? 'Simpan Perubahan' : 'Simpan Program' }}                            </button>
                            <button type="button" wire:click="$set('showModal', false)" class="flex-1 bg-gray-100 text-gray-600 py-3 rounded-xl font-bold hover:bg-gray-200 transition">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
