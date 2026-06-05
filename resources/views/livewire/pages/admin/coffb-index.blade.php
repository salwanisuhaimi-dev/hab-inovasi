<?php

use App\Models\CoffeeBreakSession;
use App\Models\Department;
use function Livewire\Volt\{layout, state, with, usesFileUploads, mount};

layout('layouts.app');
usesFileUploads();

state([
    'showModal' => false,
    'editing' => null,
    'location' => '',
    'date_created' => '',
    'start_time' => '',
    'end_time' => '',
    'created_by' => '',
    'description' => '',
    'showDetailModal' => false,
    'viewingArchive' => null,
]);

$openCreateModal = function() {
    $this->reset();
    $this->date_created = date('Y-m-d');
    $this->created_by = auth()->user()->id;
    $this->showModal = true;
};

with([
    'sessions' => fn() => CoffeeBreakSession::with(['user.department'])
        ->latest('date_created')
        ->get(),
]);

$edit = function (CoffeeBreakSession $session) {
    $this->editing = $session->id;
    $this->location = $session->location;
    $this->date_created = $session->date_created;
    $this->start_time = $session->start_time;
    $this->end_time = $session->end_time;
    $this->created_by = $session->created_by;
    $this->description = $session->description;
    $this->showModal = true;
};

$save = function () {
    $this->validate([
        'location' => 'required|string|max:255',
        'date_created' => 'required|date',
        'start_time' => 'required',
        'end_time' => 'required',
        'created_by' => 'required',
    ]);

    $payload = [
        'location' => $this->location,
        'date_created' => $this->date_created,
        'start_time' => $this->start_time,
        'end_time' => $this->end_time,
        'created_by' => $this->created_by,
        'description' => $this->description,
    ];

    if ($this->editing) {
        CoffeeBreakSession::find($this->editing)->update($payload);
        session()->flash('message', 'Sesi Coff-B berjaya dikemaskini!');
    } else {
        CoffeeBreakSession::create($payload);
        session()->flash('message', 'Sesi Coff-B baru berjaya direkodkan!');
    }

    $this->reset();
    $this->showModal = false;
};

$delete = function ($id) {
    CoffeeBreakSession::find($id)->delete();
    session()->flash('message', 'Sesi telah dipadam.');
};


$viewDetails = function ($id) {
    $this->viewingArchive = Archive::with(['department', 'competitions'])->find($id);
    $this->showDetailModal = true;
};

?>

