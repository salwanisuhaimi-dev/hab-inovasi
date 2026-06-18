<?php

use App\Models\Pitch;
use App\Models\Vote;
use function Livewire\Volt\{layout, state, computed};

layout('layouts.landing');

state([
    'selectedMonth' => (int) date('m'),
    'selectedYear' => (int) date('Y'),
    'showModal' => false,
    'showViewModal' => false,
    'viewingPitch' => null,
    'title' => '',
    'description' => '',
    'method' => '',
]);

// Fetch actual portal pitches with vote markers
$pitches = computed(function () {
    return Pitch::withCount('votes')
        ->withExists(['votes as has_voted' => function($query) {
            $query->where('user_id', auth()->id());
        }])
        ->latest()
        ->get();
});

// Fetch monthly leaderboard records sorted by vote counts
$topPitches = computed(function () {
    return Pitch::withCount(['votes' => function ($query) {
            $query->whereMonth('created_at', $this->selectedMonth)
                  ->whereYear('created_at', $this->selectedYear);
        }])
        ->orderByDesc('votes_count')
        ->take(3)
        ->get();
});

// Months translation helper
state([
    'months' => [
        1 => 'Januari', 2 => 'Februari', 3 => 'Mac', 4 => 'April',
        5 => 'Mei', 6 => 'Jun', 7 => 'Julai', 8 => 'Ogos',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Disember'
    ]
]);

$vote = function (Pitch $pitch) {
    $user = auth()->user();
    $existingVote = Vote::where('user_id', $user->id)->where('pitch_id', $pitch->id)->first();

    if ($existingVote) {
        $existingVote->delete();
        $user->decrement('credits', 5);
    } else {
        Vote::create(['user_id' => $user->id, 'pitch_id' => $pitch->id]);
        $user->increment('credits', 5);
    }
};

$viewDetails = function(Pitch $pitch) {
    $this->viewingPitch = $pitch;
    $this->showViewModal = true;
};

?>

