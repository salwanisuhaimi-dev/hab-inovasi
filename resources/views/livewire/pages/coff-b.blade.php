<?php

use App\Models\CoffeeBreakIdea;
use App\Models\CoffeeBreakSession;
use App\Models\Review;
use function Livewire\Volt\{layout, state, computed, with, usesPagination};

usesPagination();

layout('layouts.landing');

state([
  'openIndex' => null,
  'selectedDepartment' => '',
  'fromDate' => '',
  'toDate' => '',
  'page_name' => '',
  'body' => '',
  'rating' => 5,
]);

with([
    'departments' => fn() => \App\Models\Department::orderBy('name')->get(),
]);

$toggle = function ($index) {
    $this->openIndex = $this->openIndex === $index ? null : $index;
};

$sessions = computed(function () {
    return CoffeeBreakSession::query()
        ->latest('date_created')
        ->get();
});

$archiveSessions = computed(function () {
    return CoffeeBreakSession::query()
        ->with('department')
        // Tukar ke image_paths
        ->whereNotNull('image_paths')
        ->where('image_paths', '!=', '')
        ->where('image_paths', '!=', '[]')
        ->latest('date_created')
        ->get()
        ->filter(function ($session) {
            // Tukar ke image_paths
            $images = $session->image_paths;
            if (is_string($images)) {
                $images = json_decode($images, true);
            }
            return is_array($images) && count($images) > 0;
        });
});

$ideas = computed(function () {
    return CoffeeBreakIdea::query()
        ->with(['session.department'])
        ->when(!empty($this->selectedDepartment), function ($query) {
            $query->whereHas('session', function ($q) {
                $q->where('department_id', $this->selectedDepartment);
            });
        })
        ->when(!empty($this->fromDate), function ($query) {
            $query->whereDate('created_at', '>=', $this->fromDate);
        })
        ->when(!empty($this->toDate), function ($query) {
            $query->whereDate('created_at', '<=', $this->toDate);
        })
        ->latest()
        ->paginate(5); // Ubah dari limit(5) ke paginate(5)
});

$updatedSelectedDepartment = fn () => $this->resetPage();
$updatedFromDate = fn () => $this->resetPage();
$updatedToDate = fn () => $this->resetPage();

