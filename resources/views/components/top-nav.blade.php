    <nav class="sticky top-0 z-50 bg-white/90 backdrop-blur-md border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <a href="{{ route('home') }}">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-600 rounded-xl shadow-lg shadow-blue-200 flex items-center justify-center text-white font-bold text-xl">H</div>
                    <h1 class="text-xl font-black text-gray-900 tracking-tight italic">HAB<span class="text-blue-600">INOVASI</span></h1>
                </div>
            </a>
            <div class="flex items-center justify-between w-full">
                <div class="flex-1"></div>

                <div class="flex items-center space-x-8">
                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                        <button @mouseover="open = true"
                                class="flex items-center text-sm font-bold transition uppercase py-2 {{ request()->routeIs('pertandingan.*') ? 'text-blue-600' : 'text-gray-600 hover:text-blue-600' }}">
                                <span>Pertandingan</span>
                                <svg class="ml-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>

                                @if(request()->routeIs('pertandingan.*'))
                                    <span class="absolute bottom-0 left-0 w-full h-0.5 bg-blue-600 rounded-full"></span>
                                @endif
                        </button>

                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 transform scale-95"
                             x-transition:enter-end="opacity-100 transform scale-100"
                             class="absolute z-50 mt-2 w-64 rounded-xl shadow-xl bg-white ring-1 ring-black ring-opacity-5 py-2 overflow-hidden"
                             @mouseleave="open = false"
                             style="display: none;">
                            @foreach(\App\Models\Competition::all() as $competition)
                                <a href="{{ route('overview', $competition->slug) }}" class="block px-4 py-3 text-m font-bold text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition">{{ $competition->name }}</a>
                            @endforeach
                        </div>
                    </div>
                    <a href="{{ route('info') }}"
                       class="text-sm font-bold uppercase transition-all duration-300 relative py-2 {{ request()->is('info') ? 'text-blue-600' : 'text-gray-600 hover:text-blue-600' }}">
                       Info
                       @if(request()->routeIs('info'))
                            <span class="absolute bottom-0 left-0 w-full h-0.5 bg-blue-600 rounded-full"></span>
                       @endif
                    </a>
                    <a href="{{ route('publication') }}"
                       class="text-sm font-bold uppercase transition-all duration-300 relative py-2 {{ request()->is('publication') ? 'text-blue-600' : 'text-gray-600 hover:text-blue-600' }}">
                       Penerbitan
                       @if(request()->is('publication'))
                            <span class="absolute bottom-0 left-0 w-full h-0.5 bg-blue-600 rounded-full"></span>
                       @endif


                    <a href="{{ route('faq') }}"
                       class="text-sm font-bold uppercase transition-all duration-300 relative py-2 {{ request()->routeIs('faq') ? 'text-blue-600' : 'text-gray-600 hover:text-blue-600' }}">
                       FAQ
                       @if(request()->routeIs('faq'))
                            <span class="absolute bottom-0 left-0 w-full h-0.5 bg-blue-600 rounded-full"></span>
                       @endif
                    </a>

                    @include('partials.nav-contact-dropdown')

            </div>

            <div class="flex-1 flex justify-end">
            @auth
                <a href="{{ route('re-route') }}"
                    class="px-6 py-2 bg-blue-600 text-white rounded-full font-bold text-sm hover:bg-blue-700 transition shadow-lg shadow-blue-200">
                    Dashboard
                </a>
            @else
                <a href="{{ route('login') }}"
                    class="px-6 py-2 bg-gray-900 text-white rounded-full font-bold text-sm hover:bg-gray-800 transition shadow-lg">
                    Log Masuk
                </a>
            @endauth
            </div>
        </div>
    </nav>
