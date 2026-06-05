<?php

use App\Models\Archive;
use function Livewire\Volt\{layout, state, computed};

layout('layouts.landing');

state([
    'search' => '', 
    'year' => '',
    'showDetailModal' => false,
    'viewingArchive' => null,
    ]);

$archives = computed(function () {
    return Archive::query()
        ->with(['department', 'competitions'])
        ->when($this->search, function ($query) {
            $query->where('project_name', 'like', '%' . $this->search . '%');
        })
        ->when($this->year, function ($query) {
                $query->whereHas('competitions', function ($q) {
                $q->whereIn('archive_id', function($subquery) {
                    $subquery->select('archive_id')
                        ->from('archive_competition')
                        ->groupBy('archive_id')
                        ->havingRaw('MIN(year) = ?', [$this->year]);
                });
            });
        })
        ->latest()
        ->get();
});

$viewDetails = function ($id) {
    $this->viewingArchive = Archive::with(['department', 'competitions'])->find($id);
    $this->showDetailModal = true;
};


?>

<div class="min-h-screen bg-[#f8fafc]">
    <x-top-nav />
    <div class="max-w-7xl mx-auto px-6 pt-10">
    <header class="relative overflow-hidden rounded-[50px] p-10 md:p-16 mb-12 shadow-2xl border-4 border-white/20 bg-gradient-to-br from-[#1e3a8a] via-[#1e40af] to-[#1e3a8a]">
        <div class="absolute top-0 right-0 w-96 h-96 bg-blue-400/10 rounded-full -mr-32 -mt-32 blur-[100px]"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-blue-900/40 rounded-full -ml-20 -mb-20 blur-[80px]"></div>

        <div class="absolute right-10 top-1/2 -translate-y-1/2 opacity-10 pointer-events-none hidden lg:block">
            <svg width="250" height="250" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1">
                <path d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-7.714 2.143L11 21l-2.286-6.857L1 12l7.714-2.143L11 3z"></path>
            </svg>
        </div>

        <div class="relative z-10 flex flex-col lg:flex-row items-center justify-between gap-12">
            <div class="lg:w-1/2 space-y-6 text-center lg:text-left">
                <div class="inline-flex items-center px-4 py-1.5 bg-blue-600/30 border border-blue-400/30 rounded-full shadow-inner">
                    <span class="text-blue-200 text-[10px] font-black uppercase tracking-[0.3em]">Pentas Kecemerlangan JPA</span>
                </div>
    
                <h1 class="text-5xl md:text-6xl font-black leading-[1.1] text-white tracking-tighter">
                    Arkib <br>
                    <span class="text-blue-400 italic">Inovasi</span> 
                </h1>

                <p class="text-blue-100/70 text-lg font-medium max-w-xl leading-relaxed">
                    Mengiktiraf idea kreatif dan projek digital yang memberikan impak tinggi kepada penyampaian perkhidmatan jabatan.
                </p>

                <div class="mt-10 max-w-md relative group">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama projek..." 
                        class="w-full pl-12 pr-6 py-4 rounded-2xl border-white/10 bg-white/10 backdrop-blur-md text-white placeholder-blue-200/50 focus:ring-2 focus:ring-blue-400 focus:bg-white/20 transition-all shadow-lg">
                    <svg class="w-6 h-6 text-blue-300 absolute left-4 top-1/2 -translate-y-1/2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>

            <div class="lg:w-1/2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-6 rounded-[2rem] border border-white/10 bg-white/5 backdrop-blur-md shadow-xl transition-transform hover:-translate-y-1">
                    <div class="w-10 h-10 bg-blue-600 rounded-2xl flex items-center justify-center text-white font-bold mb-4 shadow-lg rotate-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    </div>
                    <h3 class="text-blue-300 font-black text-sm mb-1 uppercase tracking-wider">Impak Digital</h3>
                    <p class="text-white/80 text-sm leading-relaxed">Transformasi proses kerja melalui teknologi terkini.</p>
                </div>
    
                <div class="p-6 rounded-[2rem] border border-white/10 bg-white/5 backdrop-blur-md shadow-xl transition-transform hover:-translate-y-1">
                    <div class="w-10 h-10 bg-emerald-500 rounded-2xl flex items-center justify-center text-white font-bold mb-4 shadow-lg -rotate-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    </div>
                    <h3 class="text-emerald-400 font-black text-sm mb-1 uppercase tracking-wider">Kualiti Diiktiraf</h3>
                    <p class="text-white/80 text-sm leading-relaxed">Projek yang telah melalui penilaian juri profesional.</p>
                </div>

                <div class="p-6 rounded-[2rem] border border-white/10 bg-gradient-to-r from-blue-600/20 to-transparent backdrop-blur-md sm:col-span-2 flex items-center gap-6 shadow-xl">
                    <div class="w-14 h-14 bg-white/10 rounded-full flex-shrink-0 flex items-center justify-center text-blue-300 text-2xl border border-white/10">
                        🏆
                    </div>
                    <div>
                        <h3 class="text-blue-200 font-black text-sm mb-0.5 uppercase tracking-[0.2em]">Hab Rujukan Inovasi</h3>
                        <p class="text-white/70 text-[14px]">Himpunan kejayaan warga JPA dalam menerajui perubahan sektor awam.</p>
                    </div>
                </div>
            </div>
        </div>
    </header>
    </div>

    <section class="max-w-7xl mx-auto px-6 mt-12">
        <div class="flex flex-col gap-5">
            <div class="flex flex-wrap justify-center items-center gap-3 mt-2">
                <button wire:click="$set('year', '')" 
                    class="min-w-[100px] px-6 py-3 rounded-xl text-sm font-bold transition-all duration-200 
                    {{ $year === '' ? 'bg-blue-600 text-white shadow-lg shadow-blue-100' : 'bg-white text-slate-600 border border-gray-100 hover:bg-slate-50' }}">
                    Semua
                </button>

                @foreach(['2025', '2024', '2023', '2022'] as $y)
                <button wire:click="$set('year', '{{ $y }}')" 
                    class="min-w-[100px] px-6 py-3 rounded-xl text-sm font-bold transition-all duration-200 
                    {{ $year === $y ? 'bg-blue-600 text-white shadow-lg shadow-blue-100' : 'bg-white text-slate-600 border border-gray-100 hover:bg-slate-50' }}">
                    {{ $y }}
                </button>
                @endforeach
            </div>
        </div>
    </section>

    <main class="max-w-7xl mx-auto px-6 py-20">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
            @forelse($this->archives as $archive)
                <div class="group bg-white rounded-[2.5rem] overflow-hidden border border-gray-100 shadow-sm hover:shadow-2xl transition-all duration-500">
                    <div class="relative h-60 overflow-hidden bg-gray-100">
                        @if($archive->thumbnail)
                            <img src="{{ asset('storage/' . $archive->thumbnail) }}" class="w-full h-full object-cover group-hover:scale-110 transition duration-700">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-300">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                        @endif
                        
                        <div class="absolute top-4 right-4 px-4 py-1.5 bg-white/90 backdrop-blur rounded-full text-[10px] font-black text-blue-600 shadow-sm">
                            {{ $archive->competitions->first()->pivot->year ?? 'N/A' }}
                        </div>
                    </div>

                    <div class="p-8">
                        <span class="text-[10px] font-bold text-blue-500 uppercase tracking-widest">
                            {{ $archive->department->name ?? 'Jabatan' }}
                        </span>
                        <h3 class="text-xl font-bold text-gray-900 mt-2 mb-4 leading-tight group-hover:text-blue-600 transition min-h-[3rem]">
                            {{ $archive->project_name }}
                        </h3>
                        
                        <p class="text-xs text-gray-500 line-clamp-2 mb-6">
                            {{ $archive->description }}
                        </p>
                        
                        <div class="flex items-center justify-between pt-6 border-t border-gray-50">
                            <button wire:click="viewDetails({{ $archive->id }})" class="text-sm font-bold text-gray-400 hover:text-blue-600 transition flex items-center group/btn">
                                Lebih Lanjut
                                <svg class="w-4 h-4 ml-2 transform group-hover/btn:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-20">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-50 rounded-full mb-6 text-gray-300">
                         <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <p class="text-gray-400 font-medium tracking-tight uppercase text-xs">Tiada projek ditemui untuk kriteria tersebut.</p>
                </div>
            @endforelse
        </div>
    </main>

    <x-footer />

