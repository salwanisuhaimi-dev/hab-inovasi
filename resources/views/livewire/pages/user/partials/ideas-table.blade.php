<div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden transition-all hover:shadow-md">
    <table class="w-full text-left text-xs border-collapse">
        <thead class="bg-slate-50/80 backdrop-blur-sm">
            <tr>
                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Butiran Idea</th>
                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Bentuk</th>
                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Tindakan</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
            @php
                $ideas = \App\Models\CoffeeBreakIdea::where('coffee_break_session_id', $sessionId)
                            ->where('category', $category)
                            ->latest()
                            ->get();
            @endphp

            @forelse($ideas as $idea)
                <tr class="hover:bg-slate-50/50 transition-colors group">
                    <td class="px-8 py-5">
                        <div class="font-black text-slate-900 tracking-tight text-sm group-hover:text-blue-600 transition-colors">
                            {{ $idea->title }}
                        </div>
                        <div class="text-[10px] text-slate-400 font-medium mt-1 line-clamp-2 leading-relaxed italic">
                            "{{ Str::words($idea->suggestion, 15) }}"
                        </div>
                    </td>
                    <td class="px-8 py-5">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest {{ $idea->is_digital == 'digital' ? 'bg-indigo-50 text-indigo-600 border border-indigo-100' : 'bg-slate-100 text-slate-500 border border-slate-200' }}">
                            {{ $idea->is_digital == 'digital' ? ' Digital' : ' Bukan Digital' }}
                        </span>
                    </td>
                    <td class="px-8 py-5 text-right whitespace-nowrap">
                        <div class="flex justify-end gap-2">
                            <button
                                wire:click="viewIdea({{ $idea->id }})"
                                class="p-2.5 bg-blue-50 hover:bg-blue-600 rounded-xl transition-all text-blue-400 hover:text-white group/view relative"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span class="absolute -top-10 left-1/2 -translate-x-1/2 scale-0 group-hover/view:scale-100 transition-all bg-slate-900 text-white text-[9px] font-black px-2 py-1.5 rounded-lg shadow-xl uppercase whitespace-nowrap z-30">
                                    Lihat Butiran
                                </span>
                            </button>

                            <button
                                wire:click="deleteIdea({{ $idea->id }})"
                                wire:confirm="Adakah anda pasti ingin memadam idea ini?"
                                class="p-2.5 bg-slate-50 hover:bg-red-50 rounded-xl transition-all text-slate-300 hover:text-red-600 group/del relative"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <span class="absolute -top-10 left-1/2 -translate-x-1/2 scale-0 group-hover/del:scale-100 transition-all bg-slate-900 text-white text-[9px] font-black px-2 py-1.5 rounded-lg shadow-xl uppercase whitespace-nowrap z-30">
                                    Padam
                                </span>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="px-8 py-16 text-center">
                        <div class="flex flex-col items-center opacity-20">
                            <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                            <p class="font-black uppercase tracking-[0.2em] text-[10px]">Tiada idea direkodkan</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
