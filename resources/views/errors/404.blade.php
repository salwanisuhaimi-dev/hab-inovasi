<!DOCTYPE html>
<html lang="ms" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemui</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full bg-slate-50 flex items-center justify-center p-6 font-sans">

    <div class="max-w-md w-full text-center">
        <h1 class="text-9xl font-black text-blue-900/10 tracking-tight animate-pulse">404</h1>

        <div class="w-20 h-20 bg-blue-50 text-blue-600 rounded-3xl flex items-center justify-center text-3xl mx-auto -mt-12 mb-6 shadow-sm border border-blue-100">
            🔍
        </div>

        <h2 class="text-2xl font-black text-gray-900 mb-2">Halaman Tidak Ditemui</h2>
        <p class="text-sm text-gray-500 mb-8 leading-relaxed">
            Maaf, kami tidak dapat menemui halaman atau program inovasi yang anda cari. Pautan tersebut mungkin telah luput atau dialihkan.
        </p>

        <div class="space-y-3">
            @auth
                <a href="{{ auth()->user()->is_admin ? route('admin.dashboard') : route('user.dashboard') }}"
                   class="w-full bg-blue-900 hover:bg-blue-950 text-white font-bold text-xs uppercase tracking-widest py-4 px-6 rounded-2xl text-center shadow-md shadow-blue-900/10 hover:shadow-lg transition-all block">
                    Kembali ke Dashboard
                </a>
            @else
                <a href="/"
                   class="w-full bg-blue-900 hover:bg-blue-950 text-white font-bold text-xs uppercase tracking-widest py-4 px-6 rounded-2xl text-center shadow-md shadow-blue-900/10 hover:shadow-lg transition-all block">
                    Kembali ke Halaman Utama
                </a>
            @endauth
        </div>
    </div>

</body>
</html>
