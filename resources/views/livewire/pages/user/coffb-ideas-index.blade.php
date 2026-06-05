<?php

use App\Models\CoffeeBreakSession;
use App\Models\CoffeeBreakIdea;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CoffeeBreakIdeasExport;
use function Livewire\Volt\{layout, state, mount, usesFileUploads};

layout('layouts.app');
usesFileUploads();

state([
    'sessionId' => null,
    'location' => '',
    'session_date' => '',
    'session_dept' => '',
    'session_month' => '',
    'showModal' => false,
    'modalCategory' => 'inovasi',
    'newIdeas' => [],
    'showViewModal' => false,
    'selectedIdea' => null,

]);

$viewIdea = function ($id) {
    $this->selectedIdea = CoffeeBreakIdea::find($id);
    $this->showViewModal = true;
};

$closeViewModal = function () {
    $this->showViewModal = false;
    $this->selectedIdea = null;
};

mount(function (CoffeeBreakSession $session) {

    $this->sessionId = $session->id;
    $this->location = $session->location;
    $this->session_date = $session->date_created;
    $this->session_dept = $session->department->name;
    $this->session_month = \Carbon\Carbon::parse($session->date_created)
        ->locale('ms') // Forces Carbon to use Malay for this line
        ->translatedFormat('F');});

$openAddModal = function ($category) {
    $this->modalCategory = $category;
    $this->newIdeas = [];
    $this->addEmptyRow();
    $this->showModal = true;
};

$addEmptyRow = function () {
    $this->newIdeas[] = [
        'is_digital' => 'digital',
        'title' => '',
        'suggestion' => '',
        'action_taken' => '',
    ];
};

$removeRow = function ($index) {
    unset($this->newIdeas[$index]);
    $this->newIdeas = array_values($this->newIdeas);
};

$saveMultipleIdeas = function () {
    $this->validate([
        'newIdeas.*.title' => 'required|min:5',
    ]);

    $currentSession = CoffeeBreakSession::find($this->sessionId);

    if (!$currentSession) {
        session()->flash('error', 'Sesi tidak ditemui.');
        return;
    }

    foreach ($this->newIdeas as $idea) {

        $currentSession->ideas()->create([
            'category' => $this->modalCategory,
            'is_digital' => $idea['is_digital'],
            'title' => $idea['title'],
            'suggestion' => $idea['suggestion'],
            'action_taken' => $idea['action_taken'],
        ]);
    }

    $this->showModal = false;
    $this->newIdeas = [];
    session()->flash('message', 'Idea berjaya ditambah!');

    return $this->redirect(route('user.coffb.ideas', $this->sessionId), navigate: true);
};

$deleteIdea = function ($id) {
    CoffeeBreakIdea::find($id)->delete();
    session()->flash('message', 'Idea telah dipadam.');
    return $this->redirect(route('user.coffb.ideas', $this->sessionId), navigate: true);

};

$printIdeas = function () {
    $printUrl = route('user.coffb.ideas-print', ['session' => $this->sessionId]);

    $this->js("window.open('{$printUrl}', '_blank');");
};

$exportIdeas = function () {
    $fileName = 'Laporan Sesi Coff-B ' . $this->session_dept . ' ' . $this->session_month . '.xlsx';

    return Excel::download(new CoffeeBreakIdeasExport($this->sessionId), $fileName);
};

?>

