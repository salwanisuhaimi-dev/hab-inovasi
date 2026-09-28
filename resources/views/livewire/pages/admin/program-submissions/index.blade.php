<?php

use App\Models\Program;
use App\Models\Submission;
use function Livewire\Volt\{layout, with, state, mount};

layout('layouts.app');

state([
    'program' => null,
    'submissionSlug' => '',
    'submissionId' => null,
]);

mount(function (Program $program, string $submission_slug, $submission = null) {
    $this->program = $program;
    $this->submissionSlug = $submission_slug;
    $this->submissionId = $submission;
});


?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      @if ($submissionSlug === 'project-submissions')
          @livewire('pages.admin.program-submissions.partial.project-submissions', ['program' => $program, 'submissionId' => $submissionId])
      @elseif ($submissionSlug === 'attendance-submissions')
          @livewire('pages.admin.program-submissions.partial.attendance-submissions', ['program' => $program, 'submissionId' => $submissionId])
      @elseif ($submissionSlug === 'quiz-submissions')
          @livewire('pages.admin.program-submissions.partial.quiz-submissions', ['program' => $program, 'submissionId' => $submissionId])
      @else
          @livewire('pages.admin.program-submissions.partial.form-submissions', ['program' => $program, 'submissionId' => $submissionId])
      @endif
    </div>
</div>
