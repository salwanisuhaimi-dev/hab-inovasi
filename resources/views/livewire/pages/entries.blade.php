<?php

use function Livewire\Volt\{layout, state, computed};

layout('layouts.landing');

state(['openIndex' => null]);

$programs = computed(function () {
    return \App\Models\Program::where('category_id', 1)
        ->latest()
        ->get();
});

?>

<style>
    .quiz-gradient-header {
        background: linear-gradient(135deg, #1b9a4c 0%, #51bc47 100%);
    }
    @keyframes float {
        0%, 100% { transform: translateY(0) rotate(-12deg); }
        50% { transform: translateY(-20px) rotate(-8deg); }
    }
    .animate-float {
        animation: float 6s ease-in-out infinite;
    }
</style>

    <div class="min-h-screen bg-[#faf7f2] text-[#4a3728] font-sans pb-20 overflow-x-hidden">
        <x-top-nav />

        <div class="max-w-7xl mx-auto px-6 py-10"> 
            <header class="relative overflow-hidden rounded-[50px] p-10 md:p-16 mb-12 shadow-2xl border-4 border-white/20 bg-gradient-to-br from-[#064e3b] via-[#059669] to-[#064e3b]">
                <div class="absolute top-0 right-0 w-96 h-96 bg-emerald-400/10 rounded-full -mr-32 -mt-32 blur-[100px]"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 bg-emerald-900/40 rounded-full -ml-20 -mb-20 blur-[80px]"></div>

                <div class="relative z-10 flex flex-col lg:flex-row items-center justify-between gap-12">
                    <div class="lg:w-1/2 space-y-6 text-center lg:text-left">
                        <div class="inline-flex items-center px-4 py-1.5 bg-emerald-800/30 border border-emerald-400/30 rounded-full shadow-inner">
                            <span class="text-emerald-200 text-[10px] font-black uppercase tracking-[0.3em]">Hab Transformasi Kreatif</span>
                        </div>
        
                        <h1 class="text-5xl md:text-7xl font-black leading-[1.1] text-white tracking-tighter">
                            Penyertaan <br>
                            <span class="text-emerald-300 italic">Inovasi.</span>
                        </h1>

                        <p class="text-emerald-50/70 text-lg font-medium max-w-xl leading-relaxed">
                            Menerajui perubahan melalui penyampaian idea kreatif dan solusi organisasi yang efektif untuk masa depan jabatan.
                        </p>
                    </div>

                    <div class="lg:w-1/2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-6 rounded-[2rem] border border-white/10 bg-white/5 backdrop-blur-md shadow-xl transition-transform hover:-translate-y-1">
                            <div class="w-10 h-10 bg-emerald-600 rounded-2xl flex items-center justify-center text-white font-bold mb-4 shadow-lg rotate-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                            </div>
                            <h3 class="text-emerald-300 font-black text-sm mb-1 uppercase tracking-wider">Langkah Mudah</h3>
                            <p class="text-white/80 text-sm leading-relaxed">Isi borang atas talian dan lampirkan kertas kerja anda.</p>
                        </div>
        
                        <div class="p-6 rounded-[2rem] border border-white/10 bg-white/5 backdrop-blur-md shadow-xl transition-transform hover:-translate-y-1">
                            <div class="w-10 h-10 bg-amber-500 rounded-2xl flex items-center justify-center text-white font-bold mb-4 shadow-lg -rotate-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <h3 class="text-amber-400 font-black text-sm mb-1 uppercase tracking-wider">Kriteria Penilaian</h3>
                            <p class="text-white/80 text-sm leading-relaxed">Fokus kepada impak, keaslian, dan kebolehlaksanaan idea.</p>
                        </div>

                        <div class="p-6 rounded-[2rem] border border-white/10 bg-gradient-to-r from-emerald-600/20 to-transparent backdrop-blur-md sm:col-span-2 flex items-center gap-6 shadow-xl">
                            <div class="w-14 h-14 bg-white/10 rounded-full flex-shrink-0 flex items-center justify-center text-emerald-300 text-2xl border border-white/10">
                            🌱
                            </div>
                            <div>
                                <h3 class="text-emerald-200 font-black text-sm mb-0.5 uppercase tracking-[0.2em]">Sumbangkan Aspirasi</h3>
                                <p class="text-white/70 text-[14px]">Setiap idea kecil anda adalah pemacu kepada transformasi besar jabatan.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            <div class="grid lg:grid-cols-12 gap-12">          
                <div class="lg:col-span-4 space-y-12">
                    <section class="bg-[#efebe9] rounded-[32px] p-8 border border-stone-200">
                        <h3 class="text-lg font-bold mb-6 flex items-center gap-2">
                            <span class="bg-[#1b9a4c] text-white p-1 rounded-md text-xs italic">Akan Datang</span> Pertandingan
                        </h3>
                        <div class="space-y-4">
                            @forelse($this->programs as $program)
                            <div class="group flex items-center gap-4 p-3 hover:bg-white rounded-2xl transition-all cursor-default">
                                <div class="bg-white group-hover:bg-green-600 group-hover:text-white w-12 h-12 rounded-xl flex flex-col items-center justify-center shadow-sm transition-colors">
                                    <span class="text-[10px] font-bold">
                                    {{ \Carbon\Carbon::parse($program->start_date)->format('M') }}
                                    </span>
                                    <span class="text-lg font-black leading-none text-green-600 group-hover:text-white">
                                    {{ \Carbon\Carbon::parse($program->start_date)->format('d') }}
                                    </span>                            
                                </div>
                                <div>
                                <h4 class="text-sm font-bold text-stone-800">{{ Str::limit($program->title, 30, '...') }}</h4>
                                <p class="text-[10px] text-stone-500 uppercase">{{\Carbon\Carbon::parse($program->start_time)->format('h:i A')}} • {{$program->location}}</p>
                                </div>
                            </div>
                            @empty
                            <div class="py-12 flex flex-col items-center justify-center border-2 border-dashed border-stone-300 rounded-[24px] opacity-60">       
                                <svg class="w-10 h-10 text-stone-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.5 8V18a2 2 0 0 1-2 2h-10a2 2 0 0 1-2-2V8M15 11v-4a3 3 0 0 0-6 0v4M2 8h20" />
                                </svg>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-stone-500 italic">Tiada Pertandingan Dijadualkan</p>
                                <p class="text-[9px] text-stone-400 mt-1 uppercase">Sila semak semula kemudian</p>
                            </div>
                            @endforelse
                        </div>
                    </section>

                    <section class="bg-white rounded-[40px] p-10 shadow-xl shadow-stone-200 border border-stone-100 relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-2 h-full bg-[#1b9a4c]"></div>
                        <h2 class="text-2xl font-black italic mb-2 tracking-tighter">Leave a review</h2>
                        <p class="text-stone-400 text-[10px] mb-8 font-black uppercase tracking-[0.2em] italic">Maklum balas anda dihargai</p>
                    
                        <div class="space-y-4">
                            <input type="text" placeholder="Nama Penuh" class="w-full p-5 rounded-2xl bg-[#faf7f2] border-none text-sm outline-none focus:ring-2 focus:ring-orange-600 transition-all">
                            <textarea rows="4" placeholder="Kongsikan pengalaman anda..." class="w-full p-5 rounded-2xl bg-[#faf7f2] border-none text-sm outline-none focus:ring-2 focus:ring-orange-600 transition-all"></textarea>
                            <button class="w-full bg-[#1b9a4c] py-5 rounded-2xl text-white text-[10px] font-black uppercase tracking-[0.3em] shadow-lg hover:bg-[#2d1b69] transition-all">Hantar Sekarang</button>
                        </div>
                    </section>

                    <section class="px-4 space-y-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-black text-stone-800 uppercase tracking-widest">Komen</h3>
                            <div class="h-[1px] flex-1 bg-stone-200 mx-4"></div>
                        </div>
                        @for ($i = 0; $i < 2; $i++)
                        <div class="bg-white/60 p-6 rounded-[30px] border border-stone-100 relative">
                            <div class="flex gap-4 items-center mb-3">
                                <div class="w-8 h-8 rounded-lg bg-green-100 text-black-700 flex items-center justify-center font-black text-[10px]">NA</div>
                                <span class="text-[10px] font-black uppercase text-stone-800 tracking-tight">Nurul Ain</span>
                            </div>
                            <p class="text-xs text-stone-500 leading-relaxed italic">"Idea yang bernas untuk tingkatkan kualiti kerja!"</p>
                        </div>
                        @endfor
                    </section>
                </div>

                <div class="lg:col-span-8 space-y-16"> 
                    <div class="grid grid-cols-1 gap-8 p-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-black text-stone-800 uppercase tracking-widest">Senarai Pertandingan</h3>
                            <div class="h-[1px] flex-1 bg-stone-200 mx-4"></div>
                        </div>
                        @forelse($this->programs as $program)
                        <div class="group bg-white rounded-3xl p-2 shadow-sm hover:shadow-xl transition-all duration-500 border border-gray-100">
                            <div class="bg-gradient-to-br from-[#064e3b] to-[#022c22] rounded-2xl p-8 flex flex-col md:flex-row items-center gap-8">
                                <div class="relative flex-shrink-0">
                                    <div class="w-24 h-24 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl flex items-center justify-center shadow-inner transition-transform duration-500 group-hover:scale-105">
                                        <svg class="w-12 h-12 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"></path>
                                        </svg>
                                    </div>
                                    <div class="absolute -top-2 -right-2 bg-yellow-500 text-emerald-950 text-[9px] font-bold px-3 py-1 rounded-md uppercase tracking-tighter shadow-sm">
                                        Terbuka
                                    </div>
                                </div>

                                <div class="flex-1 space-y-4">
                                    <div>
                                        <h4 class="text-xl md:text-2xl font-semibold text-white leading-snug tracking-tight">
                                            {{ $program->title }}
                                        </h4>
                                        <div class="w-12 h-1 bg-yellow-500 mt-3 rounded-full opacity-80"></div>
                                    </div>
            
                                    <p class="text-sm text-emerald-100/70 font-light leading-relaxed max-w-xl">
                                        Inisiatif strategik bagi memacu budaya inovasi dan kecemerlangan penyampaian perkhidmatan organisasi.
                                    </p>

                                    <div class="flex items-center pt-2">
                                        <a href="#" class="inline-flex items-center justify-center gap-3 bg-white hover:bg-yellow-500 text-[#064e3b] hover:text-white px-8 py-3 rounded-xl text-xs font-bold uppercase tracking-widest transition-all duration-300">
                                            Sertai Sekarang
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                            </svg>
                                        </a>
                                    </div>
                                </div>

                            </div>
                        </div>                        
                        @empty
                        <div class="col-span-full py-20 flex flex-col items-center justify-center bg-white rounded-3xl border-2 border-dashed border-emerald-100 relative overflow-hidden group">
                            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-emerald-50 rounded-full blur-[80px] opacity-50 group-hover:opacity-100 transition-opacity"></div>
                            <div class="relative z-10 flex flex-col items-center text-center">
                                <div class="w-24 h-24 bg-emerald-50 rounded-2xl flex items-center justify-center shadow-inner mb-6 transition-transform duration-500 group-hover:scale-110">
                                    <svg class="w-12 h-12 text-emerald-600/40 group-hover:text-emerald-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                    </svg>
                                </div>

                                <h3 class="text-xl font-bold text-emerald-900 tracking-tight leading-tight">
                                    Tiada Program Aktif <br> 
                                    <span class="text-emerald-600 font-medium">Buat Masa Ini</span>
                                </h3>
        
                                <p class="mt-3 text-sm text-gray-500 max-w-[320px] leading-relaxed">
                                    Terima kasih atas minat anda. Sila semak semula dalam masa terdekat untuk peluang penyertaan baru.
                                </p>

                                <button wire:click="$refresh" class="mt-8 px-10 py-3 bg-white border border-emerald-200 rounded-xl text-xs font-bold uppercase tracking-widest text-emerald-700 hover:bg-emerald-600 hover:text-white hover:border-emerald-600 shadow-sm transition-all duration-300">
                                    Semula ↻
                                </button>
                            </div>
                            <div class="absolute top-10 left-10 text-emerald-100 opacity-50 animate-bounce">
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path></svg>
                            </div>
                            <div class="absolute bottom-10 right-10 text-emerald-100 opacity-50 animate-pulse">
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                            </div>
                        </div>                        
                        @endforelse        
                    </div>
                </div>  
            </div>
        </div>
    </div>