$reviews = computed(function () {
    return Review::query()
        ->with(['user'])
        ->where('page_name', 'coff-b')
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
        'page_name' => 'coff-b',
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

$loadMoreSessions = function () {
    $this->sessionLimit += 5;
};

?>

<div>

<style>
  .coffee-gradient-header {
    background: #3e2723 !important;
    background: linear-gradient(135deg, #3e2723 0%, #8d6e63 50%, #d7ccc8 100%) !important;
    position: relative;
    overflow: hidden;
  }

  .glass-card {
    background: rgba(255, 255, 255, 0.1) !important;
    backdrop-filter: blur(12px) !important;
    -webkit-backdrop-filter: blur(12px) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
  }
</style>

    <div class="min-h-screen bg-[#faf7f2] text-[#4a3728] font-sans pb-20 overflow-x-hidden">
        <x-top-nav />

        <div class="fixed top-20 -left-10 opacity-20 rotate-45 pointer-events-none">
            <span class="text-8xl">☕</span>
        </div>
        <div class="fixed bottom-10 -right-10 opacity-10 -rotate-12 pointer-events-none">
            <span class="text-[120px]">🫘</span>
        </div>

        <div class="max-w-7xl mx-auto px-6 py-10">
            <header class="coffee-gradient-header rounded-[50px] p-10 md:p-16 mb-12 shadow-2xl border-4 border-white/20">
                <div class="absolute top-0 right-0 w-96 h-96 bg-orange-500/10 rounded-full -mr-32 -mt-32 blur-[100px]"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 bg-orange-900/20 rounded-full -ml-20 -mb-20 blur-[80px]"></div>

                <div class="absolute right-10 top-1/2 -translate-y-1/2 opacity-20 pointer-events-none hidden lg:block">
                    <svg width="200" height="200" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="1">
                        <path d="M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z"></path>
                        <line x1="6" y1="1" x2="6" y2="4"></line>
                        <line x1="10" y1="1" x2="10" y2="4"></line>
                        <line x1="14" y1="1" x2="14" y2="4"></line>
                    </svg>
                </div>

                <div class="relative z-10 flex flex-col lg:flex-row items-center justify-between gap-12">
                    <div class="lg:w-2/4 space-y-6 text-center lg:text-left">
                        <div class="inline-flex items-center px-4 py-1.5 bg-orange-600/30 border border-orange-500/40 rounded-full shadow-inner">
                            <span class="text-orange-300 text-[10px] font-black uppercase tracking-[0.3em]">Membudaya Inovasi, Mengukuh Kesejahteraan</span>
                        </div>

                        <h1 class="text-5xl md:text-7xl font-black leading-[1.1] font-serif flex flex-col lg:flex-row lg:items-center gap-6">
                            <div class="relative flex-shrink-0 animate-float">
                                <div class="absolute -top-10 left-1/2 -translate-x-1/2 flex space-x-2 opacity-50 pointer-events-none">
                                    <div class="w-1 h-10 bg-white/20 rounded-full blur-md animate-steam-slow"></div>
                                    <div class="w-1.5 h-14 bg-white/10 rounded-full blur-md animate-steam-slow" style="animation-delay: 0.5s;"></div>
                                </div>

                                <img src="/images/coffee_idea.png"
                                    alt="Coffee Mug"
                                    class="w-18 h-18 md:w-36 md:h-36 object-contain drop-shadow-[0_20px_30px_rgba(0,0,0,0.6)] hover:scale-110 transition-transform duration-500"
                                    style="transform: rotate(-8deg); filter: brightness(1.05) contrast(1.1);">
                            </div>

                            <style>
                                @keyframes float {
                                    0%, 100% { transform: translateY(0px) rotate(-8deg); }
                                    50% { transform: translateY(-10px) rotate(-6deg); }
                                }
                                .animate-float {
                                    animation: float 4s ease-in-out infinite;
                                }

                                @keyframes steam-slow {
                                    0% { transform: translateY(0) scaleX(1); opacity: 0; }
                                    50% { opacity: 0.5; }
                                    100% { transform: translateY(-40px) scaleX(2); opacity: 0; }
                                }
                                .animate-steam-slow {
                                    animation: steam-slow 3s infinite ease-out;
                                }
                            </style>
                            <div class="relative">
                                <span style="color: #f06616;" class="text-5xl">Coffee Break</span>
                                <span style="color: #ede0d4;" class="text-5xl">Session (COFF-B)</span>
                            </div>
                        </h1>

                        <p style="color: #a8a29e;" class="text-lg font-medium italic max-w-sm">
                            Platform perbincangan santai dan sesi sumbang saran idea di peringkat Bahagian bagi semua warga JPA.
                        </p>
                    </div>

                    <div class="lg:w-2/4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-6 rounded-[2rem] border border-white/10 sm:col-span-2 flex items-center gap-6" style="background-color: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px);">
                          <div class="w-12 h-12 bg-orange-600 rounded-full flex-shrink-0 flex items-center justify-center text-white text-xl">✨</div>
                          <div>
                            <h3 class="text-orange-400 font-black text-sm mb-0.5 uppercase tracking-widest">Idea Inovasi</h3>
                            <p style="color: #d6d3d1;" class="text-[16px] leading-relaxed">Penambahbaikan Proses Kerja atau Inisiatif Baharu (Digital atau Bukan Digital) yang meningkatkan kecekapan, kebersanan, dan kualiti penyampaian perkhidmatan.</p>
                          </div>
                        </div>

                        <div class="p-6 rounded-[2rem] border border-white/10 sm:col-span-2 flex items-center gap-6" style="background-color: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px);">
                          <div class="w-12 h-12 bg-orange-600 rounded-full flex-shrink-0 flex items-center justify-center text-white text-xl">✨</div>
                            <div>
                              <h3 class="text-orange-400 font-black text-sm mb-0.5 uppercase tracking-widest">Idea Selain Inovasi</h3>
                              <p style="color: #d6d3d1;" class="text-[16px] leading-relaxed">Cadangan pembudayaan persekitaran kerja positif, termasuk aspek keseronokan bekerja, aktiviti riadah, kebajikan, dan pengukuhan semangat berpasukan.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="grid lg:grid-cols-12 gap-10">
                <div class="lg:col-span-4 space-y-10">
                  <section class="bg-[#efebe9] rounded-[32px] p-8 border border-stone-200"
                      x-data="{ expanded: false, defaultLimit: 5 }">

                      <h3 class="text-lg font-bold mb-6 flex items-center gap-2">Sesi Coff-B</h3>

                      <div class="space-y-4">
                          @forelse($this->sessions as $index => $session)
                              <div wire:key="session-row-{{ $session->id }}"
                                   x-show="expanded || {{ $index }} < defaultLimit"
                                   x-transition:enter="transition ease-out duration-200"
                                   x-transition:enter-start="opacity-0 transform -translate-y-2"
                                   x-transition:enter-end="opacity-100 transform translate-y-0"
                                   class="group flex items-center justify-between p-3 hover:bg-white rounded-2xl transition-all cursor-default">

                                  <div class="flex items-center gap-4">
                                      <div class="bg-white group-hover:bg-orange-600 group-hover:text-white w-12 h-12 rounded-xl flex flex-col items-center justify-center shadow-sm transition-colors shrink-0">
                                          <span class="text-[10px] font-bold">
                                              {{ \Carbon\Carbon::parse($session->date_created)->format('M') }}
                                          </span>
                                          <span class="text-lg font-black leading-none text-orange-600 group-hover:text-white">
                                              {{ \Carbon\Carbon::parse($session->date_created)->format('d') }}
                                          </span>
                                      </div>

                                      <div>
                                          <h4 class="text-sm font-bold text-stone-800">{{ $session->department->name ?? 'N/A' }}</h4>
                                          <p class="text-[10px] text-stone-500 uppercase">
                                              {{ \Carbon\Carbon::parse($session->start_time)->format('h:i A') }} • {{ $session->location }}
                                          </p>
                                      </div>
                                  </div>
                              </div>
                          @empty
                              <div class="py-12 flex flex-col items-center justify-center border-2 border-dashed border-stone-300 rounded-[24px] opacity-60">
                                  <svg class="w-10 h-10 text-stone-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.5 8V18a2 2 0 0 1-2 2h-10a2 2 0 0 1-2-2V8M15 11v-4a3 3 0 0 0-6 0v4M2 8h20" />
                                  </svg>
                                  <p class="text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 italic">Tiada Sesi Dijadualkan</p>
                              </div>
                          @endforelse
                      </div>

                      @if(count($this->sessions) > 5)
                          <div class="mt-6 flex justify-center">
                              <button @click="expanded = !expanded" type="button"
                                  class="w-full bg-white/60 hover:bg-white border border-stone-200/60 text-stone-700 text-xs font-bold py-3 px-5 rounded-[20px] transition-all cursor-pointer shadow-sm text-center flex items-center justify-center gap-2 group">

                                  <span x-text="expanded ? 'Sembunyikan Sesi' : 'Lihat Semua Sesi'"></span>

                                  <svg xmlns="http://www.w3.org/2000/svg"
                                       :class="expanded ? 'rotate-180' : ''"
                                       class="h-3.5 w-3.5 text-stone-400 group-hover:text-stone-600 transition-transform duration-300"
                                       fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                  </svg>
                              </button>
                          </div>
                      @endif
                  </section>
                </div>

                <div class="lg:col-span-8">
                    <div class="flex items-end justify-between mb-8 px-4">
                        <div>
                            <h2 class="text-3xl font-black text-stone-800 italic">Ruang Cetusan Idea</h2>
                            <p class="text-stone-500 text-sm italic font-serif">Apa yang menarik baru-baru ni...</p>
                        </div>
                        <div class="flex gap-2">
                            <div class="h-10 w-10 rounded-full bg-white border border-stone-200 flex items-center justify-center text-stone-400 cursor-pointer hover:bg-stone-50 shadow-sm">&lt;</div>
                            <div class="h-10 w-10 rounded-full bg-white border border-stone-200 flex items-center justify-center text-stone-400 cursor-pointer hover:bg-stone-50 shadow-sm">&gt;</div>
                        </div>
                    </div>

                    <div class="mb-10 flex flex-col gap-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 w-full">

                            <div class="w-full">
                                <label class="block text-[9px] font-black text-stone-400 uppercase tracking-[0.2em] mb-2 ml-1">
                                    Tapis Mengikut Bahagian
                                </label>
                                <div class="relative">
                                    <select wire:model.live="selectedDepartment" id="department_filter"
                                        class="w-full bg-white border border-stone-200 text-stone-700 text-xs font-bold py-3.5 px-5 rounded-[20px] focus:outline-none focus:border-orange-300 focus:ring-4 focus:ring-orange-50 transition-all cursor-pointer shadow-sm">
                                        <option value="">Semua Bahagian (Keseluruhan)</option>
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept->id }}" wire:key="filter-dept-{{ $dept->id }}">
                                                {{ $dept->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="w-full">
                                <label class="block text-[9px] font-black text-stone-400 uppercase tracking-[0.2em] mb-2 ml-1">
                                    Dari Tarikh
                                </label>
                                <div class="relative">
                                    <input type="date" wire:model.live="fromDate" id="from_date"
                                        class="w-full bg-white border border-stone-200 text-stone-700 text-xs font-bold py-3.5 px-5 rounded-[20px] focus:outline-none focus:border-orange-300 focus:ring-4 focus:ring-orange-50 transition-all cursor-pointer shadow-sm uppercase">
                                </div>
                            </div>

                            <div class="w-full">
                                <label class="block text-[9px] font-black text-stone-400 uppercase tracking-[0.2em] mb-2 ml-1">
                                    Hingga Tarikh
                                </label>
                                <div class="relative">
                                    <input type="date" wire:model.live="toDate" id="to_date"
                                        class="w-full bg-white border border-stone-200 text-stone-700 text-xs font-bold py-3.5 px-5 rounded-[20px] focus:outline-none focus:border-orange-300 focus:ring-4 focus:ring-orange-50 transition-all cursor-pointer shadow-sm uppercase">
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-6" wire:key="ideas-section-{{ $selectedDepartment }}-{{ $fromDate }}-{{ $toDate }}">
                        @forelse($this->ideas as $idea)
                            <div x-data="{ open: false }" wire:key="idea-card-{{ $idea->id }}" class="bg-white p-6 rounded-2xl shadow-sm hover:shadow-md transition-all border border-stone-200 flex flex-col justify-between group">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        @php
                                            $deptCode = $idea->session->department->code ?? 'N/A';
                                        @endphp
                                        <div>
                                            <h4 class="text-base font-bold text-stone-800">{{ $deptCode }}</h4>
                                        </div>
                                    </div>
                                    <span class="text-xl opacity-40 group-hover:opacity-100 transition-opacity">💡</span>
                                </div>

                                <h3 class="text-base font-black text-stone-900 mb-3 leading-snug transition-colors break-words">
                                    {{ $idea->title ?? 'N/A' }}
                                </h3>

                                <div class="flex items-center gap-2 my-3">
                                    <span class="text-[10px] font-bold text-orange-600 uppercase tracking-wider whitespace-nowrap">Cadangan:</span>
                                    <div class="w-full h-[1px] bg-stone-100"></div>
                                </div>
                                <div class="relative flex-grow mb-4">
                                    <p :class="open ? '' : 'line-clamp-3'" class="text-stone-600 text-sm leading-relaxed font-sans">
                                        {{ $idea->suggestion }}
                                    </p>
                                </div>

                                <div class="border-t border-stone-100 pt-3 flex justify-end">
                                    <button @click="open = !open" type="button" class="text-xs font-bold text-orange-600 hover:text-orange-700 transition-colors flex items-center gap-1">
                                        <span x-text="open ? 'Sembunyikan' : 'Baca Penuh'"></span>
                                        <svg xmlns="http://www.w3.org/2000/svg" :class="open ? 'rotate-180' : ''" class="h-3 w-3 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="sm:col-span-2 py-20 flex flex-col items-center justify-center bg-stone-50/50 border-2 border-dashed border-stone-200 rounded-[40px]">
                                <div class="relative mb-6">
                                    <div class="w-16 h-16 bg-white rounded-3xl flex items-center justify-center shadow-sm text-stone-300">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                        </svg>
                                    </div>
                                    <div class="absolute -top-2 -right-2 w-6 h-6 bg-orange-100 rounded-full flex items-center justify-center text-[10px]">💤</div>
                                </div>

                                <h3 class="text-stone-800 font-black uppercase tracking-widest text-xs">Belum Ada Idea Coff-B</h3>
                                <p class="text-stone-400 text-[10px] mt-2 italic uppercase tracking-tighter">Sesi sedang berlangsung atau belum ada idea direkodkan berdasarkan tapisan yang dipilih.</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-4 pt-4 border-t border-stone-100">
                        {{ $this->ideas->links(data: ['scrollTo' => false]) }}
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="gap-10 ">
      <section class="m-12"
          x-data="{
              modalOpen: false,
              activeImages: [],
              currentImageIndex: 0,
              openGallery(images) {
                  if (images && images.length > 0) {
                      this.activeImages = images;
                      this.currentImageIndex = 0;
                      this.modalOpen = true;
                  }
              },
              scrollLeft() {
                  $refs.slider.scrollBy({ left: -260, behavior: 'smooth' });
              },
              scrollRight() {
                  $refs.slider.scrollBy({ left: 260, behavior: 'smooth' });
              }
          }">

          <div class="flex items-center justify-between mb-4">
              <div>
                  <h4 class="text-3xl font-black text-stone-800 italic">Sorotan Sesi</h4>
                  <p class="text-stone-500 text-sm italic font-serif">Momen-momen dikongsi...</p>
              </div>

              @if(count($this->archiveSessions) > 4)
                  <div class="flex items-center gap-1.5">
                      <button @click="scrollLeft()" type="button" class="w-8 h-8 border border-stone-200 bg-white rounded-full flex items-center justify-center hover:bg-stone-50 transition-all cursor-pointer shadow-sm">
                          <svg class="w-3.5 h-3.5 text-stone-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                          </svg>
                      </button>
                      <button @click="scrollRight()" type="button" class="w-8 h-8 border border-stone-200 bg-white rounded-full flex items-center justify-center hover:bg-stone-50 transition-all cursor-pointer shadow-sm">
                          <svg class="w-3.5 h-3.5 text-stone-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                          </svg>
                      </button>
                  </div>
              @endif
          </div>

          <div x-ref="slider" class="flex gap-4 overflow-x-auto snap-x snap-mandatory scrollbar-none pb-4 -mx-1 px-1">
              @forelse($this->archiveSessions as $session)
                  @php
                      $sessionImages = $session->image_paths;
                      if (is_string($sessionImages)) {
                          $sessionImages = json_decode($sessionImages, true);
                      }
                      $sessionImages = is_array($sessionImages) ? array_values($sessionImages) : [];

                      $imagesJson = htmlspecialchars(json_encode($sessionImages), ENT_QUOTES, 'UTF-8');
                      $deptCode = $session->department->code ?? 'N/A';
                      $deptName = $session->department->name ?? 'N/A';

                      $day = \Carbon\Carbon::parse($session->date_created)->format('d');
                      $month = \Carbon\Carbon::parse($session->date_created)->format('M');
                      $formattedDate = \Carbon\Carbon::parse($session->date_created)->format('d M Y');

                      $coverImage = count($sessionImages) > 0 ? $sessionImages[0] : null;
                  @endphp

                  <div class="w-[220px] sm:w-[245px] shrink-0 snap-start flex flex-col group">

                      <div class="relative aspect-[4/3] bg-stone-100 rounded-[22px] overflow-hidden shadow-sm group-hover:shadow-md transition-shadow">
                          @if($coverImage)
                              <img src="/storage/{{ $coverImage }}" class="w-full h-full object-cover group-hover:scale-103 transition-transform duration-300" alt="Cover Sesi">
                          @else
                              <div class="w-full h-full flex items-center justify-center text-stone-300 text-xs">🖼️</div>
                          @endif

                          <div class="absolute top-3 left-3 bg-white rounded-lg shadow px-2 py-1 flex flex-col items-center min-w-[38px]">
                              <span class="text-sm font-black text-stone-800 leading-none">{{ $day }}</span>
                              <span class="text-[8px] font-bold text-stone-400 uppercase mt-0.5 tracking-wider">{{ $month }}</span>
                          </div>
                      </div>

                      <div class="pt-3 px-1 flex flex-col flex-grow">
                          <div class="flex items-center gap-1.5 mb-1">
                              <span class="text-[9px] font-black uppercase tracking-wider text-orange-600">
                                  Pertandingan {{ $deptCode }}
                              </span>
                              <span class="text-[9px] font-bold text-stone-400 truncate max-w-[120px]">
                                  • {{ $session->location }}
                              </span>
                          </div>

                          <h4 class="text-sm font-extrabold text-stone-900 line-clamp-1 group-hover:text-blue-600 transition-colors leading-tight">
                              {{ $deptName }}
                          </h4>

                          <p class="text-[10px] text-stone-400 font-medium mt-0.5 mb-3 line-clamp-2 leading-relaxed">
                              Sesi perkongsian idea santai bertempat di {{ $session->location }} pada {{ $formattedDate }}.
                          </p>

                          <!--<div class="mt-auto">
                              <button type="button"
                                  @click="openGallery(JSON.parse($el.getAttribute('data-images')))"
                                  data-images="{{ $imagesJson }}"
                                  class="w-full bg-stone-600 hover:bg-purple-700 text-white text-[11px] font-bold py-2.5 px-3 rounded-lg transition-all shadow-sm shadow-purple-100 text-center cursor-pointer flex items-center justify-center gap-1">
                                  <span>Lihat Gambar</span>
                              </button>
                          </div>-->
                      </div>
                  </div>
              @empty
                  <div class="w-full py-12 flex flex-col items-center justify-center bg-stone-50 border-2 border-dashed border-stone-200 rounded-[24px] opacity-70">
                      <span class="text-xl mb-1">💤</span>
                      <p class="text-[9px] font-black uppercase tracking-[0.2em] text-stone-400 italic">Tiada Rekod Gambar Sesi Ditemui</p>
                  </div>
              @endforelse
          </div>

          <div x-show="modalOpen"
               class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-955/90 backdrop-blur-sm"
               x-transition:enter="transition ease-out duration-200"
               x-transition:enter-start="opacity-0"
               x-transition:enter-end="opacity-100"
               x-transition:leave="transition ease-in duration-150"
               x-transition:leave-start="opacity-100"
               x-transition:leave-end="opacity-0"
               x-cloak
               @keydown.escape.window="modalOpen = false">

              <div class="relative w-full max-w-2xl bg-white rounded-[32px] p-6 shadow-xl overflow-hidden flex flex-col items-center"
                   @click.away="modalOpen = false">

                  <button @click="modalOpen = false" type="button" class="absolute top-4 right-4 z-10 text-stone-400 hover:text-stone-700 p-2 rounded-full hover:bg-stone-100 transition-colors">
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                  </button>

                  <h4 class="text-sm font-black text-stone-800 uppercase tracking-widest mb-4">Sorotan Gambar Sesi</h4>

                  <div class="relative w-full aspect-[4/3] bg-stone-50 rounded-2xl flex items-center justify-center overflow-hidden border border-stone-100">
                      <template x-if="activeImages.length > 0">
                          <img :src="'/storage/' + activeImages[currentImageIndex]" class="w-full h-full object-cover transition-all duration-300" alt="Gambar Sesi">
                      </template>

                      <button x-show="activeImages.length > 1"
                              @click="currentImageIndex = (currentImageIndex === 0) ? activeImages.length - 1 : currentImageIndex - 1"
                              class="absolute left-3 bg-white/90 hover:bg-white text-stone-800 p-2.5 rounded-full shadow-md hover:scale-105 transition-all">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                          </svg>
                      </button>

                      <button x-show="activeImages.length > 1"
                              @click="currentImageIndex = (currentImageIndex === activeImages.length - 1) ? 0 : currentImageIndex + 1"
                              class="absolute right-3 bg-white/90 hover:bg-white text-stone-800 p-2.5 rounded-full shadow-md hover:scale-105 transition-all">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                          </svg>
                      </button>
                  </div>

                  <div x-show="activeImages.length > 1" class="mt-3 text-xs font-bold text-stone-500 bg-stone-100 py-1 px-3 rounded-full">
                      <span x-text="currentImageIndex + 1"></span> / <span x-text="activeImages.length"></span>
                  </div>
              </div>
          </div>
      </section>

    </div>


    <x-footer />

</div>