<style>
    .pitch-gradient-header {
        background: linear-gradient(135deg, #111827 0%, #1e1b4b 100%);
    }
</style>

<div class="min-h-screen bg-[#faf7f2] text-[#4a3728] font-sans pb-20 overflow-x-hidden">
    <x-top-nav />

    <div class="max-w-7xl mx-auto px-6">
        <header class="my-5 pitch-gradient-header rounded-[50px] p-10 md:p-16 mb-16 shadow-2xl border-4 border-white/20 relative overflow-hidden text-white">
            <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-500/10 rounded-full -mr-32 -mt-32 blur-[100px]"></div>
            <div class="relative z-10 grid lg:grid-cols-2 gap-12 items-center">
                <div class="space-y-6 text-center lg:text-left">
                    <div class="inline-flex items-center px-4 py-1.5 bg-indigo-600/30 border border-indigo-500/40 rounded-full">
                        <span class="text-indigo-300 text-[9px] font-black uppercase tracking-[0.3em]">KREATIVITI & INOVASI</span>
                    </div>

                    <h1 class="text-5xl md:text-6xl font-black leading-tight tracking-tighter">
                        Hub <span class="text-amber-500 italic">Pitching</span> Idea
                    </h1>

                    <p class="text-indigo-100/70 text-lg max-w-md font-medium leading-relaxed">
                        Zon interaktif untuk berkongsi rancangan inovasi, mengundi strategi terbaik, dan menyumbang maklum balas membina.
                    </p>
                </div>

                <div class="bg-white/5 backdrop-blur-md rounded-[2.5rem] p-8 border border-white/10 shadow-inner">
                    <h3 class="text-amber-500 font-black text-sm mb-4 uppercase tracking-widest flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Sistem Kredit & Undian
                    </h3>

                    <ul class="space-y-3">
                        <li class="flex items-start gap-3 text-sm text-indigo-50/80">
                            <span class="text-amber-500 font-bold">•</span>
                            Setiap undian yang dihantar memberikan anda +5 kredit interaktif.
                        </li>
                        <li class="flex items-start gap-3 text-sm text-indigo-50/80">
                            <span class="text-amber-500 font-bold">•</span>
                            Anda dibenarkan menarik balik undian (Unvote) pada bila-bila masa.
                        </li>
                        <li class="flex items-start gap-3 text-sm text-indigo-50/80">
                            <span class="text-amber-500 font-bold">•</span>
                            Idea dengan undian bulanan tertinggi akan disenaraikan di Carta Utama.
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">

            <div class="lg:col-span-4 space-y-12">

                <section class="bg-[#efebe9] rounded-[32px] p-8 border border-stone-200">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-base font-black uppercase tracking-tight flex items-center gap-2 text-stone-800">
                            🏆 Carta Idea Terbaik
                        </h3>
                        <select wire:model.live="selectedMonth" class="text-[11px] font-bold bg-white text-stone-700 border-none rounded-xl py-1 px-2 outline-none focus:ring-1 focus:ring-amber-500">
                            @foreach($months as $num => $name)
                                <option value="{{ $num }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-3">
                        @forelse($this->topPitches as $index => $topPitch)
                            <div class="group flex items-center justify-between p-3.5 bg-white/80 hover:bg-white rounded-2xl transition-all border border-stone-300/30">
                                <div class="flex items-center gap-3 truncate">
                                    <div class="w-8 h-8 rounded-xl flex items-center justify-center font-black text-xs shrink-0
                                        {{ $index === 0 ? 'bg-amber-100 text-amber-700' : '' }}
                                        {{ $index === 1 ? 'bg-slate-200 text-slate-700' : '' }}
                                        {{ $index === 2 ? 'bg-orange-100 text-orange-700' : '' }}">
                                        #{{ $index + 1 }}
                                    </div>
                                    <div class="truncate">
                                        <h4 class="text-xs font-black text-stone-800 truncate max-w-[150px] uppercase">{{ $topPitch->title }}</h4>
                                        <p class="text-[9px] text-stone-400 truncate">Oleh: {{ $topPitch->user->name ?? 'Ahli Kumpulan' }}</p>
                                    </div>
                                </div>
                                <span class="text-[10px] font-black bg-stone-100 text-stone-600 px-2 py-1 rounded-md shrink-0">
                                    {{ $topPitch->votes_count }} Undi
                                </span>
                            </div>
                        @empty
                            <p class="text-[11px] text-stone-500 font-medium text-center py-4">Tiada rekod undian bulan ini.</p>
                        @endforelse
                    </div>
                </section>

                <section class="bg-white rounded-[40px] p-10 shadow-xl shadow-stone-200 border border-stone-100 relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-2 h-full bg-[#1e1b4b]"></div>
                    <h2 class="text-2xl font-black italic mb-2 tracking-tighter">Cadangan Umum</h2>
                    <p class="text-stone-400 text-[10px] mb-8 font-black uppercase tracking-[0.2em] italic">Saluran maklum balas am portal</p>

                    <div class="space-y-4">
                        <textarea rows="3" placeholder="Kongsikan pandangan atau maklum balas am anda..." class="w-full p-5 rounded-2xl bg-[#faf7f2] border-none text-sm outline-none focus:ring-2 focus:ring-indigo-600 transition-all placeholder-stone-400"></textarea>
                        <button class="w-full bg-[#3e2723] py-4 rounded-2xl text-white text-[10px] font-black uppercase tracking-[0.3em] shadow-lg hover:bg-[#1e1b4b] transition-all">Hantar Komen</button>
                    </div>
                </section>

                <section class="px-4 space-y-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-black text-stone-800 uppercase tracking-widest">Sembang Am</h3>
                        <div class="h-[1px] flex-1 bg-stone-200 mx-4"></div>
                    </div>

                    <div class="bg-white/60 p-5 rounded-[30px] border border-stone-100 relative">
                        <div class="flex gap-3 items-center mb-2">
                            <div class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-black text-[9px]">AF</div>
                            <span class="text-[10px] font-black uppercase text-stone-800 tracking-tight">Ahmad Faiz</span>
                        </div>
                        <p class="text-xs text-stone-500 leading-relaxed italic">"Sistem undian ni sangat telus. Memudahkan semua pihak!"</p>
                    </div>
                </section>
            </div>

            <div class="lg:col-span-8 space-y-16">
                <div class="grid grid-cols-1 gap-8 p-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-black text-stone-800 uppercase tracking-widest">Senarai Idea Inovasi</h3>
                        <div class="h-[1px] flex-1 bg-stone-200 mx-4"></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @forelse($this->pitches as $pitch)
                          <div class="bg-white p-8 rounded-[40px] shadow-sm hover:shadow-xl transition-all duration-300 border border-stone-100 relative group">
                                <div class="absolute top-6 right-8 opacity-20 group-hover:opacity-100 transition-opacity">
                                    <span class="text-2xl">⚡️</span>
                                </div>

                                <div class="flex items-center gap-3 mb-6">
                                      <div class="w-12 h-12 rounded-2xl bg-orange-100 flex items-center justify-center text-orange-700 font-black shadow-inner tracking-tighter text-sm">
                                      @php
                                          $name = $pitch->user->name ?? 'Ahli Kumpulan';
                                          $words = explode(' ', trim($name));
                                          $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                                      @endphp

                                      {{ $initials }}
                                      </div>

                                      <div>
                                          <h4 class="text-sm font-black text-stone-800 uppercase tracking-tight">
                                              {{ $pitch->user->name ?? 'Unknown' }}
                                          </h4>
                                          <span class="text-[9px] bg-stone-100 px-2 py-0.5 rounded text-stone-500 uppercase font-bold tracking-widest block w-max mt-0.5">
                                              {{ $pitch->user->department->name ?? 'N/A' }}
                                          </span>
                                      </div>
                                </div>

                                <div class="space-y-2">
                                    <h5 class="text-xs font-black text-stone-400 uppercase tracking-wider">Metodologi Idea:</h5>
                                    <p class="text-stone-600 text-sm leading-relaxed italic border-l-4 border-orange-200 pl-4 font-serif bg-orange-50/30 py-2 rounded-r-xl">
                                          "{{ $pitch->method ?? $pitch->description }}"
                                    </p>
                                </div>
                          </div>
                    @empty
                          <div class="col-span-full py-16 flex flex-col items-center justify-center bg-white rounded-[40px] border-2 border-dashed border-stone-200 shadow-sm relative overflow-hidden group">
                                <div class="relative z-10 flex flex-col items-center text-center px-6">
                                      <div class="w-20 h-20 bg-[#faf7f2] rounded-[30px] flex items-center justify-center shadow-inner mb-4 rotate-[-6deg] group-hover:rotate-0 transition-transform duration-500">
                                            <span class="text-4xl grayscale opacity-40 group-hover:grayscale-0 group-hover:opacity-100 transition-all">🌱</span>
                                      </div>
                                      <h3 class="text-xl font-black text-stone-800 uppercase italic tracking-tight">
                                            Tiada Idea <br> <span class="text-amber-500">Dikongsi Lagi</span>
                                      </h3>
                                      <p class="mt-2 text-xs text-stone-400 font-medium italic max-w-[280px] leading-relaxed">
                                            Belum ada sebarang cadangan strategi atau metodologi pitching dikemukakan buat masa ini.
                                      </p>
                                </div>
                          </div>
                     @endforelse
                     </div>
                </div>
            </div>
        </div>
    </div>
</div>
