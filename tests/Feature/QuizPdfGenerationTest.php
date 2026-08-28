<?php

use App\Models\Section;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

it('blocks students from the PDF quiz generation endpoint', function () {
    $section = Section::factory()->create();
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    $this->actingAs($student)
        ->post(route('quiz.generate'), [
            'module_file' => UploadedFile::fake()->create('module.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect(route('homepage'));
});

it('requires a PDF file', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);

    $this->actingAs($teacher)
        ->postJson(route('quiz.generate'), [])
        ->assertUnprocessable();
});

it('rejects a non-PDF uploaded with a .pdf name', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);

    $this->actingAs($teacher)
        ->postJson(route('quiz.generate'), [
            'module_file' => UploadedFile::fake()->create('module.pdf', 100, 'text/plain'),
        ])
        ->assertUnprocessable();
});

it('fails cleanly and calls no AI when the Gemini key is not configured', function () {
    config(['services.gemini.key' => null]);
    Http::fake();

    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);

    $this->actingAs($teacher)
        ->postJson(route('quiz.generate'), [
            'module_file' => UploadedFile::fake()->create('module.pdf', 100, 'application/pdf'),
        ])
        ->assertStatus(500)
        ->assertJson(['status' => 'error']);

    Http::assertNothingSent();
});
