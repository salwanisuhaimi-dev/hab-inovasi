<?php

use function Livewire\Volt\{layout, state};

layout('layouts.landing');

state(['openIndex' => null]);

$toggle = function ($index) {
    $this->openIndex = $this->openIndex === $index ? null : $index;
};

?>

<div class="min-h-screen bg-gray-50">
    <x-top-nav />

    <header class="py-20 bg-white border-b border-gray-100 text-center">
        <span class="text-blue-600 font-bold text-xs uppercase tracking-[0.3em]">Informasi</span>
        <h2 class="text-4xl font-bold text-gray-900 mt-2">Tentang <span class="text-blue-600 italic">Kami</span></h2>
        <p class="text-gray-500 mt-4 max-w-xl mx-auto">Segala informasi mengenai kami dan perkhidmatan yang disediakan.</p>
    </header>

    <main class="max-w-7xl mx-auto px-6 lg:px-8 py-20">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center mb-24">

            <div class="space-y-6">
                <div class="inline-flex items-center gap-2 bg-blue-50 text-blue-700 px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest">
                    Profil Korporat
                </div>

                <h1 class="text-4xl md:text-5xl font-black text-stone-900 tracking-tight leading-tight">
                    Memacu Kecemerlangan, <br>
                    <span class="text-blue-600 italic font-serif">Membina Masa Depan</span>
                </h1>

                <p class="text-stone-600 text-base leading-relaxed">
                    Kami komited dalam menyampaikan perkhidmatan yang berintegriti tinggi serta memupuk inovasi yang mampan demi kesejahteraan organisasi dan masyarakat secara menyeluruh. Dengan tadbir urus yang kukuh, setiap langkah kami berlandaskan matlamat yang jelas.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-4">
                    <div class="bg-stone-50 border-l-4 border-blue-600 p-6 rounded-r-2xl shadow-sm">
                        <span class="block text-xs font-bold text-blue-600 uppercase tracking-wider mb-1">Visi Kami</span>
                        <p class="text-stone-900 font-extrabold text-lg leading-snug">
                            Menjadi peneraju organisasi global yang inklusif, berintegriti, dan berteknologi tinggi menjelang 2030.
                        </p>
                    </div>

                    <div class="bg-stone-50 border-l-4 border-stone-800 p-6 rounded-r-2xl shadow-sm">
                        <span class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Misi Kami</span>
                        <p class="text-stone-700 text-sm leading-relaxed">
                            Melaksanakan tadbir urus terbaik melalui solusi inovatif, memperkasakan modal insan, dan menyampaikan impak positif yang berterusan.
                        </p>
                    </div>
                </div>
            </div>

            <div class="relative overflow-visible p-6 justify-self-center lg:justify-self-end w-full max-w-lg">

                <div class="relative z-10 rounded-[2.5rem] overflow-hidden shadow-2xl border-8 border-white">
                    <img src="{{ asset('images/jpa.jpg') }}"
                         class="w-full h-[450px] object-cover object-top transition duration-700 hover:scale-105"
                         alt="Gambar Korporat">
                </div>
            </div>

        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12 pt-12 border-t border-stone-200">

            <div class="lg:col-span-1 space-y-4">
                <span class="text-xs font-black text-blue-600 uppercase tracking-widest block">Matlamat Strategik</span>
                <h2 class="text-3xl font-black text-stone-900 tracking-tight">Objektif <br>Organisasi</h2>
                <p class="text-stone-500 text-sm leading-relaxed">
                    Rangka kerja teras yang digubal khusus untuk memastikan setiap operasi mencapai piawaian kualiti tertinggi yang telah ditetapkan.
                </p>
            </div>

            <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-6">

                <div class="bg-white border border-stone-100 p-8 rounded-3xl shadow-sm hover:shadow-md transition duration-300">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-6 font-bold text-lg">
                        01
                    </div>
                    <h4 class="text-lg font-bold text-stone-900 mb-2">Pengurusan Strategik</h4>
                    <p class="text-stone-500 text-sm leading-relaxed">
                        Merancang, melaksana, dan memantau dasar-dasar utama organisasi bagi memastikan keselarasan dengan hala tuju negara.
                    </p>
                </div>

                <div class="bg-white border border-stone-100 p-8 rounded-3xl shadow-sm hover:shadow-md transition duration-300">
                    <div class="w-12 h-12 bg-stone-50 text-stone-800 rounded-2xl flex items-center justify-center mb-6 font-bold text-lg">
                        02
                    </div>
                    <h4 class="text-lg font-bold text-stone-900 mb-2">Pembangunan Digital</h4>
                    <p class="text-stone-500 text-sm leading-relaxed">
                        Memacu transformasi digital perkhidmatan korporat melalui integrasi sistem automasi moden yang selamat dan utuh.
                    </p>
                </div>

                <div class="bg-white border border-stone-100 p-8 rounded-3xl shadow-sm hover:shadow-md transition duration-300">
                    <div class="w-12 h-12 bg-stone-50 text-stone-800 rounded-2xl flex items-center justify-center mb-6 font-bold text-lg">
                        03
                    </div>
                    <h4 class="text-lg font-bold text-stone-900 mb-2">Pengukuhan Integriti</h4>
                    <p class="text-stone-500 text-sm leading-relaxed">
                        Menegakkan kawal selia dan audit dalaman secara telus bagi mengekalkan tahap kepercayaan pemegang taruh (stakeholders).
                    </p>
                </div>

                <div class="bg-white border border-stone-100 p-8 rounded-3xl shadow-sm hover:shadow-md transition duration-300">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-6 font-bold text-lg">
                        04
                    </div>
                    <h4 class="text-lg font-bold text-stone-900 mb-2">Optimasi Fungsi</h4>
                    <p class="text-stone-500 text-sm leading-relaxed">
                        Menilai prestasi berkala setiap sektor demi memperkemas proses rantaian kerja harian agar kekal responsif dan cekap.
                    </p>
                </div>

            </div>
        </div>
    </main>
    <x-footer />
</div>
