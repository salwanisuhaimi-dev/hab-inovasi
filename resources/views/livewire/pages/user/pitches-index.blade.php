<?php

use App\Models\Pitch;
use App\Models\User;
use function Livewire\Volt\{layout, state, with};

layout('layouts.app');

state([
    'showModal' => false,
    'showViewModal' => false,
    'viewingPitch' => null,
    'editing' => null,
    'user_id' => auth()->user()->id,
    'title' => '',
    'description' => '',
    'method' => '',
]);

with([
    'pitches' => fn() => Pitch::where('user_id', auth()->id())
        ->latest()
        ->get(),
]);

$openCreateModal = function() {
    $this->reset();
    $this->user_id = auth()->user()->id;
    $this->showModal = true;
};

$edit = function (Pitch $pitch) {
    $this->editing = $pitch->id;
    $this->title = $pitch->title;
    $this->description = $pitch->description;
    $this->method = $pitch->method;
};

$save = function() {
  $data = $this->validate([
          'title' => 'required',
          'description' => 'required',
          'method' => 'required'
      ]);

      $payload = [
         'user_id' => auth()->id(),
         'title' => $this->title,
         'description' => $this->description,
         'method' => $this->method,
      ];

      if ($this->editing) {
          Pitch::find($this->editing)->update([
              'title' => $this->title,
              'description' => $this->description,
              'method' => $this->method,
          ]);
          session()->flash('message', 'Idea berjaya dikemaskini!');
      } else {
          Pitch::create($payload);
          session()->flash('message', 'Idea berjaya disimpan!');
      }

      $this->reset();
      $this->showModal = false;
};

$delete = function ($id) {
    $pitch = Pitch::findOrFail($id);

    if ($pitch->submissions()->exists()) {
        $pitch->submissions()->delete();
    }

    $pitch->delete();
    session()->flash('message', 'Idea berjaya dipadam!');

};

$viewDetails = function(Pitch $pitch) {
    $this->viewingPitch = $pitch;
    $this->showViewModal = true;
};

?>

<div class="p-6">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h2 class="text-2xl font-black text-gray-900">Senarai Idea</h2>
            <p class="text-sm text-gray-500">Urus idea anda di sini.</p>
        </div>
        <button wire:click="openCreateModal" class="flex items-center px-5 py-2.5 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-100">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Tambah Idea
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
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Idea</th>
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Cara Pelaksanaan</th>
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Tarikh</th>
                    <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest text-right">Tindakan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($pitches as $pitch)
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900">{{ $pitch->title }}</div>
                            <div class="text-xs text-gray-500 truncate w-48">{{ $pitch->description }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900 truncate w-48">
                                {{ $pitch->method }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ \Carbon\Carbon::parse($pitch->timestamp)->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <div class="flex justify-end gap-3">
                                <button wire:click="viewDetails({{ $pitch->id }})" class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors group" title="Lihat Butiran">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                </button>
                                <button wire:click="edit({{ $pitch->id }})" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors group" title="Edit Idea">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button wire:click="delete({{ $pitch->id }})" wire:confirm="Adakah anda pasti mahu memadam pitch ini?" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Padam Idea">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>

                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-400 italic">Tiada idea ditemui. Sila tambah idea baru.</td>
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
                        {{ $editing ? 'Kemaskini Idea' : 'Tambah Idea Baru' }}
                    </h3>

                    <form wire:submit.prevent="save" class="space-y-4">
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase mb-1">Tajuk Idea</label>
                            <input type="text" wire:model="title" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 focus:border-blue-500">
                            @error('title') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>
                        <div>
                             <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block mb-2">Penerangan</label>
                             <textarea wire:model="description" rows="3" placeholder="Berikan sedikit ringkasan tentang idea ini..." class="w-full bg-slate-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-blue-500 transition-all"></textarea>
                             @error('description') <span class="text-red-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                             <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block mb-2">Cara Pelaksanaan</label>
                             <textarea wire:model="method" rows="3" placeholder="Berikan sedikit ringkasan tentang pelaksanaan idea ini..." class="w-full bg-slate-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-blue-500 transition-all"></textarea>
                             @error('method') <span class="text-red-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div class="pt-4 flex gap-3">
                            <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition">
                                {{ $editing ? 'Simpan Perubahan' : 'Simpan Idea' }}
                            </button>
                            <button type="button" wire:click="$set('showModal', false)" class="flex-1 bg-gray-100 text-gray-600 py-3 rounded-xl font-bold hover:bg-gray-200 transition">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if($showViewModal && $viewingPitch)
    <div class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto outline-none focus:outline-none">
        <div class="fixed inset-0 bg-black opacity-40" wire:click="$set('showViewModal', false)"></div>

        <div class="relative w-full max-w-2xl mx-auto my-6 z-50 bg-white rounded-xl shadow-lg flex flex-col p-6 max-h-[90vh] overflow-y-auto">

            <div class="flex items-start justify-between border-b border-gray-100 pb-4 mb-4">
                <h3 class="text-xl font-bold text-gray-900">
                    {{ $viewingPitch->title }}
                </h3>
                <button wire:click="$set('showViewModal', false)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="space-y-6 text-sm">
                <div>
                    <h4 class="font-semibold text-xs text-gray-400 uppercase tracking-wider mb-1">Butiran Idea</h4>
                    <p class="text-gray-700 leading-relaxed bg-gray-50 p-4 rounded-lg whitespace-pre-line">
                        {{ $viewingPitch->description }}
                    </p>
                </div>

                <div>
                    <h4 class="font-semibold text-xs text-gray-400 uppercase tracking-wider mb-1">Cara Pelaksanaan (Method)</h4>
                    <p class="text-gray-700 leading-relaxed bg-gray-50 p-4 rounded-lg whitespace-pre-line">
                        {{ $viewingPitch->method }}
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-end pt-4 mt-6 border-t border-gray-100">
                <button wire:click="$set('showViewModal', false)" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    </div>
@endif
</div>