@if($showDetailModal && $viewingArchive)
<div class="fixed inset-0 z-[150] overflow-y-auto" 
     x-data="{ show: false }" 
     x-init="setTimeout(() => show = true, 50)"
     x-show="show"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100">
    
    <div class="flex items-center justify-center min-h-screen p-4 md:p-10">
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-md transition-opacity" 
             wire:click="$set('showDetailModal', false)"></div>

        <div class="relative bg-white rounded-[3.5rem] shadow-2xl max-w-6xl w-full overflow-hidden transform transition-all flex flex-col md:flex-row min-h-[650px]">
            
            <div class="md:w-1/2 bg-slate-50 p-8 flex flex-col gap-6" 
                 x-data="{ 
                    activeImage: 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?q=80&w=2026',
                    dummyImages: [
                        'https://images.unsplash.com/photo-1460925895917-afdab827c52f?q=80&w=2026',
                        'https://images.unsplash.com/photo-1551288049-bbbda536339a?q=80&w=2070',
                        'https://images.unsplash.com/photo-1551033406-611cf9a28f67?q=80&w=1974',
                        'https://images.unsplash.com/photo-1504868584819-f8e90526354a?q=80&w=2070',
                        'https://images.unsplash.com/photo-1454165833767-027ffea9e77b?q=80&w=2070'
                    ]
                 }">
                
                <div class="relative flex-1 bg-white rounded-[2.5rem] overflow-hidden border border-slate-200 shadow-inner group">
                    <img :src="activeImage" 
                         class="w-full h-full object-cover transition-all duration-700 group-hover:scale-105">
                    
                    <div class="absolute top-6 left-6">
                        <span class="px-4 py-2 bg-white/90 backdrop-blur text-blue-600 text-[10px] font-black uppercase tracking-widest rounded-2xl shadow-sm border border-white/50">
                            {{ $viewingArchive->department->name ?? 'Bahagian Digital' }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-5 gap-4">
                    <template x-for="(img, index) in dummyImages" :key="index">
                        <button @click="activeImage = img" 
                                class="aspect-square relative rounded-2xl overflow-hidden border-2 transition-all duration-300 transform hover:-translate-y-1"
                                :class="activeImage === img ? 'border-blue-500 shadow-lg ring-4 ring-blue-50' : 'border-transparent opacity-70 hover:opacity-100'">
                            <img :src="img" class="w-full h-full object-cover">
                        </button>
                    </template>
                </div>
            </div>

            <div class="md:w-1/2 p-10 md:p-14 bg-white flex flex-col relative max-h-[90vh] overflow-y-auto">
                
                <button wire:click="$set('showDetailModal', false)" 
                        class="absolute top-8 right-8 p-3 text-slate-300 hover:text-slate-900 hover:bg-slate-100 rounded-full transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>

                <div class="mb-10 text-left">
                    <h2 class="text-4xl font-black text-slate-900 leading-tight tracking-tighter uppercase">
                        {{ $viewingArchive->project_name }}
                    </h2>
                    <div class="flex items-center gap-2 mt-4 text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span class="text-sm font-bold italic">{{ $viewingArchive->group_name }}</span>
                    </div>
                </div>

                <div class="mb-12">
                    <h4 class="text-[10px] font-black text-slate-300 uppercase tracking-[0.3em] mb-4 text-left">Ringkasan Eksekutif</h4>
                    <p class="text-slate-600 leading-relaxed text-sm bg-slate-50 p-8 rounded-[2rem] border border-slate-100 italic">
                        "{{ $viewingArchive->description ?? 'Tiada maklumat deskripsi tambahan disediakan untuk projek ini.' }}"
                    </p>
                </div>

                <div class="mb-10 text-left">
                    <h4 class="text-[10px] font-black text-slate-300 uppercase tracking-[0.3em] mb-8">Garis Masa Pencapaian</h4>
                    <div class="space-y-8 relative before:absolute before:inset-y-0 before:left-4 before:w-px before:bg-slate-100">
                        @forelse($viewingArchive->competitions as $comp)
                            <div class="relative pl-12 group/item">
                                <div class="absolute left-0 w-8 h-8 bg-white border-2 border-slate-200 rounded-full flex items-center justify-center z-10 group-hover/item:border-blue-500 transition-colors">
                                    <div class="w-2.5 h-2.5 bg-slate-200 rounded-full group-hover/item:bg-blue-500 transition-colors"></div>
                                </div>
                                
                                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-transparent hover:border-slate-100 hover:shadow-sm transition-all">
                                    <div>
                                        <h5 class="text-sm font-black text-slate-800 uppercase tracking-tight">{{ $comp->name }}</h5>
                                        <span class="text-[10px] font-black text-blue-500 uppercase">{{ $comp->pivot->year }}</span>
                                    </div>
                                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-amber-50 text-amber-700 rounded-xl text-[10px] font-black uppercase tracking-widest border border-amber-100 shadow-sm">
                                        🏆 {{ $comp->pivot->achievement }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="pl-12 text-slate-400 text-xs italic">Tiada rekod pertandingan ditemui.</div>
                        @endforelse
                    </div>
                </div>

                <div class="mt-auto pt-10 flex items-center justify-between border-t border-slate-50">
                    <div class="flex gap-2">
                         <span class="px-3 py-1 bg-slate-100 rounded text-[9px] font-bold text-slate-500 uppercase">{{ $viewingArchive->track ?? 'Inovasi Digital' }}</span>
                    </div>

                    @if($viewingArchive->video_link)
                    <a href="{{ $viewingArchive->video_link }}" target="_blank" 
                       class="inline-flex items-center gap-3 px-8 py-4 bg-slate-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-600 transition-all shadow-xl shadow-slate-200 active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.828a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                        Buka Bahan Projek
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endif

</div>