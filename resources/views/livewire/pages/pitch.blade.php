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

$viewDetails = function($id) {
    $this->viewingPitch = Pitch::find($id);
    $this->showViewModal = true;
};

$vote = function (Pitch $pitch) {
    $user = auth()->user();

    if ($pitch->user_id === $user->id) {
        session()->flash('error', 'Anda tidak boleh mengundi idea anda sendiri!');
        return;
    }

    if ((isset($user->is_admin) && $user->is_admin) || (isset($user->role) && $user->role === 'admin')) {
        session()->flash('error', 'Pengguna berstatus Pentadbir (Admin) tidak dibenarkan mengundi.');
        return;
    }

    $existingVote = Vote::where('user_id', $user->id)->where('pitch_id', $pitch->id)->first();

    if ($existingVote) {
        $existingVote->delete();
        $user->decrement('credits', 1);
    } else {
        Vote::create(['user_id' => $user->id, 'pitch_id' => $pitch->id]);
        $user->increment('credits', 1);
    }
};

?>
<div class="min-h-screen bg-[#faf7f2] text-[#4a3728] font-sans pb-20 overflow-x-hidden">
    <style>
        .pitch-gradient-header {
            background: linear-gradient(135deg, #111827 0%, #1e1b4b 100%);
        }
        [x-cloak] { display: none !important; }

        @keyframes paper-fly-left {
            0% {
                transform: translateY(20px) translateX(0px) rotate(0deg) scale(0.3);
                opacity: 0;
            }
            30% {
                opacity: 1;
                transform: translateY(-40px) translateX(-20px) rotate(-15deg) scale(0.8);
            }
            70% {
                transform: translateY(-100px) translateX(-40px) rotate(-5deg) scale(1);
            }
            100% {
                transform: translateY(-160px) translateX(-60px) rotate(-25deg) scale(0.4);
                opacity: 0;
            }
        }
        .animate-paper-left {
            animation: paper-fly-left 6s ease-in-out infinite;
        }

        @keyframes paper-fly-right {
            0% {
                transform: translateY(30px) translateX(0px) rotate(0deg) scale(0.2);
                opacity: 0;
            }
            25% {
                opacity: 1;
                transform: translateY(-30px) translateX(30px) rotate(25deg) scale(0.7);
            }
            60% {
                transform: translateY(-90px) translateX(15px) rotate(45deg) scale(0.9);
            }
            100% {
                transform: translateY(-180px) translateX(50px) rotate(15deg) scale(0.5);
                opacity: 0;
            }
        }
        .animate-paper-right {
            animation: paper-fly-right 5s ease-in-out infinite;
        }

        @keyframes paper-fly-center {
            0% {
                transform: translateY(40px) translateX(-10px) rotate(-10deg) scale(0.4);
                opacity: 0;
            }
            40% {
                opacity: 1;
                transform: translateY(-50px) translateX(10px) rotate(10deg) scale(0.9);
            }
            80% {
                transform: translateY(-120px) translateX(-5px) rotate(-20deg) scale(0.7);
            }
            100% {
                transform: translateY(-200px) translateX(0px) rotate(5deg) scale(0.3);
                opacity: 0;
            }
        }
        .animate-paper-center {
            animation: paper-fly-center 7s ease-in-out infinite;
        }

        @keyframes box-float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-8px) rotate(2deg); /* Naik 8px dan senget 2 darjah ke kanan */
            }
        }

        .animate-box-float {
            animation: box-float 5s ease-in-out infinite;
        }
    </style>
    <x-top-nav />

    <div class="fixed top-20 -left-10 opacity-20 rotate-45 pointer-events-none">
        <span class="text-8xl">❓❓</span>
    </div>
    <div class="fixed bottom-10 -right-10 opacity-10 -rotate-12 pointer-events-none">
        <span class="text-[120px]">✨</span>
    </div>

    <div class="max-w-7xl mx-auto px-6">
         <header class="my-5 rounded-[50px] p-10 md:p-16 mb-16 shadow-2xl relative overflow-hidden text-white border border-cyan-500/20
               bg-slate-950 bg-[radial-gradient(circle_at_center,rgba(6,182,212,0.12)_0%,transparent_75%)]">
               <div class="absolute inset-0 rounded-[50px] border-2 border-cyan-500/10 pointer-events-none shadow-[inset_0_0_30px_rgba(6,182,212,0.1)] z-0"></div>
               <div class="absolute -top-20 -left-20 w-80 h-80 bg-amber-500/5 rounded-full blur-[100px] pointer-events-none z-0"></div>
               <div class="absolute -bottom-20 -right-20 w-96 h-96 bg-cyan-500/20 rounded-full blur-[130px] pointer-events-none z-0"></div>

               <div class="absolute inset-0 w-full h-full opacity-40 pointer-events-none z-0"
                        style="mask-image: radial-gradient(circle, white, transparent 80%); -webkit-mask-image: radial-gradient(circle, white, transparent 80%);">
                       <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                           <defs>
                               <filter id="glow" x="-20%" y="-20%" width="140%" height="140%">
                                   <feGaussianBlur stdDeviation="2" result="blur" />
                                   <feComposite in="SourceGraphic" in2="blur" operator="over" />
                               </filter>
                           </defs>

                           <g stroke="rgba(6, 182, 212, 0.15)" stroke-width="1">
                               <line x1="10%" y1="20%" x2="25%" y2="45%" />
                               <line x1="25%" y1="45%" x2="45%" y2="25%" />
                               <line x1="45%" y1="25%" x2="40%" y2="70%" />
                               <line x1="25%" y1="45%" x2="15%" y2="80%" />
                               <line x1="40%" y1="70%" x2="65%" y2="85%" />
                               <line x1="45%" y1="25%" x2="70%" y2="35%" />
                               <line x1="70%" y1="35%" x2="85%" y2="15%" />
                               <line x1="70%" y1="35%" x2="75%" y2="75%" />
                               <line x1="75%" y1="75%" x2="90%" y2="50%" />
                               <line x1="40%" y1="70%" x2="75%" y2="75%" />
                           </g>

                           <g fill="rgba(6, 182, 212, 0.6)" filter="url(#glow)">
                               <circle cx="10%" cy="20%" r="3" class="animate-pulse" />
                               <circle cx="25%" cy="45%" r="4" />
                               <circle cx="45%" cy="25%" r="3.5" />
                               <circle cx="15%" cy="80%" r="2.5" />
                               <circle cx="40%" cy="70%" r="5" />
                               <circle cx="65%" cy="85%" r="3" />
                               <circle cx="70%" cy="35%" r="4.5" />
                               <circle cx="85%" cy="15%" r="3.5" />
                               <circle cx="75%" cy="75%" r="4" />
                               <circle cx="90%" cy="50%" r="3" />
                           </g>
                       </svg>
              </div>
              <div class="relative z-10 grid lg:grid-cols-2 gap-12 items-center">
                  <div class="space-y-6 text-center lg:text-left">
                       <div class="inline-flex items-center px-4 py-1.5 bg-indigo-600/30 border border-indigo-500/40 rounded-full">
                            <span class="text-indigo-300 text-[9px] font-black uppercase tracking-[0.3em]">KREATIVITI & INOVASI</span>
                       </div>

                       <h1 class="text-5xl md:text-6xl font-black leading-tight tracking-tighter flex flex-col sm:flex-row items-center gap-6 text-center sm:text-left">

                           <div class="relative w-40 h-40 md:w-52 md:h-52 flex-shrink-0 origin-center">
                               <img src="/images/paper.png" class="absolute bottom-20 left-1/2 -translate-x-1/2 animate-paper-left w-12 h-14 pointer-events-none" style="animation-delay: 0s;">
                               <img src="/images/paper.png" class="absolute bottom-20 left-1/2 -translate-x-1/2 animate-paper-right w-10 h-12 pointer-events-none" style="animation-delay: 1.5s;">
                               <img src="/images/paper.png" class="absolute bottom-20 left-1/2 -translate-x-1/2 animate-paper-center w-10 h-13 pointer-events-none" style="animation-delay: 3s;">

                               <img src="/images/box-of-ideas.png" class="w-full h-full object-contain relative z-10 animate-box-float"
                               style="filter: drop-shadow(0 25px 25px rgba(0,0,0,0.5));">
                           </div>

                           <div class="font-serif font-bold text-5xl md:text-6xl lg:text-7xl tracking-tighter text-stone-100 leading-[0.85]">
                                Ruang <br>
                                <span class="font-normal text-amber-500 italic">Idea-Idea</span><br>
                                Baru.
                          </div>
                       </h1>
                       <p class="text-indigo-100/70 text-lg max-w-md font-medium leading-relaxed">
                            Zon interaktif untuk berkongsi rancangan inovasi, mengundi strategi terbaik, dan menyumbang maklum balas membina.
                      </p>
                      @if(!auth()->check())
                      <a href="{{ route('login') }}?intended={{ urlencode(route('user.pitches')) }}"
                          class="inline-flex items-center justify-center bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white text-xs font-black uppercase tracking-wider px-5 py-3 rounded-2xl shadow-[0_10px_20px_rgba(245,158,11,0.2)] hover:shadow-[0_12px_25px_rgba(245,158,11,0.3)] transform hover:-translate-y-0.5 transition-all duration-200 cursor-pointer">
                          💡 Hantar Idea Baru
                      </a>
                      @elseif(!(auth()->user()->is_admin ?? false) && !(auth()->user()->role === 'admin'))
                      <a href="{{ route('user.pitches') }}"
                          class="inline-flex items-center justify-center bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white text-xs font-black uppercase tracking-wider px-5 py-3 rounded-2xl shadow-[0_10px_20px_rgba(245,158,11,0.2)] hover:shadow-[0_12px_25px_rgba(245,158,11,0.3)] transform hover:-translate-y-0.5 transition-all duration-200 cursor-pointer">
                          💡 Hantar Idea Baru
                      </a>
                      @endif
                  </div>

                  <div class="space-y-12 text-white">
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="p-8 rounded-[2.5rem] border border-white/10 md:col-span-2 space-y-4" style="background-color: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px);">
                                 <div class="flex items-center gap-4 border-b border-white/10 pb-4">
                                      <div class="w-10 h-10 bg-orange-600 rounded-full flex items-center justify-center text-white text-lg shadow-md">⚡</div>
                                      <h3 class="text-orange-400 font-black text-lg uppercase tracking-widest">Terma Rujukan</h3>
                                 </div>

                                 <ul class="space-y-3.5">
                                      <li class="flex items-start gap-3 text-sm text-stone-200">
                                          <span class="text-orange-500 font-black text-lg leading-none">•</span>
                                          <div>
                                               <strong class="text-white">Insentif Interaksi Kredit</strong>
                                               Setiap satu klik undian Like yang sah akan menganugerahkan pengguna kredit secara langsung.
                                          </div>
                                      </li>
                                      <li class="flex items-start gap-3 text-sm text-stone-200">
                                           <span class="text-orange-500 font-black text-lg leading-none">•</span>
                                           <div>
                                                <strong class="text-white">Scoreboard Idea</strong>
                                                Idea dengan timbunan undian bulanan paling dominan akan dikunci masuk ke dalam *Carta Utama* untuk dibentang terus ke peringkat atasan agensi.
                                           </div>
                                      </li>
                                </ul>
                           </div>
                      </div>
                 </div>
              </div>
         </header>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
            <div class="lg:col-span-4 space-y-12">
               <section class="bg-white rounded-[40px] p-8 border border-stone-200/80 shadow-[0_20px_40px_rgba(0,0,0,0.03)] relative overflow-hidden">
                      <div class="flex items-center justify-between mb-8 pb-4 border-b border-stone-100">
                           <h3 class="text-xs font-black uppercase tracking-wider flex items-center gap-2 text-stone-800">
                                <span class="text-base text-amber-500">👑</span> Carta Idea Terbaik
                           </h3>

                           <select wire:model.live="selectedMonth"
                                class="text-[11px] font-black bg-stone-50 text-stone-700 border border-stone-200 rounded-xl py-1.5 px-3.5 outline-none focus:ring-2 focus:ring-orange-500 transition-all cursor-pointer shadow-xs">
                                @foreach($months as $num => $name)
                                    <option value="{{ $num }}" class="bg-white text-stone-800">{{ $name }}</option>
                                @endforeach
                           </select>
                      </div>

                      <div class="space-y-3.5">
                      @forelse($this->topPitches as $index => $topPitch)

                          @if($index === 0)
                              <div wire:click="viewDetails({{ $topPitch->id }})"
                                   class="group/item flex items-center justify-between p-5 bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 rounded-3xl border border-orange-400/30 shadow-[0_20px_45px_rgba(245,158,11,0.32)] transform -translate-y-1 transition-all duration-300 hover:scale-[1.01] hover:shadow-[0_25px_50px_rgba(245,158,11,0.4)] relative overflow-hidden cursor-pointer">

                                  <div class="absolute -right-10 -top-10 w-32 h-32 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>

                                  <div class="flex items-center gap-4 truncate mr-4">
                                      <div class="w-10 h-10 rounded-2xl bg-white text-orange-600 font-black text-sm flex items-center justify-center shadow-md shrink-0">
                                          🏆
                                      </div>

                                      <div class="truncate">
                                          <!--<span class="text-[9px] font-black uppercase tracking-widest text-amber-100 bg-black/20 px-2 py-0.5 rounded-md block w-max mb-1">Juara</span>-->
                                          <h4 class="text-xs md:text-sm font-black text-white uppercase tracking-wide line-clamp-2 whitespace-normal leading-tight max-w-[200px] md:max-w-[280px]">
                                              {{ $topPitch->title }}
                                          </h4>
                                          <p class="text-[10px] text-amber-50 font-bold truncate mt-1">
                                              Oleh: {{ $topPitch->user->name ?? 'N/A' }}
                                          </p>
                                      </div>
                                   </div>

                                  <!--<span class="text-xs font-mono font-black bg-white text-orange-600 px-3.5 py-2 rounded-2xl shadow-sm border border-orange-100 shrink-0">
                                      {{ $topPitch->votes_count }} <span class="text-[9px] font-sans text-orange-500 font-normal">U</span>
                                  </span>-->
                              </div>

                          @else
                              <div wire:click="viewDetails({{ $topPitch->id }})"
                                   class="group/item flex items-center justify-between p-3.5 bg-stone-50 hover:bg-white rounded-2xl transition-all duration-300 border border-stone-200/50 hover:border-orange-500/30 transform hover:-translate-y-0.5 hover:shadow-md cursor-pointer">

                                  <div class="flex items-center gap-3 truncate mr-4">
                                      <div class="w-8 h-8 rounded-xl flex items-center justify-center font-black text-xs shrink-0
                                          {{ $index === 1 ? 'bg-slate-200 text-slate-700 border border-slate-300/60' : '' }}
                                          {{ $index === 2 ? 'bg-orange-100 text-orange-600 border border-orange-200/60' : '' }}
                                          {{ $index > 2 ? 'bg-stone-200/50 text-stone-500' : '' }}">
                                          #{{ $index + 1 }}
                                      </div>

                                      <div class="truncate">
                                          <h4 class="text-xs font-black text-stone-800 uppercase tracking-wide group-hover/item:text-orange-600 transition-colors line-clamp-2 whitespace-normal leading-tight max-w-[180px] md:max-w-[240px]">
                                              {{ $topPitch->title }}
                                          </h4>
                                          <p class="text-[9px] text-stone-500 truncate mt-1 font-semibold">
                                              Oleh: {{ $topPitch->user->name ?? 'N/A' }}
                                          </p>
                                      </div>
                                  </div>

                                  <!--<span class="text-[10px] font-mono font-black bg-white text-stone-700 group-hover/item:bg-orange-50 group-hover/item:text-orange-600 px-2.5 py-1.5 rounded-xl border border-stone-200/60 shrink-0 transition-colors shadow-2xs">
                                      {{ $topPitch->votes_count }} <span class="text-[9px] font-sans text-stone-400 font-normal group-hover/item:text-orange-400">U</span>
                                  </span>-->

                              </div>
                          @endif

                      @empty
                          <p class="text-[11px] text-stone-400 font-medium text-center py-8 bg-stone-50 rounded-2xl border border-dashed border-stone-200">
                              Tiada rekod undian bulan ini.
                          </p>
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

            <div class="lg:col-span-8 space-y-6">
                <div class="flex items-center justify-between px-4">
                    <h3 class="text-sm font-black text-stone-800 uppercase tracking-widest">Senarai Idea</h3>
                    <div class="h-[1px] flex-1 bg-stone-200 mx-4"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-4">
                    @forelse($this->pitches as $pitch)
                     <div class="bg-slate-950/70 backdrop-blur-md p-8 rounded-[40px] transition-all duration-500 border border-cyan-500/10 hover:border-orange-500/40 relative group flex flex-col justify-between min-h-[380px] shadow-2xl hover:shadow-orange-950/30">
                          <div class="absolute top-0 right-0 w-32 h-32 bg-orange-500/10 rounded-full blur-2xl group-hover:bg-orange-500/20 transition-all duration-500 pointer-events-none"></div>

                          <div>
                               <div class="flex items-center gap-4 mb-8">
                                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-orange-500 to-amber-500 flex items-center justify-center text-stone-950 font-black shadow-lg tracking-tighter text-sm transform group-hover:rotate-3 transition-transform duration-300">
                                    @php
                                        $name = $pitch->user->name ?? 'N/A';
                                        $words = explode(' ', trim($name));
                                        $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                                    @endphp
                                    {{ $initials }}
                                    </div>

                                    <div class="space-y-0.5">
                                        <h4 class="text-sm font-black text-stone-100 tracking-tight">
                                            {{ $pitch->user->name ?? 'Unknown' }}
                                        </h4>
                                        <span class="text-[10px] text-slate-200/80 font-sans uppercase tracking-wider block font-semibold">
                                            {{ $pitch->user->department->name ?? 'N/A' }}
                                        </span>
                                    </div>
                              </div>

                              <div class="space-y-3">
                                  <h3 class="text-2xl font-serif text-white leading-snug group-hover:text-orange-400 transition-colors duration-300">
                                      {{ $pitch->title }}
                                  </h3>

                                  <p class="text-sm text-stone-300/80 leading-relaxed font-medium line-clamp-3">
                                      {{ $pitch->description }}
                                  </p>
                              </div>
                          </div>

                          <div class="mt-8 pt-4 border-t border-white/10 flex items-center justify-between gap-4">
                               <button type="button"
                                    wire:click="viewDetails({{ $pitch->id }})"
                                    class="text-[10px] font-black uppercase tracking-widest text-stone-300 hover:text-orange-400 transition-colors focus:outline-none flex items-center gap-1.5 cursor-pointer group/btn">
                                Lihat Butiran <span class="inline-block transform group-hover/btn:translate-x-1 transition-transform text-orange-400">&rarr;</span>
                                </button>

                                <div class="flex items-center gap-2 bg-stone-800/60 px-4 py-2 rounded-2xl border border-white/10">
                                    @if(!auth()->check())
                                    <button type="button"
                                            onclick="window.location.href = '{{ route('login') }}?intended=' + encodeURIComponent(window.location.href);"
                                            class="w-7 h-7 inline-flex items-center justify-center rounded-xl transition-all focus:outline-none text-stone-400 hover:text-rose-400"
                                            title="Log masuk untuk undi">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                                        </svg>
                                    </button>
                                    @elseif(auth()->id() !== $pitch->user_id && !(auth()->user()->is_admin ?? false) && !(auth()->user()->role === 'admin'))
                                    <button type="button"
                                            wire:click="vote({{ $pitch->id }})"
                                            class="w-7 h-7 inline-flex items-center justify-center rounded-xl transition-all focus:outline-none
                                            {{ $pitch->has_voted ? 'text-rose-400 drop-shadow-[0_0_8px_rgba(244,63,94,0.6)] scale-110' : 'text-stone-400 hover:text-rose-400' }}"
                                            title="{{ $pitch->has_voted ? 'Batal Undi' : 'Suka Idea Ini' }}">
                                        <svg class="w-4 h-4 {{ $pitch->has_voted ? 'fill-current' : '' }}" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                                        </svg>
                                    </button>
                                    @else
                                    <div class="w-7 h-7 inline-flex items-center justify-center text-stone-500 cursor-not-allowed"
                                         title="{{ auth()->id() === $pitch->user_id ? 'Idea Anda Sendiri' : 'Admin Tidak Boleh Mengundi' }}">
                                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                            <path d="M12 2a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-1V7a5 5 0 0 0-5-5zm-3 5a3 3 0 0 1 6 0v3H9V7zm3 9a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                                        </svg>
                                    </div>
                                    @endif

                                    <span class="text-xs font-mono font-black text-stone-100">
                                        {{ $pitch->votes_count }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="col-span-full py-16 flex flex-col items-center justify-center bg-white rounded-[40px] border-2 border-dashed border-stone-200 shadow-sm relative overflow-hidden">
                            <div class="relative z-10 flex flex-col items-center text-center px-6">
                                <div class="w-20 h-20 bg-[#faf7f2] rounded-[30px] flex items-center justify-center shadow-inner mb-4">
                                    <span class="text-4xl grayscale opacity-40">🌱</span>
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

    <div x-data="{ open: @entangle('showViewModal') }"
         x-show="open"
         x-transition
         class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-x-hidden overflow-y-auto"
         style="display: none;">

        <div class="fixed inset-0 bg-stone-900/40 backdrop-blur-xs transition-opacity" @click="open = false; $wire.set('showViewModal', false)"></div>

        <div class="relative w-full max-w-2xl bg-white rounded-[40px] shadow-2xl border border-stone-100 p-8 md:p-10 z-50 max-h-[90vh] overflow-y-auto space-y-6">
            @if($viewingPitch)
                <div class="flex items-start justify-between border-b border-stone-100 pb-4">
                    <div>
                        <span class="text-[9px] bg-orange-100 px-2.5 py-1 rounded-full text-orange-700 font-black uppercase tracking-widest">Butiran Cadangan</span>
                        <h2 class="text-2xl font-black text-stone-900 uppercase italic tracking-tight mt-2">
                            {{ $viewingPitch->title }}
                        </h2>
                        <p class="text-xs text-stone-400 mt-1 uppercase font-bold">
                            Oleh: {{ $viewingPitch->user->name ?? 'Ahli Kumpulan' }} ({{ $viewingPitch->user->department->name ?? 'Jabatan Am' }})
                        </p>
                    </div>
                    <button @click="open = false; $wire.set('showViewModal', false)" class="text-stone-400 hover:text-stone-700 transition-colors bg-stone-100 p-2 rounded-full">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-6">
                    <div class="space-y-2">
                        <h4 class="text-xs font-black text-stone-400 uppercase tracking-wider">Penerangan Ringkas:</h4>
                        <p class="text-stone-600 text-sm leading-relaxed bg-stone-50 p-5 rounded-[24px] whitespace-pre-line">
                            {{ $viewingPitch->description }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <h4 class="text-xs font-black text-stone-400 uppercase tracking-wider">Metodologi & Cara Pelaksanaan:</h4>
                        <p class="text-stone-700 text-sm leading-relaxed italic border-l-4 border-orange-400 pl-4 font-serif bg-orange-50/30 p-5 rounded-r-[24px] whitespace-pre-line">
                            "{{ $viewingPitch->method ?? 'Tiada butiran metodologi disediakan.' }}"
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end pt-4 border-t border-stone-100">
                    <button @click="open = false; $wire.set('showViewModal', false)" class="px-6 py-3 bg-stone-900 hover:bg-stone-800 text-white text-[10px] font-black uppercase tracking-[0.2em] rounded-2xl transition-all shadow-md">
                        Tutup Paparan
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

<x-footer />
