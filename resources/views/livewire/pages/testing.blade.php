<?php

use function Livewire\Volt\{layout, state, with};

layout('layouts.landing');

// 1. Set state asal untuk memegang ID jabatan yang dipilih
state([
    'selectedDepartment' => '',
]);

// 2. Tarik data sebenar dari database menggunakan closure (fn)
with([
    'departments' => fn() => \App\Models\Department::orderBy('name')->get(),
]);

?>

<div class="min-h-screen bg-[#faf7f2] text-[#4a3728] font-sans pb-20 overflow-x-hidden">
  <x-top-nav />


    <div class="max-w-md mx-auto pt-20 px-4">

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-stone-100">
            <h2 class="text-sm font-black uppercase tracking-wider text-stone-400 mb-4">
                Ujian Data Sebenar (Real Data)
            </h2>

            <div class="relative mb-6">
                <select wire:model.live="selectedDepartment" id="department_filter"
                    class="w-full bg-stone-50 border border-stone-200 text-stone-700 text-sm font-bold py-3 px-4 rounded-xl focus:outline-none focus:border-orange-300 focus:ring-4 focus:ring-orange-50 transition-all cursor-pointer">

                    <option value="">Semua Bahagian (Keseluruhan)</option>

                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" wire:key="dept-{{ $dept->id }}">
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="p-4 bg-red-50 border border-red-100 text-red-800 rounded-xl text-xs font-mono">
                ID Jabatan Semasa: <span class="font-bold text-sm">"{{ $selectedDepartment }}"</span>
            </div>
        </div>

    </div>
</div>
