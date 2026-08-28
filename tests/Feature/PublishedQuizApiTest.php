<?php

use App\Models\QuizCustomTopic;
use App\Models\QuizPublished;
use App\Models\Section;
use App\Models\User;

beforeEach(function () {
    $this->teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $this->sampleQuiz = json_encode([['question' => '2+2?', 'options' => ['A' => '3', 'B' => '4'], 'answer' => 'B']]);
});

it('publishes a quiz and replaces it on re-publish instead of duplicating', function () {
    $this->actingAs($this->teacher)->postJson(route('teacher.quiz.published.store'), [
        'topic_key' => 'ari',
        'pretest' => $this->sampleQuiz,
        'posttest' => $this->sampleQuiz,
        'activity' => json_encode([]),
    ])->assertOk();

    $this->actingAs($this->teacher)->postJson(route('teacher.quiz.published.store'), [
        'topic_key' => 'ari',
        'pretest' => json_encode([['question' => 'new', 'options' => ['A' => '1'], 'answer' => 'A']]),
        'posttest' => $this->sampleQuiz,
        'activity' => json_encode([]),
    ])->assertOk();

    expect(QuizPublished::where('topic_key', 'ari')->count())->toBe(1);
    expect(QuizPublished::where('topic_key', 'ari')->value('pretest'))->toContain('new');
});

it('unpublishes a quiz by topic key', function () {
    QuizPublished::create(['topic_key' => 'geo', 'pretest' => '[]', 'posttest' => '[]', 'activity' => '[]']);

    $this->actingAs($this->teacher)
        ->deleteJson(route('teacher.quiz.published.destroy', 'geo'))
        ->assertOk();

    $this->assertDatabaseMissing('quiz_published', ['topic_key' => 'geo']);
});

it('rejects a non-JSON pretest', function () {
    $this->actingAs($this->teacher)->postJson(route('teacher.quiz.published.store'), [
        'topic_key' => 'ari',
        'pretest' => 'not json',
        'posttest' => '[]',
        'activity' => '[]',
    ])->assertUnprocessable();
});

it('serves published quizzes and custom topic names to a student', function () {
    QuizPublished::create(['topic_key' => 'ari', 'pretest' => $this->sampleQuiz, 'posttest' => '[]', 'activity' => '[]']);
    QuizCustomTopic::create(['module_key' => 'sequences', 'topic_key' => 'ari', 'topic_name' => 'Custom Arithmetic']);

    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => Section::factory()->create()->id,
    ]);

    $response = $this->actingAs($student)->getJson(route('student.modules.published'));

    $response->assertOk();
    expect($response->json('published'))->toHaveCount(1);
    expect($response->json('published.0.topic_key'))->toBe('ari');
    expect($response->json('customTopics.0.topic_name'))->toBe('Custom Arithmetic');
});

it('blocks a student from the teacher publish endpoints', function () {
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => Section::factory()->create()->id,
    ]);

    $this->actingAs($student)->postJson(route('teacher.quiz.published.store'), [
        'topic_key' => 'ari', 'pretest' => '[]', 'posttest' => '[]', 'activity' => '[]',
    ])->assertRedirect(route('homepage'));

    $this->actingAs($student)->get(route('teacher.quiz.published.index'))
        ->assertRedirect(route('homepage'));
});

it('requires authentication for the student modules feed', function () {
    $this->get(route('student.modules.published'))->assertRedirect(route('student.login'));
});
