<?php

use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\Http;

it('generates quiz text server-side without exposing the API key', function () {
    config(['services.openrouter.key' => 'test-key-should-never-reach-browser']);
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => '[{"question":"2+2?","options":{"A":"3","B":"4"},"answer":"B"}]']],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($teacher)->postJson(route('quiz.generate-text'), [
        'prompt' => 'Generate 1 easy question about addition.',
    ]);

    $response->assertOk();
    $response->assertJson(['status' => 'success']);
    expect($response->json('content'))->toContain('2+2?');

    // The response body must never contain the raw API key.
    expect($response->getContent())->not->toContain('test-key-should-never-reach-browser');

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer test-key-should-never-reach-browser');
    });
});

it('handles the AI provider failing gracefully', function () {
    config(['services.openrouter.key' => 'test-key']);
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);

    Http::fake([
        'openrouter.ai/*' => Http::response(['error' => 'server error'], 500),
    ]);

    $response = $this->actingAs($teacher)->postJson(route('quiz.generate-text'), [
        'prompt' => 'Generate a question.',
    ]);

    $response->assertStatus(502);
    $response->assertJson(['status' => 'error']);
});

it('requires a prompt to generate a quiz', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);

    $response = $this->actingAs($teacher)->postJson(route('quiz.generate-text'), []);

    $response->assertUnprocessable();
});

it('blocks students from the quiz generation endpoint', function () {
    $section = Section::factory()->create();
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    $response = $this->actingAs($student)->postJson(route('quiz.generate-text'), [
        'prompt' => 'test',
    ]);

    $response->assertRedirect(route('homepage'));
});

it('generates quiz text server-side for students without exposing the API key', function () {
    config(['services.openrouter.key' => 'test-key-should-never-reach-browser']);
    $section = Section::factory()->create();
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => '["Rewritten question text"]']],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($student)->postJson(route('student.quiz.generate-text'), [
        'prompt' => 'Reword this summative test question.',
    ]);

    $response->assertOk();
    $response->assertJson(['status' => 'success']);
    expect($response->json('content'))->toContain('Rewritten question text');
    expect($response->getContent())->not->toContain('test-key-should-never-reach-browser');
});

it('handles the AI provider failing gracefully for the student summative endpoint', function () {
    config(['services.openrouter.key' => 'test-key']);
    $section = Section::factory()->create();
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    Http::fake([
        'openrouter.ai/*' => Http::response(['error' => 'server error'], 500),
    ]);

    $response = $this->actingAs($student)->postJson(route('student.quiz.generate-text'), [
        'prompt' => 'Reword this summative test question.',
    ]);

    $response->assertStatus(502);
    $response->assertJson(['status' => 'error']);
});

it('blocks teachers from the student summative generation endpoint', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);

    $response = $this->actingAs($teacher)->postJson(route('student.quiz.generate-text'), [
        'prompt' => 'test',
    ]);

    $response->assertRedirect(route('homepage'));
});

it('grades activity answers server-side for students without exposing the API key', function () {
    config(['services.openrouter.key' => 'test-key-should-never-reach-browser']);
    $section = Section::factory()->create();
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => '[{"correct":true,"answer":"39"}]']],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($student)->postJson(route('student.quiz.grade-activity'), [
        'prompt' => 'Grade this activity item.',
    ]);

    $response->assertOk();
    $response->assertJson(['status' => 'success']);
    expect($response->json('content'))->toContain('"correct":true');
    expect($response->getContent())->not->toContain('test-key-should-never-reach-browser');
});

it('handles the AI provider failing gracefully for activity grading', function () {
    config(['services.openrouter.key' => 'test-key']);
    $section = Section::factory()->create();
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    Http::fake([
        'openrouter.ai/*' => Http::response(['error' => 'server error'], 500),
    ]);

    $response = $this->actingAs($student)->postJson(route('student.quiz.grade-activity'), [
        'prompt' => 'Grade this activity item.',
    ]);

    $response->assertStatus(502);
    $response->assertJson(['status' => 'error']);
});

it('requires a prompt to grade an activity', function () {
    $section = Section::factory()->create();
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    $response = $this->actingAs($student)->postJson(route('student.quiz.grade-activity'), []);

    $response->assertUnprocessable();
});

it('blocks teachers from the activity grading endpoint', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);

    $response = $this->actingAs($teacher)->postJson(route('student.quiz.grade-activity'), [
        'prompt' => 'test',
    ]);

    $response->assertRedirect(route('homepage'));
});
