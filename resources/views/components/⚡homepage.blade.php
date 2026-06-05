<?php

use App\Models\Archive;
use function Livewire\Volt\{state};

// Ini bahagian "Otak" (PHP)
state(['archives' => fn() => Archive::with('user')->latest()->get()]);

?>

<div class="min-h-screen bg-gray-50">
    <nav class="bg-white shadow-sm p-4 flex justify-between items-center">
        <h1 class="text-xl font-bold text-blue-600">HAB INOVASI</h1>
        <div class="space-x-4">
            @auth
                <a href="{{ url('/dashboard') }}" class="text-sm text-gray-700">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="text-sm text-gray-700">Login</a>
                <a href="{{ route('register') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">Register</a>
            @endauth
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-12 px-4">
        <header class="mb-10">
            <h2 class="text-3xl font-extrabold text-gray-900">Arkib Inovasi</h2>
            <p class="mt-2 text-gray-600">Koleksi idea kreatif warga organisasi kita.</p>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($archives as $item)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-900">{{ $item->title }}</h3>
                        <p class="text-gray-600 mt-2 text-sm">{{ Str::limit($item->description, 120) }}</p>
                        
                        <div class="mt-6 flex justify-between items-center border-t pt-4">
                            <button class="flex items-center space-x-2 text-pink-500 hover:scale-110 transition">
                                <span>❤️</span>
                                <span class="font-bold">{{ $item->likes_count }}</span>
                            </button>
                            <span class="text-xs text-gray-400 italic">Oleh: {{ $item->user->name }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($archives->isEmpty())
            <div class="text-center py-20">
                <p class="text-gray-400">Belum ada inovasi di dalam arkib buat masa ini.</p>
            </div>
        @endif
    </div>
</div>