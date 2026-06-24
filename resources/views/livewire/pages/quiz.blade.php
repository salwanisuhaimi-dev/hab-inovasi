<?php

use function Livewire\Volt\{layout, state, computed};
use App\Models\Review;

layout('layouts.landing');

state([
    'openIndex' => null,
    'page_name' => '',
    'body' => '',
    'rating' => 5,
]);

$quizzes = computed(function () {
    return \App\Models\Program::where('category_id', 2)
        ->latest()
        ->get();
});

$reviews = computed(function () {
    return Review::query()
        ->with(['user'])
        ->where('page_name', 'quiz')
        ->latest()
        ->limit(5)
        ->get();
});

$save = function () {
    $this->validate([
      'body' => 'required|string|max:1000',
      'rating' => 'required|integer|min:1|max:5',
    ]);

    Review::create([
        'user_id' => auth()->id(),
        'body' => $this->body,
        'rating' => $this->rating,
        'page_name' => 'quiz',
    ]);

    $this->body = '';
    $this->rating = 5;

    session()->flash('success', 'Ulasan anda telah berjaya dihantar!');

    $this->dispatch('review-added');
};

$delete = function ($id) {
    $review = Review::find($id);

    if ($review && $review->user_id === auth()->id()) {
        $review->delete();

        session()->flash('success', 'Ulasan anda telah dipadam!');

        $this->dispatch('review-added');
    }
};

