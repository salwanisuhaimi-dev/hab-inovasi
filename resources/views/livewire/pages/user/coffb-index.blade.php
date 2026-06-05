<?php

use App\Models\CoffeeBreakSession;
use App\Models\Department;
use function Livewire\Volt\{layout, state, with, usesFileUploads, mount, updated};
use Livewire\WithFileUploads;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

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
    'department_id' => auth()->user()->department_id,
    'image_slot_0' => null,
    'image_slot_1' => null,
    'image_slot_2' => null,
]);

$removeImageSlot = function ($index) {
    if ($index == 0) $this->image_slot_0 = null;
    if ($index == 1) $this->image_slot_1 = null;
    if ($index == 2) $this->image_slot_2 = null;
};

with([

    'sessions' => fn() => CoffeeBreakSession::where('created_by', auth()->id())
        ->with(['user.department'])
        ->latest('date_created')
        ->get(),
    'departments' => fn() => \App\Models\Department::orderBy('name')->get(),
]);


$openCreateModal = function() {
    $this->reset();
    $this->date_created = date('Y-m-d');
    $this->created_by = auth()->user()->id;
    $this->department_id = auth()->user()->department_id;

    $this->image_slot_0 = null;
    $this->image_slot_1 = null;
    $this->image_slot_2 = null;

    $this->showModal = true;
};

$edit = function (CoffeeBreakSession $session) {
    $this->editing = $session->id;
    $this->location = $session->location;
    $this->date_created = $session->date_created;
    $this->start_time = $session->start_time;
    $this->end_time = $session->end_time;
    $this->created_by = $session->created_by;
    $this->description = $session->description;
    $this->department_id = $session->department_id;
    $this->existing_images = $session->image_paths ?? [];
    $paths = $session->image_paths ?? [];
    $this->image_slot_0 = $paths[0] ?? null;
    $this->image_slot_1 = $paths[1] ?? null;
    $this->image_slot_2 = $paths[2] ?? null;

    $this->showModal = true;
};

$save = function () {
    $this->validate([
        'location' => 'required|string|max:255',
        'date_created' => 'required|date',
        'start_time' => 'required',
        'end_time' => 'required',
        'created_by' => 'required',
        'department_id' => 'required|exists:departments,id',
    ]);

    if (empty($this->image_slot_0) || empty($this->image_slot_1) || empty($this->image_slot_2)) {
        $this->addError('image_slots', "Sila pastikan ketiga-tiga slot gambar diisi.");
        return;
    }

    $manager = new ImageManager(new Driver());
    $finalPaths = [];

    if (!file_exists(storage_path('app/public/sessions'))) {
        mkdir(storage_path('app/public/sessions'), 0755, true);
    }

    $slots = [$this->image_slot_0, $this->image_slot_1, $this->image_slot_2];

    foreach ($slots as $slot) {
        if (is_string($slot)) {
            $finalPaths[] = $slot;
        } elseif (is_object($slot) && method_exists($slot, 'getRealPath')) {
            $filename = 'sess_' . uniqid() . '.webp';
            $image = $manager->read($slot->getRealPath());
            $image->scale(width: 1200);
            $encoded = $image->toWebp(80);

            file_put_contents(storage_path('app/public/sessions/' . $filename), $encoded);
            $finalPaths[] = 'sessions/' . $filename;
        }
    }

    $payload = [
        'location' => $this->location,
        'date_created' => $this->date_created,
        'start_time' => $this->start_time,
        'end_time' => $this->end_time,
        'created_by' => $this->created_by,
        'description' => $this->description,
        'department_id' => $this->department_id,
        'image_paths' => $finalPaths,
    ];

    if ($this->editing) {
        CoffeeBreakSession::find($this->editing)->update($payload);
        session()->flash('message', 'Sesi Coff-B berjaya dikemaskini!');
    } else {
        CoffeeBreakSession::create($payload);
        session()->flash('message', 'Sesi Coff-B baru berjaya direkodkan!');
    }

    $this->reset(['showModal', 'editing', 'location', 'image_slot_0', 'image_slot_1', 'image_slot_2']);
    $this->showModal = false;
};

