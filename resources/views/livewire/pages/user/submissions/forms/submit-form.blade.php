<?php

use App\Models\Program;
use App\Models\Submission;
use App\Models\GeneralSubmission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use function Livewire\Volt\{state, mount, usesFileUploads};

usesFileUploads();

state([
    'program' => null,
    'submission' => null,
    'submissionId' => null,
    'user_notes' => '',
    'user_file' => null,
    'existing_file_path' => null,
]);

mount(function (Program $program, Submission $submission = null) {
    $this->program = $program;

    // 1. Dapatkan rekod penyerahan pengguna jika wujud
    if (!$submission || !$submission->exists) {
        $submission = Submission::where('user_id', Auth::id())
            ->where('program_id', $program->id)
            ->first();
    }

    // 2. Isi semula data ke dalam state (Hydrate) jika mod Kemaskini
    if ($submission && $submission->exists) {
        $this->submission = $submission;
        $this->submissionId = $submission->id;

        $generalDetail = GeneralSubmission::where('submission_id', $submission->id)->first();

        if ($generalDetail) {
            $this->user_notes = $generalDetail->notes ?? '';
            $this->existing_file_path = $generalDetail->file_path ?? null;
        }
    }
});

$submit = function () {
    $format = $this->program->other_submission_format ?? 'notes';

    // 1. Pengesahan dinamik mengikut format pilihan Admin
    if ($format === 'upload_form') {
        $fileRule = ($this->user_file || !$this->existing_file_path)
            ? 'required|file|max:10240|mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png,application/zip'
            : 'nullable';

        $this->validate([
            'user_file' => $fileRule,
            'user_notes' => 'nullable|string|max:1000',
        ]);
    } else {
        $this->validate([
            'user_notes' => 'required|string|min:3|max:1000',
        ]);
    }

    $newFilePath = null;
    if ($this->user_file) {
        $newFilePath = $this->user_file->store('user_submissions', 'public');
    }

    try {
        DB::transaction(function () use ($newFilePath) {
            if ($this->submissionId) {
                // MOD KEMASKINI (UPDATE)
                $submission = Submission::findOrFail($this->submissionId);
                $generalDetail = GeneralSubmission::where('submission_id', $submission->id)->first();

                $finalFilePath = $generalDetail ? $generalDetail->file_path : null;

                // Padam fail lama jika fail baharu dimuat naik
                if ($newFilePath) {
                    if ($finalFilePath && Storage::disk('public')->exists($finalFilePath)) {
                        Storage::disk('public')->delete($finalFilePath);
                    }
                    $finalFilePath = $newFilePath;
                }

                if ($generalDetail) {
                    $generalDetail->update([
                        'notes'     => $this->user_notes,
                        'file_path' => $finalFilePath,
                    ]);
                } else {
                    GeneralSubmission::create([
                        'submission_id' => $submission->id,
                        'notes'         => $this->user_notes,
                        'file_path'     => $finalFilePath,
                    ]);
                }
            } else {
                // MOD PERMOHONAN BAHARU (CREATE)
                $newSubmission = Submission::create([
                    'program_id' => $this->program->id,
                    'user_id'    => Auth::id(),
                ]);

                GeneralSubmission::create([
                    'submission_id' => $newSubmission->id,
                    'notes'         => $this->user_notes,
                    'file_path'     => $newFilePath,
                ]);

                $this->submissionId = $newSubmission->id;
            }
        });

    } catch (\Throwable $e) {
        if ($newFilePath && Storage::disk('public')->exists($newFilePath)) {
            Storage::disk('public')->delete($newFilePath);
        }

        session()->flash('error', 'Gagal menyimpan penyertaan. Sila cuba lagi.');
        return;
    }

    $msg = $this->submissionId ? 'Penyertaan anda berjaya dikemaskini!' : 'Penyertaan anda telah berjaya dihantar!';
    session()->flash('success', $msg);
};
?>