<div class="p-8 max-w-7xl mx-auto">
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-10 md:flex-row md:justify-between md:items-center">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight">Sesi Coffee Break</h2>
            <p class="text-sm text-slate-500 font-medium">Pantau sesi perbincangan idea mengikut bahagian.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.coffb.report')}}" wire:navigate class="flex items-center px-6 py-3 bg-slate-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-800 transition shadow-xl shadow-slate-100">
              <svg class="w-4 h-4 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
              </svg>
              Laporan Analitik
            </a>
            <button wire:click="openCreateModal" class="flex items-center px-6 py-3 bg-amber-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-amber-700 transition shadow-xl shadow-amber-100">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Tambah Sesi
            </button>
        </div>
    </div>

    {{-- Alert Message --}}
    @if (session()->has('message'))
        <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100 font-bold text-sm flex items-center">
            <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            {{ session('message') }}
        </div>
    @endif

    {{-- Table Section --}}
    <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
              <thead class="bg-slate-50">
                  <tr>
                      <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Sesi & Lokasi</th>
                      <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Bahagian</th>
                      <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Bil. Idea</th>
                      <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Tindakan</th>
                  </tr>
              </thead>
              <tbody class="divide-y divide-slate-50">
                  @forelse($sessions as $session)
                      <tr class="hover:bg-amber-50/20 transition-colors group">
                          <td class="px-8 py-5">
                              <div class="flex items-center gap-4">
                                  <div>
                                      <div class="font-black text-slate-900 tracking-tight text-base">
                                          {{ \Carbon\Carbon::parse($session->date_created)->format('d F Y') }}
                                      </div>
                                      <div class="text-[11px] text-slate-400 font-bold mt-0.5 flex items-center gap-1 uppercase tracking-tighter">
                                          📍 {{ $session->location }}
                                      </div>
                                      <div class="text-xs font-black text-slate-700">

                                          <span class="block text-[10px] text-slate-300 font-medium uppercase tracking-tighter mt-0.5 italic">{{ \Carbon\Carbon::parse($session->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($session->end_time)->format('h:i A') }}</span>
                                      </div>
                                  </div>
                              </div>
                          </td>
                          <td class="px-8 py-5">
                              <div class="flex items-center gap-3">
                                  <div class="flex flex-col">
                                      <span class="text-xs font-black text-slate-900 uppercase tracking-tight">
                                          {{ $session->department->code ?? 'N/A' }}
                                      </span>
                                      <span class="text-[9px] font-bold text-amber-600 uppercase tracking-widest mt-0.5">
                                           {{ $session->user->name ?? 'User Tidak Diketahui' }}
                                      </span>
                                  </div>
                              </div>
                          </td>
                          <td class="px-8 py-5 text-center">
                              <div class="inline-flex items-center justify-center px-4 py-1.5 rounded-full bg-slate-900 text-white text-[10px] font-black tracking-tighter shadow-lg shadow-slate-200">
                                 {{ $session->ideas->count() }} IDEA
                              </div>
                          </td>
                          <td class="px-8 py-5 text-right whitespace-nowrap">
                              <div class="flex justify-end gap-2">
                                  <a href="{{ route('admin.coffb.ideas', $session->id) }}" class="p-2.5 bg-slate-50 hover:bg-amber-600 rounded-xl transition-all text-slate-400 hover:text-white group/btn relative">
                                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                      <span class="absolute -top-10 left-1/2 -translate-x-1/2 scale-0 group-hover/btn:scale-100 transition-all bg-slate-900 text-white text-[9px] font-black px-2 py-1.5 rounded-lg shadow-xl uppercase whitespace-nowrap z-30">Urus Idea</span>
                                  </a>
                                  <button wire:click="edit({{ $session->id }})" class="p-2.5 bg-slate-50 hover:bg-blue-600 rounded-xl transition-all text-slate-400 hover:text-white group/edit relative">
                                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                  </button>
                                  <button onclick="confirm('Pasti?') || event.stopImmediatePropagation()" wire:click="delete({{ $session->id }})" class="p-2.5 bg-slate-50 hover:bg-red-600 rounded-xl transition-all text-slate-400 hover:text-white">
                                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                  </button>
                              </div>
                          </td>
                      </tr>
                  @empty
                      <tr>
                          <td colspan="5" class="px-8 py-20 text-center">
                              <div class="flex flex-col items-center opacity-20">
                                  <svg class="w-20 h-20 text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="1"></path></svg>
                                  <p class="font-black uppercase tracking-widest text-xs">Tiada rekod sesi ditemui</p>
                              </div>
                          </td>
                      </tr>
                  @endforelse
              </tbody>
          </table>

        </div>
    </div>

    {{-- Modal Form --}}
    @if($showModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md" wire:click="$set('showModal', false)"></div>

            <div class="relative bg-white rounded-[3rem] shadow-2xl max-w-2xl w-full p-12 overflow-hidden transform transition-all">
                <div class="flex items-center gap-5 mb-10">
                    <div class="w-14 h-14 bg-amber-100 rounded-2xl flex items-center justify-center text-amber-600 shadow-inner">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-2xl font-black text-slate-900 tracking-tighter">
                            {{ $editing ? 'Kemaskini Sesi' : 'Tambah Sesi Baru' }}
                        </h3>
                        <p class="text-sm text-slate-400 font-medium">Lengkapkan butiran log sesi coffee break.</p>
                    </div>
                </div>

                <form wire:submit.prevent="save" class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="md:col-span-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 block">Lokasi Sesi</label>
                            <input type="text" wire:model="location" placeholder="cth: Bilik Gerakan / Pantri Level 3" class="w-full bg-slate-50 border-none rounded-2xl p-4 font-bold text-sm focus:ring-2 focus:ring-amber-500 transition-all">
                            @error('location') <span class="text-red-500 text-[10px] font-bold mt-2 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 block">Tarikh Sesi</label>
                            <input type="date" wire:model="date_created" class="w-full bg-slate-50 border-none rounded-2xl p-4 font-bold text-sm focus:ring-2 focus:ring-amber-500 transition-all">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 block">Masa Mula</label>
                            <input type="time" wire:model="start_time" class="w-full bg-slate-50 border-none rounded-2xl p-4 font-bold text-sm focus:ring-2 focus:ring-amber-500 transition-all">
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 block">Masa Tamat</label>
                            <input type="time" wire:model="end_time" class="w-full bg-slate-50 border-none rounded-2xl p-4 font-bold text-sm focus:ring-2 focus:ring-amber-500 transition-all">
                        </div>
                    </div>

                    <div class="pt-6 border-t border-slate-50 flex gap-4">
                        <button type="submit" class="flex-1 bg-amber-600 text-white py-5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] shadow-xl shadow-amber-100 hover:bg-slate-900 transition-all">
                            {{ $editing ? 'Simpan Perubahan' : 'Simpan Sesi' }}
                        </button>
                        <button type="button" wire:click="$set('showModal', false)" class="px-10 py-5 bg-slate-50 text-slate-400 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:bg-slate-100 transition-all">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
