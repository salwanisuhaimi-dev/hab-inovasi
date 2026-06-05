<?php

use App\Models\Department;
use function Livewire\Volt\{layout, state, with, usesFileUploads};

layout('layouts.app'); 
usesFileUploads();

state([
    'showModal' => false,
    'editing' => null,
    'name' => '',
    'code' => '',
    'description' => '',
    'status' => '',  
]);

$edit = function (Department $department) {
    $this->editing = $department->id;
    $this->name = $department->name;
    $this->code = $department->code;
    $this->description = $department->description;
    $this->status = $department->status;    
    $this->showModal = true;
};

with([
    'departments' => fn() => Department::latest()->get(),
]);

$save = function () {
    $data = $this->validate([
        'name' => 'required',
        'code' => 'required|unique:departments,code,' . $this->editing,
        'description' => 'nullable',
    ]);

    $payload = [
        'name' => $this->name,
        'code' => $this->code,
        'description' => $this->description,
    ];

    if ($this->editing) {
        Department::find($this->editing)->update($payload);
        session()->flash('message', 'Bahagian berjaya dikemaskini!');
    } else {
        Department::create($payload);
        session()->flash('message', 'Bahagian berjaya disimpan!');
    }

    $this->reset(); 
    $this->showModal = false;
    session()->flash('message', 'Bahagian berjaya disimpan!');
};

$openCreateModal = function() {
    $this->reset(['editing', 'name', 'code', 'description']);
    $this->showModal = true;
};

?>

<div class="p-6">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h2 class="text-2xl font-black text-gray-900">Senarai Bahagian</h2>
            <p class="text-sm text-gray-500">Urus dan pantau semua bahagian di sini.</p>
        </div>
        <button wire:click="openCreateModal" class="flex items-center px-5 py-2.5 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-100">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Tambah Bahagian
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
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Kod</th>
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Penerangan</th>
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Status</th>
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest text-right">Tindakan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($departments as $department)
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900">{{ $department->name }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900">{{ $department->code }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900">{{ $department->description }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900">{{ $department->status }}</div>
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <div class="flex justify-end gap-3">
                                <button wire:click="edit({{ $department->id }})" 
                                    class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors group" 
                                    title="Edit Bahagian">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>

                                <button wire:click="delete({{ $department->id }})" 
                                    wire:confirm="Adakah anda pasti mahu memadam bahagian ini?"
                                    class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                    title="Padam Bahagian">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>                    
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-400 italic">Tiada bahagian ditemui. Sila tambah bahagian baru.</td>
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
                        {{ $editing ? 'Kemaskini Bahagian' : 'Tambah Bahagian Baru' }}
                    </h3>
                    
                    <form wire:submit.prevent="save" class="space-y-4">
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase mb-1">Nama</label>
                            <input type="text" wire:model="name" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase mb-1">Kod</label>
                            <input type="text" wire:model="code" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase mb-1">Penerangan</label>
                            <input type="text" wire:model="description" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="pt-4 flex gap-3">
                            <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition">
                                {{ $editing ? 'Simpan Perubahan' : 'Simpan Bahagian' }}                            </button>
                            <button type="button" wire:click="$set('showModal', false)" class="flex-1 bg-gray-100 text-gray-600 py-3 rounded-xl font-bold hover:bg-gray-200 transition">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