<div class="max-w-3xl mx-auto p-6 bg-white rounded-3xl shadow-sm border border-gray-200">

    {{-- Header & Butang Kembali --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight">
                {{ $submissionId ? 'Kemaskini Penyertaan Program' : 'Borang Penyertaan Program' }}
            </h2>
            <div class="mt-1 flex items-center gap-2">
                <span class="px-3 py-1 bg-blue-100 text-blue-700 text-[10px] font-black uppercase rounded-full">
                    {{ $program->title }}
                </span>
                @if($submissionId)
                    <span class="px-3 py-1 bg-amber-100 text-amber-700 text-[10px] font-black uppercase rounded-full">
                        Kemaskini
                    </span>
                @endif
            </div>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 text-sm font-bold rounded-2xl flex items-center gap-2">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 text-sm font-bold rounded-2xl flex items-center gap-2">
            {{ session('error') }}
        </div>
    @endif

    {{-- KASUS 1: ADMIN SEDIAKAN PAUTAN LUAR (Google Forms / Drive / URL) --}}
    @if($program->other_submission_format === 'external_link' && $program->submission_external_link)
        <div class="p-5 bg-blue-50/70 border border-blue-100 rounded-2xl mb-6">
            <h4 class="text-xs font-black text-blue-900 uppercase tracking-wider mb-1">🔗 Pautan Borang</h4>
            <p class="text-xs text-blue-700 mb-3">Sila buka pautan di bawah untuk menghantar penyertaan, kemudian klik Hantar Penyertaan setelah selesai.</p>
            <a href="{{ $program->submission_external_link }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow transition">
                Buka Pautan Borang
            </a>
        </div>
    @endif

    {{-- KASUS 2: ADMIN SEDIAKAN TEMPLAT BORANG (PDF / WORD) UNTUK DIMUAT TURUN --}}
    @if($program->other_submission_format === 'upload_form' && $program->submission_pdf_form)
        <div class="p-5 bg-amber-50/70 border border-amber-200 rounded-2xl mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h4 class="text-xs font-black text-amber-900 uppercase tracking-wider mb-1">📄 Muat Turun Templat Borang</h4>
                <p class="text-xs text-amber-700">Sila muat turun borang templat di bawah, lengkapkan maklumat, dan muat naik semula borang yang telah diisi.</p>
            </div>
            <a href="{{ Storage::url($program->submission_pdf_form) }}" download class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-xl shadow transition whitespace-nowrap">
                Muat Turun Borang
            </a>
        </div>
    @endif

    <div class="mb-6 flex items-center justify-between">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('user.dashboard') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold rounded-xl transition duration-200 group">
            <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali
        </a>
    </div>


    {{-- BORANG PENYERAHAN PENGGUNA --}}
    <form wire:submit="submit" class="space-y-6">

        {{-- Input Muat Naik Fail Pengguna (Wajib jika Admin pilih 'upload_form') --}}
        @if($program->other_submission_format === 'upload_form')
            <div>
                <label class="block text-xs font-black text-gray-700 uppercase mb-2">
                    Muat Naik Borang Telah Diisi (PDF / Word) <span class="text-red-500">*</span>
                </label>

                {{-- Fail sedia ada dalam Mod Kemaskini --}}
                @if($existing_file_path)
                    <div class="mb-3 p-3 bg-blue-50 rounded-xl flex items-center justify-between border border-blue-100">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="text-xs font-bold text-blue-700">Fail Wujud: {{ basename($existing_file_path) }}</span>
                        </div>
                        <a href="{{ Storage::disk('public')->url($existing_file_path) }}" target="_blank" class="text-xs text-blue-600 underline font-semibold">Lihat Fail</a>
                    </div>
                @endif

                <input type="file" wire:model="user_file" class="w-full text-sm text-gray-500 bg-gray-50 border border-gray-200 rounded-2xl p-3 focus:border-blue-500">
                <p class="text-[11px] text-gray-400 mt-1">
                    {{ $existing_file_path ? 'Muat naik fail baharu di atas jika ingin menggantikan fail sedia ada.' : 'Maksimum saiz fail: 10MB (PDF, DOCX, Images, ZIP)' }}
                </p>
                @error('user_file') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
        @endif

        {{-- Ruang Catatan / Notes --}}
        <div>
            <label class="block text-xs font-black text-gray-700 uppercase mb-2">
                {{ $program->other_submission_format === 'notes' ? 'Jawapan ' : 'Catatan Tambahan' }}
                @if($program->other_submission_format !== 'upload_form')
                    <span class="text-red-500">*</span>
                @else
                    <span class="text-gray-400 font-normal">(Optional)</span>
                @endif
            </label>
            <textarea wire:model="user_notes" rows="4"
                      placeholder="{{ $program->other_submission_format === 'notes' ? 'Sila tuliskan jawapan anda di sini...' : 'Tambah sebarang penerangan atau catatan ringkas jika ada...' }}"
                      class="w-full rounded-2xl border-gray-200 bg-gray-50 p-4 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
            @error('user_notes') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
        </div>

        {{-- Butang Hantar --}}
        <button type="submit" wire:loading.attr="disabled" class="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white font-black text-sm rounded-2xl shadow-lg shadow-blue-100 transition">
            <span wire:loading.remove>{{ $submissionId ? 'Kemaskini Penyertaan' : 'Hantar Penyertaan' }}</span>
            <span wire:loading>Sedang Memproses...</span>
        </button>

    </form>
</div>
