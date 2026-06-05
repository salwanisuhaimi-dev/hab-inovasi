<?php

use App\Models\Program;
use App\Models\Submission;
use function Livewire\Volt\{layout, with, state};

layout('layouts.app');

state([
    'program' => fn (Program $program) => $program,
    'selectedSubmission' => null,
    'showModal' => false,
]);

with([
    'submissions' => fn() => Submission::where('program_id', $this->program->id)
        ->with(['user', 'department'])
        ->latest()
        ->get()
]);

$updateStatus = function ($submissionId, $status) {
    $submission = Submission::findOrFail($submissionId);
    $submission->update(['status' => $status]);
    
    session()->flash('success', "Status penyertaan telah dikemaskini ke $status.");
};

$viewDetails = function ($id) {
    $this->selectedSubmission = Submission::with('user')->find($id);
    $this->showModal = true;
}; 

?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-3xl font-black text-gray-900 mt-2">{{ $program->title }}</h2>
                <p class="text-gray-500 font-medium">Senarai penyertaan bagi program ini.</p>
            </div>
            <div class="bg-blue-600 px-6 py-3 rounded-2xl text-white shadow-lg shadow-blue-100 text-center">
                <p class="text-[10px] font-bold uppercase opacity-80 tracking-tighter">Jumlah Penyertaan</p>
                <p class="text-2xl font-black">{{ $submissions->count() }}</p>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-xl sm:rounded-[2.5rem] border border-gray-100">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400">Peserta / Kumpulan</th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400">Bahagian</th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400">Maklumat Projek</th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400">Dokumen</th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400">Status</th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase text-gray-400 text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($submissions as $sub)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-xs uppercase">
                                            {{ substr($sub->user->name, 0, 2) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900 leading-none">{{ $sub->user->name }}</p>
                                            <p class="text-xs text-gray-500 mt-1 uppercase font-black tracking-tighter text-blue-600">{{ $sub->group_name }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-6">
                                    <p class="text-xs text-gray-400 mt-1 line-clamp-1 italic">{{ $sub->department->code }}</p>
                                </td>
                                
                                <td class="px-6 py-6">
                                    <p class="font-bold text-gray-900 leading-tight">{{ $sub->project_title }}</p>
                                    <p class="text-xs text-gray-400 mt-1 line-clamp-1 italic">{{ $sub->project_description }}</p>
                                </td>
                                <td class="px-6 py-6 text-center">
                                    @if($sub->file_path)
                                        <a href="{{ Storage::url($sub->file_path) }}" target="_blank" 
                                           class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-all group">
                                            <svg class="w-4 h-4 mr-2 text-blue-600 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            <span class="text-xs font-bold uppercase tracking-widest">Buka Fail</span>
                                        </a>
                                    @else
                                        <span class="text-[10px] text-gray-300 italic">Tiada Fail</span>
                                    @endif
                                </td>
                                <td class="px-6 py-6">
                                    @php
                                        $colors = [
                                            'pending' => 'bg-yellow-100 text-yellow-700',
                                            'approved' => 'bg-green-100 text-green-700',
                                            'rejected' => 'bg-red-100 text-red-700',
                                        ][$sub->status] ?? 'bg-gray-100 text-gray-700';
                                    @endphp
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase {{ $colors }}">
                                        {{ $sub->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-6 text-right">
                                    <div class="flex justify-end gap-2">
                                        @if($sub->status === 'pending')
                                            <button wire:click="updateStatus({{ $sub->id }}, 'approved')" 
                                                class="p-2 bg-green-50 text-green-600 rounded-xl hover:bg-green-600 hover:text-white transition-all shadow-sm">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            </button>
                                            <button wire:click="updateStatus({{ $sub->id }}, 'rejected')"
                                                class="p-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition-all shadow-sm">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        @else
                                            <button wire:click="updateStatus({{ $sub->id }}, 'pending')" 
                                                class="text-[10px] font-bold text-gray-400 hover:text-gray-900 underline">Reset</button>
                                        @endif

                                        <button wire:click="viewDetails({{ $sub->id }})" 
                                            class="p-2 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white transition-all shadow-sm">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-20 text-center">
                                    <p class="text-gray-400 font-bold uppercase tracking-widest">Tiada penyertaan masuk lagi</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div x-data="{ open: @entangle('showModal') }" 
     x-show="open" 
     class="fixed inset-0 z-50 overflow-y-auto" 
     style="display: none;">
    
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" x-on:click="open = false"></div>
            <div class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-[2.5rem] shadow-xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-10">
            
            @if($selectedSubmission)
                <div>
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 text-[10px] font-black uppercase rounded-full">
                                {{ $selectedSubmission->status }}
                            </span>
                            <h3 class="text-2xl font-black text-gray-900 mt-2">{{ $selectedSubmission->project_title }}</h3>
                            <p class="text-sm text-blue-600 font-bold uppercase tracking-tight">{{ $selectedSubmission->group_name }}</p>
                        </div>
                        <button x-on:click="open = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div class="space-y-6">
                        <div class="p-4 bg-gray-50 rounded-2xl flex items-center gap-4">
                            <div class="h-12 w-12 rounded-full bg-gray-200 flex items-center justify-center font-bold text-gray-600 uppercase">
                                {{ substr($selectedSubmission->user->name, 0, 2) }}
                            </div>
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-widest">Dihantar Oleh</p>
                            <p class="font-bold text-gray-900">{{ $selectedSubmission->user->name }} ({{ $selectedSubmission->user->email }})</p>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2 italic">Penerangan Projek</h4>
                        <div class="text-gray-700 leading-relaxed bg-blue-50/30 p-6 rounded-3xl border border-blue-50">
                            {{ $selectedSubmission->project_description }}
                        </div>
                    </div>

                    @if($selectedSubmission->file_path)
                    <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-sm font-bold text-gray-600 italic">Dokumen Sokongan Terlampir</span>
                        <a href="{{ Storage::url($selectedSubmission->file_path) }}" target="_blank" 
                           class="px-6 py-3 bg-gray-900 text-white rounded-xl font-bold text-xs hover:bg-blue-600 transition-all">
                           Lihat Fail (PDF/ZIP)
                        </a>
                    </div>
                    @endif
                </div>

                <div class="mt-10 flex gap-3">
                    <button x-on:click="open = false" class="flex-1 py-4 bg-gray-100 text-gray-600 rounded-2xl font-bold text-sm">Tutup</button>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

