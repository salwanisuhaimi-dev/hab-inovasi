<?php

use App\Models\Competition;
use function Livewire\Volt\{layout, state, mount};

layout('layouts.landing');

state([
    'competition' => null,
    'openIndex' => null
]);

mount(function (Competition $competition) {
    $this->competition = $competition;
});

$toggle = function ($index) {
    $this->openIndex = $this->openIndex === $index ? null : $index;
};

?>

<div class="min-h-screen bg-gray-50">
    <x-top-nav />

    <div class="bg-gray-50 min-h-screen pb-20">
        <header class="py-20 bg-white border-b border-gray-100 px-6">
            <div class="max-w-6xl mx-auto flex flex-col lg:flex-row items-center justify-between gap-10">
                <div class="lg:w-2/3 text-center lg:text-left">
                    <span class="text-blue-600 font-bold text-xs uppercase tracking-[0.3em]">Pertandingan</span>
                    <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mt-2 leading-tight">
                        {{ $competition->name }}
                    </h2>
                    <p class="text-gray-500 mt-4 max-w-xl mx-auto lg:mx-0 leading-relaxed text-lg">
                        {{ $competition->description }}
                    </p>
                </div>

                <div class="lg:w-1/3 flex justify-center lg:justify-end">
                    <div class="relative group">
                        <div class="absolute inset-0 bg-blue-100 blur-2xl rounded-full opacity-50"></div>
                        <div class="relative bg-white p-6 rounded-[2.5rem] border border-gray-100 shadow-[0_20px_50px_rgba(0,0,0,0.08)] animate-bounce duration-[4000ms] flex items-center gap-4 min-w-[260px]">
                            <div class="w-14 h-14 bg-yellow-400 rounded-2xl flex items-center justify-center text-3xl shadow-lg shadow-yellow-200">
                                💡
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[10px] font-black uppercase text-blue-500 tracking-[0.2em] mb-1">Status Terkini</span>
                                <span class="text-lg font-bold text-gray-900 leading-none">Idea Inovasi</span>
                                <span class="text-xs font-semibold text-emerald-500 mt-1.5 flex items-center gap-1">
                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                                    Terbuka Sekarang
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </header>
        
        <div class="max-w-7xl mx-auto px-6 my-8">
            <div class="grid lg:grid-cols-3 gap-8 mb-16">
                <div class="lg:col-span-2 bg-white p-10 rounded-[3rem] shadow-sm border border-gray-100">
                    <h2 class="text-2xl font-black text-[#002966] mb-6 flex items-center gap-3">
                        <span class="text-3xl">🔍</span> Pengenalan
                    </h2>
                    <div class="space-y-4 text-gray-600 leading-relaxed text-justify">
                        <p>
                            {{ $competition->introduction }}
                        </p>
                    </div>
                </div>
            
                <div class="bg-gradient-to-br from-blue-900 to-blue-800 p-10 rounded-[3rem] text-white flex flex-col justify-center shadow-xl">
                    <div class="text-sm font-black uppercase tracking-[0.2em] text-blue-300 mb-4 text-center">Kitaran Acara</div>
                    <div class="text-6xl font-black text-center mb-2">{{ $competition->cycle }}</div>
                    <div class="text-xl font-bold text-center text-blue-100 uppercase tracking-widest">Tahun Sekali</div>
                </div>
            </div>

            <div class="mb-20">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-black text-[#002966] uppercase tracking-tighter">Tujuan</h2>
                    <p class="text-gray-500 mt-2 italic font-medium">"{{ $competition->objectives['main'] ?? 'Meningkatkan mutu penyampaian perkhidmatan kerajaan.' }}"</p>
                </div>
            
                <div class="flex flex-wrap justify-center gap-6 text-center">
                    @foreach($competition->objectives['items'] as $item)
                        <div class="group bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-2 transition-all duration-300 w-full sm:w-80 flex flex-col items-center">
                            <div class="flex flex-col items-center text-center">
                                <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center text-4xl mb-6 group-hover:scale-110 transition-transform">
                                    {{ $item['icon'] ?? '💡' }}
                                </div>
                                <h4 class="font-black text-gray-900 text-lg uppercase mb-3 tracking-tight">
                                    {{ $item['title'] }}
                                </h4>
                                <p class="text-gray-500 text-sm leading-relaxed">
                                    {{ $item['desc'] }}
                                </p>
                            </div>
                        </div>
                    @endforeach            
                </div>
            </div>

            <div class="grid lg:grid-cols-2 gap-10 mb-20">
                <div class="bg-white rounded-[3rem] p-10 border border-gray-100 shadow-sm">
                    <h3 class="text-xl font-black text-blue-900 mb-8 uppercase tracking-widest flex items-center gap-3">
                        <span class="text-2xl">📝</span> Syarat Penyertaan
                    </h3>
                    <ul class="space-y-6">
                        @foreach($competition->requirements as $req)
                            <li class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-12 h-12 rounded-2xl flex items-center justify-center {{ $req['is_allowed'] ? 'bg-green-50 text-green-500' : 'bg-red-50 text-red-500' }}">
                                    @if($req['is_allowed'])
                                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    @else
                                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    @endif
                                </div>                        
                                <div>
                                    <p class="font-bold text-gray-800">{{ $req['title'] }}</p>
                                    <p class="text-sm text-gray-500">{{ $req['desc'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="bg-blue-900 rounded-[3rem] p-10 text-white shadow-2xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 opacity-10 text-8xl translate-x-10 translate-y-10">✨</div>
                    <h3 class="text-xl font-black text-blue-300 mb-8 uppercase tracking-widest">Kategori & Bidang</h3>
                    <div class="grid sm:grid-cols-2 gap-8">
                        <div class="space-y-4">
                            <h4 class="text-amber-500 font-bold uppercase text-xs tracking-widest">Kategori</h4>
                            <div class="space-y-3">
                                @forelse($competition->categories as $cat)
                                    <div class="bg-emerald-500/20 border border-emerald-400/20 p-4 rounded-2xl">
                                        <p class="font-bold text-sm">{{ $cat }}</p>
                                        <p class="text-[10px] text-emerald-200 uppercase">{{ $cat }}</p>
                                    </div>
                                @empty
                                    <div class="p-4 rounded-2xl border border-dashed border-gray-700/50 flex flex-col items-center justify-center">
                                        <p class="text-[10px] text-gray-500 uppercase font-black tracking-widest italic">Tiada Kategori Ditetapkan</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h4 class="text-amber-500 font-bold uppercase text-xs tracking-widest">Bidang</h4>
                            <div class="space-y-3">
                            @forelse($competition->tracks as $track)
                                <div class="bg-white/10 p-4 rounded-2xl">
                                    <p class="font-bold text-sm">{{ $track }}</p>
                                </div>
                            @empty
                                <div class="p-4 rounded-2xl border border-dashed border-gray-700/50 flex flex-col items-center justify-center">
                                    <p class="text-[10px] text-gray-500 uppercase font-black tracking-widest italic">Tiada Bidang Ditetapkan</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-20 bg-white rounded-[4rem] p-12 border border-gray-100 shadow-sm">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-black text-[#002966]">GANJARAN & PENGIKTIRAFAN</h2>
                <div class="w-20 h-1.5 bg-amber-500 mx-auto mt-4 rounded-full"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-end max-w-5xl mx-auto">
                <div class="order-2 md:order-1 flex flex-col items-center">
                    <div class="text-4xl mb-4">🥈</div>
                    <div class="w-full bg-gray-50 rounded-t-[3rem] p-8 border border-gray-200 border-b-0 text-center">
                        <h3 class="font-black text-gray-500 text-xs uppercase mb-2">Naib Johan</h3>
                        <p class="text-3xl font-black text-gray-800">{{ $competition->prizes['naib_johan'] }}</p>
                        <p class="text-[10px] text-white-100 mt-4 font-bold uppercase tracking-widest">Sijil Penghargaan & Penyertaan</p>
                    </div>
                </div>

                <div class="order-1 md:order-2 flex flex-col items-center">
                    <div class="text-6xl mb-4 animate-bounce">🥇</div>
                    <div class="w-full bg-gradient-to-b from-amber-400 to-amber-600 rounded-t-[3rem] p-10 text-center shadow-2xl">
                        <h3 class="font-black text-amber-950 text-xs uppercase mb-2">Johan Keseluruhan</h3>
                        <p class="text-4xl font-black text-white">RM {{ $competition->prizes['johan'] }}</p>
                        <p class="text-[10px] text-amber-100 mt-4 font-bold uppercase tracking-widest">Piala Pusingan & Iringan</p>
                    </div>
                </div>

                <div class="order-3 flex flex-col items-center">
                    <div class="text-4xl mb-4">🥉</div>
                    <div class="w-full bg-gray-50 rounded-t-[3rem] p-8 border border-gray-200 border-b-0 text-center">
                        <h3 class="font-black text-gray-500 text-xs uppercase mb-2">Tempat Ketiga</h3>
                        <p class="text-3xl font-black text-gray-800">RM {{ $competition->prizes['ketiga'] }}</p>
                        <p class="text-[10px] text-white-100 mt-4 font-bold uppercase tracking-widest">Sijil Penghargaan & Penyertaan</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-12 pt-12 border-t border-gray-100">
                <!--<div class="text-center p-4 bg-gray-50 rounded-3xl">
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Inovasi Terbaik</p>
                    <p class="font-black text-blue-900">RM 2,000</p>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-3xl">
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Hibrid Terbaik</p>
                    <p class="font-black text-blue-900">RM 2,000</p>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-3xl">
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Dokumentasi</p>
                    <p class="font-black text-blue-900">RM 1,500</p>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-3xl">
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Persembahan</p>
                    <p class="font-black text-blue-900">RM 1,500</p>
                </div>-->
            </div>
        </div>

        <div class="flex flex-col md:flex-row items-center justify-between gap-6 pt-10 border-t border-gray-200">
            <div class="flex items-center gap-4 text-gray-400">
                <!--<div class="text-sm font-medium italic">Kongsi info ini:</div>
                <div class="flex gap-2">
                    <button class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all shadow-sm">f</button>
                    <button class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center hover:bg-emerald-500 hover:text-white transition-all shadow-sm">w</button>
                </div>-->
            </div>
            
            <button onclick="window.scrollTo({top: 0, behavior: 'smooth'})" class="group flex items-center gap-3 px-8 py-3 bg-gray-900 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-blue-600 transition-all shadow-xl">
                Kembali ke Atas
                <span class="group-hover:-translate-y-1 transition-transform">↑</span>
            </button>
        </div>
    </div>
</div>

<style>
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-20px); }
    }
    .animate-float {
        animation: float 5s ease-in-out infinite;
    }
    /* Font serif khusus untuk 'Inovasi' */
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@1,900&display=swap');
    .font-serif {
        font-family: 'Playfair Display', serif;
    }
</style>

    <x-footer />
</div>