$delete = function ($id) {
    CoffeeBreakSession::find($id)->delete();
    session()->flash('message', 'Sesi telah dipadam.');
};

mount(function () {
    $user = auth()->user();

    if (empty($user->department_id) || empty($user->telephone_num) || empty($user->position)) {

        session()->flash('warning', 'Sila lengkapkan maklumat perkhidmatan anda terlebih dahulu.');

        return redirect()->route('profile.edit');
    }
});

?>

<div class="p-8 max-w-7xl mx-auto">
    {{-- Header Section --}}
    <div class="flex justify-between items-center mb-10">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight">Sesi Coffee Break</h2>
            <p class="text-sm text-slate-500 font-medium">Pantau sesi perbincangan idea mengikut bahagian.</p>
        </div>
        <button wire:click="openCreateModal" class="flex items-center px-6 py-3 bg-amber-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-amber-700 transition shadow-xl shadow-amber-100">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Tambah Sesi
        </button>
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
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Sesi & Lokasi</th>
                    <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Bahagian</th>
                    <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Bil. Idea</th>
                    <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Waktu</th>
                    <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Tindakan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($sessions as $session)
                    <tr class="hover:bg-amber-50/20 transition-colors group">
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-amber-100 flex items-center justify-center text-amber-600 font-black text-xs shadow-sm flex-shrink-0 uppercase">
                                    {{ \Carbon\Carbon::parse($session->date_created)->format('M') }}
                                </div>
                                <div>
                                    <div class="font-black text-slate-900 tracking-tight text-base">
                                        {{ \Carbon\Carbon::parse($session->date_created)->format('d F Y') }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 font-bold mt-0.5 flex items-center gap-1 uppercase tracking-tighter">
                                        📍 {{ $session->location }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-5">
                            <span class="text-[10px] font-black text-amber-700 bg-amber-50 px-3 py-1.5 rounded-xl border border-amber-100 uppercase tracking-widest">
                                {{ $session->department->code ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="px-8 py-5 text-center">
                            <div class="inline-flex items-center justify-center px-4 py-1.5 rounded-full bg-slate-900 text-white text-[10px] font-black tracking-tighter shadow-lg shadow-slate-200">
                               {{ $session->ideas->count() }} IDEA
                            </div>
                        </td>
                        <td class="px-8 py-5">
                            <div class="text-xs font-black text-slate-700">
                                {{ \Carbon\Carbon::parse($session->start_time)->format('h:i A') }}
                                <span class="block text-[10px] text-slate-300 font-medium uppercase tracking-tighter mt-0.5 italic">Hingga {{ \Carbon\Carbon::parse($session->end_time)->format('h:i A') }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-5 text-right whitespace-nowrap">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('user.coffb.ideas', $session->id) }}" class="p-2.5 bg-slate-50 hover:bg-amber-600 rounded-xl transition-all text-slate-400 hover:text-white group/btn relative">
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

    {{-- Modal Form --}}
    @if($showModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md" wire:click="$set('showModal', false)"></div>

            <div class="relative bg-white rounded-[3rem] shadow-2xl max-w-3xl w-full p-12 overflow-hidden transform transition-all">
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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-h-[400px] overflow-y-auto custom-scrollbar">
                       <div class="md:col-span-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 block">
                                Bahagian
                            </label>

                            <select wire:model="department_id" class="w-full bg-slate-50 border-none rounded-2xl p-4 font-bold text-sm focus:ring-2 focus:ring-amber-500 transition-all appearance-none">
                                <optgroup label="Bahagian Anda">
                                    <option value="{{ auth()->user()->department_id }}">
                                        {{ auth()->user()->department->name }}
                                    </option>
                                </optgroup>

                                <optgroup label="Pilihan Bahagian Lain">
                                      @foreach($departments->where('id', '!=', auth()->user()->department_id) as $dept)
                                            <option value="{{ $dept->id }}">
                                                  {{ $dept->name }}
                                            </option>
                                      @endforeach
                                </optgroup>
                            </select>
                            <p class="text-[9px] text-slate-400 mt-2 ml-2 italic font-medium uppercase tracking-tight">
                                  * Sesi akan didaftarkan di bawah bahagian yang dipilih.
                            </p>

                            @error('department_id')
                                  <span class="text-red-500 text-[10px] font-bold mt-2 block">{{ $message }}</span>
                            @enderror
                       </div>

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

                       <div class="md:col-span-2">
                           <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 block">Gambar Sesi</label>

                           @error('image_slots')
                               <span class="text-red-500 text-[10px] font-bold mb-3 block">{{ $message }}</span>
                           @enderror

                           <div class="grid grid-cols-3 gap-4 mb-4">
                                {{-- SLOT 1 --}}
                                <div class="relative group h-28 w-full border-2 border-dashed border-slate-200 rounded-2xl overflow-hidden bg-slate-50 flex items-center justify-center" wire:key="slot-0-panel">
                                    @if(!empty($image_slot_0))
                                        @if(is_string($image_slot_0))
                                            <img src="{{ asset('storage/' . $image_slot_0) }}" class="h-full w-full object-cover">
                                            <span class="absolute bottom-1 left-1 bg-amber-500 text-[8px] text-white px-1.5 rounded font-bold uppercase tracking-tight">Asal</span>
                                        @elseif(is_object($image_slot_0) && method_exists($image_slot_0, 'temporaryUrl'))
                                            <img src="{{ $image_slot_0->temporaryUrl() }}" class="h-full w-full object-cover">
                                            <span class="absolute bottom-1 left-1 bg-blue-500 text-[8px] text-white px-1.5 rounded font-bold uppercase tracking-tight">Baru</span>
                                        @endif
                                        <div class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                            <button type="button" onclick="document.getElementById('file_input_0').click()" class="p-2 bg-white text-slate-700 rounded-xl shadow hover:bg-amber-500 hover:text-white transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                                </svg>
                                            </button>
                                            <button type="button" wire:click="removeImageSlot(0)" class="p-2 bg-white text-red-500 rounded-xl shadow hover:bg-red-500 hover:text-white transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                    @else
                                        <button type="button" onclick="document.getElementById('file_input_0').click()" class="w-full h-full flex flex-col items-center justify-center cursor-pointer hover:bg-amber-50/30 transition focus:outline-none">
                                            <div wire:loading wire:target="image_slot_0" class="text-center">
                                                <svg class="animate-spin h-5 w-5 text-amber-600 mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            </div>
                                            <div wire:loading.remove wire:target="image_slot_0" class="text-center p-2">
                                                <svg class="w-5 h-5 text-slate-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                <span class="text-[8px] font-black text-slate-400 block mt-1 uppercase tracking-wider">Slot 1</span>
                                            </div>
                                        </button>
                                    @endif
                                    <input type="file" id="file_input_0" wire:model="image_slot_0" class="hidden" accept="image/*" />
                                </div>

                                {{-- SLOT 2 --}}
                                <div class="relative group h-28 w-full border-2 border-dashed border-slate-200 rounded-2xl overflow-hidden bg-slate-50 flex items-center justify-center" wire:key="slot-1-panel">
                                    @if(!empty($image_slot_1))
                                        @if(is_string($image_slot_1))
                                            <img src="{{ asset('storage/' . $image_slot_1) }}" class="h-full w-full object-cover">
                                            <span class="absolute bottom-1 left-1 bg-amber-500 text-[8px] text-white px-1.5 rounded font-bold uppercase tracking-tight">Asal</span>
                                        @elseif(is_object($image_slot_1) && method_exists($image_slot_1, 'temporaryUrl'))
                                            <img src="{{ $image_slot_1->temporaryUrl() }}" class="h-full w-full object-cover">
                                            <span class="absolute bottom-1 left-1 bg-blue-500 text-[8px] text-white px-1.5 rounded font-bold uppercase tracking-tight">Baru</span>
                                        @endif
                                        <div class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                            <button type="button" onclick="document.getElementById('file_input_1').click()" class="p-2 bg-white text-slate-700 rounded-xl shadow hover:bg-amber-500 hover:text-white transition">
                                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                              </svg>
                                            </button>
                                            <button type="button" wire:click="removeImageSlot(1)" class="p-2 bg-white text-red-500 rounded-xl shadow hover:bg-red-500 hover:text-white transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                    @else
                                        <button type="button" onclick="document.getElementById('file_input_1').click()" class="w-full h-full flex flex-col items-center justify-center cursor-pointer hover:bg-amber-50/30 transition focus:outline-none">
                                            <div wire:loading wire:target="image_slot_1" class="text-center">
                                                <svg class="animate-spin h-5 w-5 text-amber-600 mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            </div>
                                            <div wire:loading.remove wire:target="image_slot_1" class="text-center p-2">
                                                <svg class="w-5 h-5 text-slate-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                <span class="text-[8px] font-black text-slate-400 block mt-1 uppercase tracking-wider">Slot 2</span>
                                            </div>
                                        </button>
                                    @endif
                                    <input type="file" id="file_input_1" wire:model="image_slot_1" class="hidden" accept="image/*" />
                                </div>

                                {{-- SLOT 3 --}}
                                <div class="relative group h-28 w-full border-2 border-dashed border-slate-200 rounded-2xl overflow-hidden bg-slate-50 flex items-center justify-center" wire:key="slot-2-panel">
                                    @if(!empty($image_slot_2))
                                        @if(is_string($image_slot_2))
                                            <img src="{{ asset('storage/' . $image_slot_2) }}" class="h-full w-full object-cover">
                                            <span class="absolute bottom-1 left-1 bg-amber-500 text-[8px] text-white px-1.5 rounded font-bold uppercase tracking-tight">Asal</span>
                                        @elseif(is_object($image_slot_2) && method_exists($image_slot_2, 'temporaryUrl'))
                                            <img src="{{ $image_slot_2->temporaryUrl() }}" class="h-full w-full object-cover">
                                            <span class="absolute bottom-1 left-1 bg-blue-500 text-[8px] text-white px-1.5 rounded font-bold uppercase tracking-tight">Baru</span>
                                        @endif
                                        <div class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                            <button type="button" onclick="document.getElementById('file_input_2').click()" class="p-2 bg-white text-slate-700 rounded-xl shadow hover:bg-amber-500 hover:text-white transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                                </svg>
                                            </button>
                                            <button type="button" wire:click="removeImageSlot(2)" class="p-2 bg-white text-red-500 rounded-xl shadow hover:bg-red-500 hover:text-white transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                    @else
                                        <button type="button" onclick="document.getElementById('file_input_2').click()" class="w-full h-full flex flex-col items-center justify-center cursor-pointer hover:bg-amber-50/30 transition focus:outline-none">
                                            <div wire:loading wire:target="image_slot_2" class="text-center">
                                                <svg class="animate-spin h-5 w-5 text-amber-600 mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            </div>
                                            <div wire:loading.remove wire:target="image_slot_2" class="text-center p-2">
                                                <svg class="w-5 h-5 text-slate-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                <span class="text-[8px] font-black text-slate-400 block mt-1 uppercase tracking-wider">Slot 3</span>
                                            </div>
                                        </button>
                                    @endif
                                    <input type="file" id="file_input_2" wire:model="image_slot_2" class="hidden" accept="image/*" />
                                </div>
                           </div>
                       </div>


                    </div>

                    <div class="pt-6 border-t border-slate-50 flex gap-4">
                        <button type="submit"
                                wire:loading.attr="disabled"
                                class="flex-1 bg-amber-600 text-white py-5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] shadow-xl shadow-amber-100 hover:bg-slate-900 transition-all disabled:opacity-50 disabled:cursor-not-allowed">

                            <span wire:loading.remove wire:target="save">
                                {{ $editing ? 'Simpan Perubahan' : 'Simpan Sesi' }}
                            </span>

                            <span wire:loading wire:target="save" class="flex items-center justify-center">
                                Menyimpan data...
                            </span>
                        </button>

                        <button type="button"
                                wire:loading.attr="disabled"
                                wire:click="$set('showModal', false)"
                                class="px-10 py-5 bg-slate-50 text-slate-400 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:bg-slate-100 transition-all disabled:opacity-30">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