?>


    <div class="min-h-screen bg-[#faf7f2] text-[#4a3728] font-sans pb-20 overflow-x-hidden">
       <style>
          .quiz-gradient-header {
              background: linear-gradient(135deg, #6a1b9a 0%, #ab47bc 100%);
          }
          @keyframes float {
              0%, 100% { transform: translateY(0) rotate(-12deg); }
              50% { transform: translateY(-20px) rotate(-8deg); }
          }
          .animate-float {
              animation: float 6s ease-in-out infinite;
          }
        </style>

        <x-top-nav />

        <div class="max-w-7xl mx-auto px-6">
            <header class="my-5 quiz-gradient-header rounded-[50px] p-10 md:p-16 mb-16 shadow-2xl border-4 border-white/20 relative overflow-hidden text-white bg-gradient-to-br from-[#064e3b] to-[#022c22]">
                <div class="absolute top-0 right-0 w-96 h-96 bg-emerald-500/10 rounded-full -mr-32 -mt-32 blur-[100px]"></div>
                <div class="relative z-10 grid lg:grid-cols-2 gap-12 items-center">
                    <div class="space-y-6 text-center lg:text-left">
                        <div class="inline-flex items-center px-4 py-1.5 bg-emerald-600/30 border border-emerald-500/40 rounded-full">
                            <span class="text-emerald-300 text-[9px] font-black uppercase tracking-[0.3em]">UJI PENGETAHUAN</span>
                        </div>

                        <div class="flex flex-col lg:flex-row items-center gap-6">
                            <!--<div class="relative flex-shrink-0 animate-float">
                                <img src="/images/rocket.png" class="w-20 md:w-28 rotate-[-12deg] drop-shadow-2xl" alt="Rocket">
                            </div>-->

                            <h1 class="text-5xl md:text-6xl font-black leading-tight tracking-tighter">
                                Kuiz <span class="text-amber-500 italic">Inovasi</span>
                            </h1>
                        </div>

                        <p class="text-emerald-100/70 text-lg max-w-md font-medium leading-relaxed">
                            Uji tahap literasi teknologi dan inovasi anda melalui siri cabaran interaktif kami.
                        </p>
                    </div>

                    <div class="bg-white/5 backdrop-blur-md rounded-[2.5rem] p-8 border border-white/10 shadow-inner">
                        <h3 class="text-amber-500 font-black text-sm mb-4 uppercase tracking-widest flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Terma & Syarat
                        </h3>

                        <ul class="space-y-3">
                            <li class="flex items-start gap-3 text-sm text-emerald-50/80">
                                <span class="text-amber-500 font-bold">•</span>
                                Terbuka kepada semua warga jabatan yang sah.
                            </li>
                            <li class="flex items-start gap-3 text-sm text-emerald-50/80">
                                <span class="text-amber-500 font-bold">•</span>
                                Setiap peserta hanya dibenarkan menjawab satu kali sahaja.
                            </li>
                            <li class="flex items-start gap-3 text-sm text-emerald-50/80">
                                <span class="text-amber-500 font-bold">•</span>
                                Masa menjawab adalah terhad (5-10 minit mengikut kategori).
                            </li>
                            <li class="flex items-start gap-3 text-sm text-emerald-50/80">
                                <span class="text-amber-500 font-bold">•</span>
                                Markah lulus minimum adalah 80% untuk sijil digital.
                            </li>
                        </ul>

                        <div class="mt-6 pt-4 border-t border-white/5">
                            <p class="text-[10px] text-emerald-200/50 uppercase tracking-widest italic">
                                *Keputusan penganjur adalah muktamad.
                            </p>
                        </div>
                    </div>
                </div>
            </header>

            <style>
            @keyframes float {
                0%, 100% { transform: translateY(0px) rotate(-12deg); }
                50% { transform: translateY(-10px) rotate(-10deg); }
            }
            .animate-float {
                animation: float 4s ease-in-out infinite;
            }
            </style>

            <div class="grid lg:grid-cols-12 gap-12">
                <div class="lg:col-span-4 space-y-12">
                    <section class="bg-[#efebe9] rounded-[32px] p-8 border border-stone-200">
                        <h3 class="text-lg font-bold mb-6 flex items-center gap-2">
                            <span class="bg-[#3e2723] text-white p-1 rounded-md text-xs italic">Akan Datang</span> Kuiz
                        </h3>
                        <div class="space-y-4">
                            @forelse($this->quizzes as $quiz)
                            <div class="group flex items-center gap-4 p-3 hover:bg-white rounded-2xl transition-all cursor-default">
                                <div class="bg-white group-hover:bg-orange-600 group-hover:text-white w-12 h-12 rounded-xl flex flex-col items-center justify-center shadow-sm transition-colors">
                                    <span class="text-[10px] font-bold">
                                        OCT
                                    </span>
                                    <span class="text-lg font-black leading-none text-orange-600 group-hover:text-white">
                                        14
                                    </span>
                                </div>
                                <div>
                                <h4 class="text-sm font-bold text-stone-800">KUIZ INNOCTOBER</h4>
                                <p class="text-[10px] text-stone-500 uppercase">8.00 AM • SECARA TALIAN</p>
                                </div>
                            </div>
                            @empty
                            <div class="py-12 flex flex-col items-center justify-center border-2 border-dashed border-stone-300 rounded-[24px] opacity-60">
                                <svg class="w-10 h-10 text-stone-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.5 8V18a2 2 0 0 1-2 2h-10a2 2 0 0 1-2-2V8M15 11v-4a3 3 0 0 0-6 0v4M2 8h20" />
                                </svg>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 italic">Tiada Sesi Dijadualkan</p>
                                <p class="text-[9px] text-stone-400 mt-1 uppercase">Sila semak semula kemudian</p>
                            </div>
                            @endforelse
                        </div>
                    </section>

                    <section class="bg-white rounded-[40px] p-10 shadow-xl shadow-stone-200 border border-stone-100 relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-2 h-full bg-[#2d1b69]"></div>
                        <h2 class="text-2xl font-black italic mb-2 tracking-tighter">Leave a review</h2>
                        <p class="text-stone-400 text-[10px] mb-8 font-black uppercase tracking-[0.2em] italic">Maklum balas anda dihargai</p>
                        @guest
                            <div class="bg-[#faf7f2] rounded-2xl p-6 text-center border border-dashed border-stone-200 flex flex-col items-center py-8">
                                <div class="p-3 bg-purple-50 text-purple-600 rounded-2xl mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                </div>
                                <h4 class="text-sm font-bold text-stone-800 mb-1">Log Masuk Diperlukan</h4>
                                <p class="text-stone-500 text-xs max-w-xs mb-5 leading-relaxed">Sila log masuk ke akaun anda terlebih dahulu untuk mula berkongsi ulasan.</p>

                                <a href="{{ route('login') }}?intended={{ urlencode(route('quiz')) }}" class="inline-flex items-center gap-2 bg-[#3e2723] hover:bg-purple-700 text-white font-bold text-xs px-6 py-3 rounded-xl transition-all shadow-md shadow-stone-300">
                                    <span>Log Masuk Sekarang</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3 h-3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                    </svg>
                                </a>
                            </div>
                        @endguest

                        @auth
                        <form wire:submit.prevent="save" class="space-y-4">
                            @if (session()->has('success'))
                              <div class="bg-green-50 border border-green-200 text-green-800 text-xs font-semibold p-4 rounded-2xl mb-4 flex items-center gap-2">
                                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4 text-green-600">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                  </svg>
                                  <span>{{ session('success') }}</span>
                              </div>
                            @endif

                            <div class="flex items-center gap-2 bg-[#faf7f2] px-4 py-2.5 rounded-xl border border-stone-100">
                                <div class="w-5 h-5 rounded-full bg-purple-600 flex items-center justify-center text-[10px] text-white font-bold uppercase">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                                <span class="text-xs text-stone-600"><strong class="text-stone-900">{{ auth()->user()->name }}</strong></span>
                            </div>

                            <div>
                                <textarea
                                    wire:model="body"
                                    rows="4"
                                    placeholder="Kongsikan sesuatu..."
                                    class="w-full p-4 rounded-2xl bg-[#faf7f2] border-none focus:ring-2 focus:ring-purple-600 outline-none text-sm placeholder:text-stone-400 transition-all">
                                </textarea>

                                @error('body')
                                    <span class="text-red-500 text-xs mt-1 block px-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="bg-[#faf7f2] p-4 rounded-2xl border border-stone-100 flex flex-col gap-1">
                                 <label class="text-xs font-bold text-stone-500 uppercase tracking-wider">Berikan Penilaian:</label>
                                 <div class="flex items-center gap-1.5 mt-1">
                                      @for ($i = 1; $i <= 5; $i++)
                                      <button type="button"
                                              wire:click.prevent="$set('rating', {{ $i }})"
                                              class="transition-all duration-200 transform hover:scale-125 focus:outline-none cursor-pointer">

                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                 viewBox="0 0 24 24"
                                                 class="w-7 h-7 {{ $i <= $rating ? 'fill-amber-400 text-amber-400' : 'fill-none text-stone-300' }} transition-colors"
                                                 stroke="currentColor"
                                                 stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499c.172-.436.784-.436.956 0l2.22 4.473 4.925.711c.48.069.672.66.326 1.005l-3.567 3.477.842 4.902c.08.47-.417.83-.838.608L12 18.754l-4.418 2.322c-.42.22-.919-.139-.838-.608l.842-4.903-3.567-3.477c-.346-.345-.154-.936.326-1.005l4.925-.711 2.22-4.472Z" />
                                            </svg>
                                        </button>
                                        @endfor

                                        <span class="text-xs font-bold text-stone-600 ml-2">
                                              ({{ $rating }}/5)
                                        </span>
                                 </div>
                            </div>

                            <button type="submit" class="w-full bg-[#3e2723] hover:bg-purple-700 text-white font-bold py-4 rounded-2xl transition-all shadow-lg shadow-stone-300 flex items-center justify-center gap-2 group disabled:opacity-50" wire:loading.attr="disabled">
                                <span wire:loading.remove>Hantar</span>
                                <span wire:loading>Menghantar...</span>

                                <svg wire:loading.remove class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M14 5l7 7m0 0l-7 7m7-7H3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </form>
                        @endauth
                    </section>

                    <section class="px-4 space-y-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-black text-stone-800 uppercase tracking-widest">Komen</h3>
                            <div class="h-[1px] flex-1 bg-stone-200 mx-4"></div>
                        </div>
                        @forelse($this->reviews as $review)
                            <div class="bg-white p-6 rounded-[32px] border border-stone-100 shadow-sm hover:shadow-md transition-all group">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="w-10 h-10 rounded-xl bg-[#faf7f2] flex items-center justify-center text-stone-700 font-black text-xs shadow-inner uppercase">
                                        @php
                                            $words = explode(' ', $review->user->name);
                                            $initials = isset($words[1])
                                                ? substr($words[0], 0, 1) . substr($words[1], 0, 1)
                                                : substr($words[0], 0, 2);
                                        @endphp
                                        {{ $initials }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                          <div class="flex items-center gap-2 flex-wrap">
                                               <h4 class="text-sm font-black text-stone-800 uppercase leading-none truncate">
                                                    {{ \Illuminate\Support\Str::words($review->user->name, 2, '') }}
                                               </h4>

                                               @auth
                                                    @if($review->user_id === auth()->id())
                                                        <button type="button"
                                                                 wire:click="delete({{ $review->id }})"
                                                                 wire:confirm="Adakah anda pasti mahu memadam ulasan ini?"
                                                                 class="text-stone-400 hover:text-red-500 transition-colors focus:outline-none p-0.5 rounded"
                                                                 title="Padam Ulasan">
                                                              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                                                                   <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.34 9m-4.72 0-.34-9m9.96-3.24l-.81 10.63a2.25 2.25 0 0 1-2.24 2.25H8.55a2.25 2.25 0 0 1-2.24-2.25L5.5 5.76M19.5 5.76A10.5 10.5 0 0 0 4.5 5.76M10.5 3.5h3" />
                                                              </svg>
                                                         </button>
                                                      @endif
                                                @endauth
                                            </div>
                                            <span class="text-[9px] text-stone-400 font-bold uppercase tracking-widest block mt-1">
                                                  {{ $review->created_at->diffForHumans() }}
                                            </span>
                                    </div>
                                    <div class="flex gap-0.5 text-orange-500 text-lg leading-none select-none">
                                        @for ($i = 1; $i <= 5; $i++)
                                            @if ($i <= $review->rating)
                                                <span class="text-amber-400">★</span>
                                            @else
                                                <span class="text-stone-200">★</span>
                                            @endif
                                        @endfor
                                    </div>
                                </div>

                                <p class="text-stone-600 text-sm leading-relaxed italic border-l-2 border-orange-100 pl-4">
                                    "{{ $review->body }}"
                                </p>
                            </div>
                        @empty
                            <div class="text-center py-8 bg-[#faf7f2]/50 border border-dashed border-stone-200 rounded-[32px] p-6">
                                <p class="text-stone-400 text-xs font-medium italic">Belum ada ulasan untuk halaman ini. Jadilah yang pertama memberikan maklum balas!</p>
                            </div>
                        @endforelse
                    </section>
                </div>

                <div class="lg:col-span-8 space-y-16">
                    <div class="grid grid-cols-1 gap-8 p-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-black text-stone-800 uppercase tracking-widest">Senarai Kuiz</h3>
                            <div class="h-[1px] flex-1 bg-stone-200 mx-4"></div>
                        </div>
                        @forelse($this->quizzes as $quiz)
                        <div class="group bg-white rounded-[50px] p-4 shadow-sm hover:shadow-2xl transition-all duration-500 border border-stone-50">
                            <div class="bg-[#faf7f2] rounded-[42px] p-8 md:p-10 flex flex-col md:flex-row items-center gap-10">
                                <div class="relative flex-shrink-0">
                                    <div class="w-32 h-32 bg-[#63249d] rounded-[40px] flex items-center justify-center shadow-2xl rotate-[-6deg] group-hover:rotate-0 transition-all duration-500">
                                        <span class="text-6xl">🧠</span>
                                    </div>
                                    <div class="absolute -bottom-1 -right-3 bg-[#d651e0] text-white text-[10px] font-black px-4 py-1.5 rounded-full uppercase tracking-widest shadow-lg border-4 border-[#faf7f2]">
                                        New
                                    </div>
                                </div>

                                <div class="flex-1 space-y-6">
                                    <div class="space-y-2">
                                        <h4 class="text-3xl font-black text-[#2d1b69] uppercase italic leading-[0.9] tracking-tighter">
                                            Kuiz Inovasi Digital 2026
                                        </h4>
                                        <p class="text-sm text-[#a08cc5] font-medium italic">
                                            Uji pengetahuan transformasi digital jabatan.
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-8">
                                        <div class="flex flex-col leading-tight">
                                            <span class="text-[10px] font-black text-[#c3b4df] uppercase tracking-widest">Masa</span>
                                            <span class="text-2xl font-black text-[#4a3728]">5</span>
                                            <span class="text-[10px] font-black text-[#4a3728] uppercase">Minit</span>
                                        </div>

                                        <a href="#" class="flex-1 max-w-[240px] inline-flex items-center justify-center gap-4 bg-[#8b31ff] hover:bg-[#3e2723] text-white py-5 rounded-[30px] text-xs font-black uppercase tracking-[0.2em] transition-all shadow-xl shadow-purple-200">
                                            Jawab <span class="text-orange-400 text-xl">⚡</span>
                                        </a>
                                    </div>
                                </div>

                            </div>
                        </div>
                        @empty
                        <div class="col-span-full py-20 flex flex-col items-center justify-center bg-white rounded-[50px] border-2 border-dashed border-purple-100 relative overflow-hidden group">
                            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-purple-50 rounded-full blur-[80px] opacity-50 group-hover:opacity-100 transition-opacity"></div>
                            <div class="relative z-10 flex flex-col items-center text-center">
                                <div class="w-24 h-24 bg-[#faf7f2] rounded-[35px] flex items-center justify-center shadow-inner mb-6 rotate-[-6deg] group-hover:rotate-0 transition-transform duration-500">
                                    <span class="text-5xl grayscale opacity-40 group-hover:grayscale-0 group-hover:opacity-100 transition-all">🧩</span>
                                </div>
                                <h3 class="text-2xl font-black text-[#2d1b69] uppercase italic tracking-tighter leading-none">
                                    Tiada Cabaran <br> <span class="text-[#d651e0]">Buat Masa Ini</span>
                                </h3>
                                <p class="mt-4 text-xs text-[#a08cc5] font-medium italic max-w-[280px] leading-relaxed">
                                    Tiada kuiz untuk disertai buat masa sekarang. Sila semak semula nanti!
                                </p>

                                <button wire:click="$refresh" class="mt-8 px-8 py-3 bg-[#faf7f2] border border-purple-100 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] text-[#63249d] hover:bg-white hover:shadow-lg transition-all">
                                    Semak Semula ↻
                                </button>
                            </div>

                            <div class="absolute top-10 left-10 text-purple-200 opacity-20 animate-bounce">❓</div>
                            <div class="absolute bottom-10 right-10 text-purple-200 opacity-20 animate-pulse">❓</div>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
    <x-footer />