<div class="p-8 max-w-7xl mx-auto">
    <div class="mb-6 flex justify-between items-end">
        <div>
            <nav class="flex mb-4 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                <a href="{{ route('user.coffb') }}" wire:navigate class="hover:text-blue-600 transition">Sesi Coff-B</a>
                <span class="mx-2">/</span>
                <span class="text-slate-900">Urus Idea Sesi</span>
            </nav>
        </div>
    </div>

    <div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div>
                <h2 class="text-3xl font-black text-slate-900 tracking-tight uppercase">Sesi Coff-B</h2>
                <p class="text-slate-500 font-medium italic text-sm">
                    Tarikh: {{ \Carbon\Carbon::parse($session_date)->format('d F Y') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button wire:click="printIdeas" class="px-8 py-3 bg-slate-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-800 transition shadow-xl shadow-slate-200">
                Cetak Idea
            </button>
            <button wire:click="exportIdeas" class="px-8 py-3 bg-slate-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-800 transition shadow-xl shadow-slate-200">
                Muat Turun Excel
            </button>
            <a href="{{ route('user.coffb') }}" wire:navigate class="px-5 py-3 bg-white rounded-2xl border border-slate-200 text-slate-600 text-xs font-black uppercase tracking-widest hover:bg-slate-50 transition shadow-sm">
                &larr; Kembali
            </a>
        </div>
    </div>
    @if (session()->has('message'))
        <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 rounded-2xl border border-emerald-100 font-bold text-sm flex items-center">
            <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            {{ session('message') }}
        </div>
    @endif



    <div class="grid grid-cols-1 xl:grid-cols-2 gap-10">

        {{-- TABLE 1: IDEA INOVASI --}}
        <div class="space-y-4">
            <div class="flex justify-between items-center bg-blue-600 p-6 rounded-[2rem] shadow-xl shadow-blue-100">
                <h3 class="text-white font-black uppercase tracking-widest text-xs flex items-center gap-2">
                    💡 Idea Inovasi
                </h3>
                <button wire:click="openAddModal('inovasi')" class="bg-white text-blue-600 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-50 transition">
                    + Tambah
                </button>
            </div>
            {{-- Pastikan file partials/ideas-table wujud --}}
            @include('livewire.pages.user.partials.ideas-table', ['category' => 'inovasi', 'sessionId' => $sessionId])
        </div>

        {{-- TABLE 2: IDEA BUKAN INOVASI --}}
        <div class="space-y-4">
            <div class="flex justify-between items-center bg-amber-500 p-6 rounded-[2rem] shadow-xl shadow-amber-100">
                <h3 class="text-white font-black uppercase tracking-widest text-xs flex items-center gap-2">
                    📝 Selain Inovasi
                </h3>
                <button wire:click="openAddModal('selain_inovasi')" class="bg-white text-amber-600 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-amber-50 transition">
                    + Tambah
                </button>
            </div>
            @include('livewire.pages.user.partials.ideas-table', ['category' => 'selain_inovasi', 'sessionId' => $sessionId])
        </div>
    </div>

    @if($showModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="$set('showModal', false)"></div>
            <div class="relative bg-white rounded-[3rem] shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto p-12 transform transition-all">

                <div class="mb-8">
                    <h3 class="text-2xl font-black uppercase tracking-tighter italic">Tambah Idea ({{ str_replace('_', ' ', $modalCategory) }})</h3>
                    <p class="text-slate-400 text-[10px] font-black uppercase tracking-[0.2em] mt-1 italic">Catat setiap cadangan yang dibincangkan dalam sesi.</p>
                </div>

                @if ($errors->any())
                    <div class="mb-6 p-4 bg-red-50 rounded-2xl border border-red-100">
                        <div class="text-[10px] font-black uppercase tracking-widest text-red-600 mb-2">Sila betulkan ralat berikut:</div>
                        <ul class="list-disc list-inside text-xs text-red-500 font-bold">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form wire:submit.prevent="saveMultipleIdeas" class="space-y-6">
                    @foreach($newIdeas as $index => $idea)
                        <div class="p-8 bg-slate-50 rounded-[2.5rem] border border-slate-100 relative group" wire:key="idea-row-{{ $index }}">
                            @if(count($newIdeas) > 1)
                                <button type="button" wire:click="removeRow({{ $index }})" class="absolute -top-3 -right-3 w-8 h-8 bg-white text-red-500 rounded-full shadow-md flex items-center justify-center hover:bg-red-500 hover:text-white transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                            @endif

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="md:col-span-2 flex gap-8 items-center border-b border-slate-200 pb-4">
                                    <span class="bg-slate-900 text-white w-7 h-7 rounded-xl flex items-center justify-center text-[10px] font-black shadow-lg shadow-slate-200 italic">#{{ $index+1 }}</span>
                                    <label class="flex items-center gap-3 cursor-pointer group/radio">
                                        <input type="radio" wire:model="newIdeas.{{ $index }}.is_digital" value="digital" class="w-5 h-5 text-blue-600 border-slate-300 focus:ring-blue-500">
                                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">Digital</span>
                                    </label>
                                    <label class="flex items-center gap-3 cursor-pointer group/radio">
                                        <input type="radio" wire:model="newIdeas.{{ $index }}.is_digital" value="non-digital" class="w-5 h-5 text-blue-600 border-slate-300 focus:ring-blue-500">
                                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">Bukan Digital</span>
                                    </label>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Tajuk Idea</label>
                                    <input type="text" wire:model="newIdeas.{{ $index }}.title" placeholder="cth: Sistem Dashboard Pintar" class="w-full border-none rounded-2xl bg-white p-4 font-bold text-sm shadow-sm focus:ring-2 focus:ring-blue-500 transition-all">
                                </div>

                                <div>
                                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Cadangan / Masalah</label>
                                    <textarea wire:model="newIdeas.{{ $index }}.suggestion" placeholder="Apa yang ingin dicadangkan?" class="w-full border-none rounded-2xl bg-white p-4 text-sm shadow-sm focus:ring-2 focus:ring-blue-500" rows="3"></textarea>
                                </div>

                                <div>
                                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Cadangan Penyelesaian</label>
                                    <textarea wire:model="newIdeas.{{ $index }}.action_taken" placeholder="Apa tindakan seterusnya?" class="w-full border-none rounded-2xl bg-white p-4 text-sm shadow-sm focus:ring-2 focus:ring-blue-500" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="flex flex-col md:flex-row gap-4 pt-4">
                        <button type="button" wire:click="addEmptyRow" class="flex-1 py-5 border-2 border-dashed border-slate-200 rounded-[2rem] text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 hover:bg-slate-50 transition-all italic">
                            + Tambah Row Idea
                        </button>
                        <button type="submit" wire:loading.attr="disabled" class="flex-1 py-5 bg-slate-900 text-white rounded-[2rem] text-[10px] font-black uppercase tracking-[0.2em] hover:bg-blue-600 transition shadow-2xl disabled:opacity-50">
                            <span wire:loading.remove>Simpan Semua Idea</span>
                            <span wire:loading>Memproses...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($showViewModal && $selectedIdea)
    <style>
       @media print {
           body * {
               visibility: hidden;
           }
           .printable-modal-group, .printable-modal-group * {
               visibility: visible;
           }
       }
   </style>

    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 print:absolute print:inset-0 print:p-0 print:bg-white printable-modal-group">
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-md print:hidden" wire:click="closeViewModal"></div>
        <div class="relative bg-white rounded-[3rem] shadow-2xl max-w-2xl w-full flex flex-col transform transition-all print:shadow-none print:rounded-none print:max-w-full print:h-auto print:max-h-none print:min-h-0"
             style="height: 80vh; max-height: 80vh; min-height: 300px;">


            <div class="p-10 pb-6 border-b border-slate-50 flex-none">
                <div class="flex justify-between items-start">
                    <div class="space-y-1">
                        <span class="inline-flex items-center px-3 py-0.5 rounded-full text-[8px] font-black uppercase tracking-[0.2em] {{ $selectedIdea->category == 'inovasi' ? 'bg-blue-50 text-blue-600 border border-blue-100' : 'bg-amber-50 text-amber-600 border border-amber-100' }}">
                            {{ str_replace('_', ' ', $selectedIdea->category) }}
                        </span>
                        <h3 class="text-2xl font-black text-slate-900 uppercase tracking-tighter italic">Butiran Idea</h3>
                        <button onclick="window.print()" class="px-8 py-3 bg-slate-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-800 transition shadow-xl shadow-slate-200 print:hidden">
                            Cetak Idea
                        </button>
                    </div>
                    <button wire:click="closeViewModal" class="p-2 bg-slate-50 text-slate-400 hover:text-red-500 rounded-xl transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
            </div>
            <div class="flex-grow p-10 space-y-8 print:overflow-visible print:h-auto print:max-h-none space-y-6 print:p-0"
                 style="overflow-y: auto !important; -webkit-overflow-scrolling: touch;">

                <div class="text-center">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] my-2 block italic">Tajuk Projek/Idea</label>
                    <p class="text-xl font-black text-slate-900 uppercase italic leading-tight">{{ $selectedIdea->title }}</p>
                </div>

                <div class="grid grid-cols-2 gap-8 border-t border-slate-50 pt-6">
                    <div>
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1 block italic">Bentuk</label>
                        <span class="text-xs font-black uppercase text-slate-600">{{ $selectedIdea->is_digital == 'digital' ? '⚡ Digital' : '📦 Bukan Digital' }}</span>
                    </div>
                    <div>
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1 block italic">Tarikh</label>
                        <span class="text-xs font-black uppercase text-slate-600 tracking-tight">{{ $selectedIdea->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>

                <div class="bg-slate-50 p-8 rounded-[2.5rem] border border-slate-100">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 block italic">Cadangan / Masalah</label>
                    <p class="text-sm font-bold text-slate-700 leading-relaxed italic whitespace-pre-line">
                        {{ $selectedIdea->suggestion }}
                    </p>
                </div>

                <div class="bg-slate-50 p-8 rounded-[2.5rem] border border-slate-100">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 block italic">Cadangan Penyelesaian</label>
                    <p class="text-sm font-bold text-slate-700 leading-relaxed italic whitespace-pre-line">
                        {{ $selectedIdea->action_taken ?: 'Tiada tindakan direkodkan lagi.' }}
                    </p>
                </div>

            </div>

            <div class="p-10 pt-6 border-t border-slate-50 bg-white flex-none rounded-b-[3rem] print:hidden">
                <button wire:click="closeViewModal" class="w-full py-5 bg-slate-900 text-white rounded-[2rem] text-[10px] font-black uppercase tracking-[0.3em] hover:bg-slate-700 transition shadow-xl">
                    Tutup Paparan
                </button>
            </div>
        </div>
    </div>
@endif

</div>
