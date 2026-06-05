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
        <span class="text-blue-600 font-bold text-xs uppercase tracking-[0.3em]">Bantuan</span>
        <h2 class="text-4xl font-bold text-gray-900 mt-2">Soalan <span class="text-blue-600 italic">Lazim</span></h2>
        <p class="text-gray-500 mt-4 max-w-xl mx-auto">Segala jawapan kepada persoalan anda mengenai platform Hab Inovasi Jabatan.</p>
    </header>

    <main class="max-w-3xl mx-auto px-6 py-20">
        <div class="space-y-4">
            @php
                $faqs = [
                    ['q' => 'Apa itu Hab Inovasi?', 'a' => 'Hab Inovasi adalah platform pusat untuk warga jabatan berkongsi, mendokumentasikan, dan meneroka projek-projek inovasi digital.'],
                    ['q' => 'Siapa yang boleh menghantar idea?', 'a' => 'Semua warga jabatan yang berdaftar boleh menghantar idea atau projek inovasi mereka melalui modul Hantar Idea.'],
                    ['q' => 'Adakah projek saya akan disemak?', 'a' => 'Ya, setiap projek yang dihantar akan melalui proses semakan oleh Admin sebelum dipaparkan di Arkib Inovasi.'],
                    ['q' => 'Bagaimana cara untuk menyertai Kuiz?', 'a' => 'Anda boleh terus ke modul Kuiz di Homepage dan pilih kuiz yang sedang aktif untuk menguji pengetahuan anda.'],
                ];
            @endphp

            @foreach($faqs as $index => $faq)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <button 
                        wire:click="toggle({{ $index }})"
                        class="w-full flex items-center justify-between p-6 text-left hover:bg-gray-50 transition"
                    >
                        <span class="font-bold text-gray-900">{{ $faq['q'] }}</span>
                        <svg class="w-5 h-5 text-blue-600 transform {{ $openIndex === $index ? 'rotate-180' : '' }} transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    
                    <div class="{{ $openIndex === $index ? 'block' : 'hidden' }} px-6 pb-6 text-gray-600 leading-relaxed border-t border-gray-50 pt-4">
                        {{ $faq['a'] }}
                    </div>
                </div>
            @endforeach
        </div>
    </main>

    <x-footer />
</div>