<?php

use App\Models\QuizCustomTopic;
use App\Models\QuizPublished;
use App\Models\Section;
use App\Models\StudentProgress;
use App\Models\StudentQuizAnswer;
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

it('resets every student\'s pre/post progress for a topic when its quiz is unpublished', function () {
    QuizPublished::create(['topic_key' => 'geo', 'pretest' => '[]', 'posttest' => '[]', 'activity' => '[]']);

    $alice = User::factory()->create(['role' => 'student', 'approval_status' => 'approved', 'section_id' => Section::factory()->create()->id]);
    $bob = User::factory()->create(['role' => 'student', 'approval_status' => 'approved', 'section_id' => Section::factory()->create()->id]);

    // Both finished the geo pre + post test and the activity.
    foreach ([$alice, $bob] as $student) {
        StudentProgress::create(['session_id' => (string) $student->id, 'topic_key' => 'geo', 'phase' => 'pre', 'score' => 8, 'total' => 10, 'passed' => true]);
        StudentProgress::create(['session_id' => (string) $student->id, 'topic_key' => 'geo', 'phase' => 'post', 'score' => 9, 'total' => 10, 'passed' => true]);

        foreach (['pre', 'post', 'activity'] as $phase) {
            StudentQuizAnswer::create(['session_id' => (string) $student->id, 'topic_key' => 'geo', 'phase' => $phase, 'answers' => ['A'], 'score' => 1, 'total' => 1]);
        }
    }

    // Untouched: geo reading progress, and a different topic entirely.
    StudentProgress::create(['session_id' => (string) $alice->id, 'topic_key' => 'geo', 'phase' => 'reading', 'score' => 100, 'total' => 100]);
    StudentProgress::create(['session_id' => (string) $alice->id, 'topic_key' => 'ari', 'phase' => 'post', 'score' => 10, 'total' => 10, 'passed' => true]);
    StudentQuizAnswer::create(['session_id' => (string) $alice->id, 'topic_key' => 'ari', 'phase' => 'post', 'answers' => ['B'], 'score' => 1, 'total' => 1]);

    $this->actingAs($this->teacher)
        ->deleteJson(route('teacher.quiz.published.destroy', 'geo'))
        ->assertOk();

    expect(StudentProgress::where('topic_key', 'geo')->whereIn('phase', ['pre', 'post'])->count())->toBe(0);
    expect(StudentQuizAnswer::where('topic_key', 'geo')->count())->toBe(0);

    $this->assertDatabaseHas('student_progress', ['session_id' => (string) $alice->id, 'topic_key' => 'geo', 'phase' => 'reading']);
    $this->assertDatabaseHas('student_progress', ['session_id' => (string) $alice->id, 'topic_key' => 'ari', 'phase' => 'post']);
    $this->assertDatabaseHas('student_quiz_answers', ['session_id' => (string) $alice->id, 'topic_key' => 'ari', 'phase' => 'post']);
});

it('resets every student\'s pre/post progress when a published quiz is replaced with new questions', function () {
    QuizPublished::create(['topic_key' => 'geo', 'pretest' => '[]', 'posttest' => $this->sampleQuiz, 'activity' => '[]']);

    $alice = User::factory()->create(['role' => 'student', 'approval_status' => 'approved', 'section_id' => Section::factory()->create()->id]);
    $bob = User::factory()->create(['role' => 'student', 'approval_status' => 'approved', 'section_id' => Section::factory()->create()->id]);

    foreach ([$alice, $bob] as $student) {
        StudentProgress::create(['session_id' => (string) $student->id, 'topic_key' => 'geo', 'phase' => 'pre', 'score' => 8, 'total' => 10, 'passed' => true]);
        StudentProgress::create(['session_id' => (string) $student->id, 'topic_key' => 'geo', 'phase' => 'post', 'score' => 9, 'total' => 10, 'passed' => true]);
        StudentQuizAnswer::create(['session_id' => (string) $student->id, 'topic_key' => 'geo', 'phase' => 'activity', 'answers' => ['A'], 'score' => 1, 'total' => 1]);
    }

    StudentProgress::create(['session_id' => (string) $alice->id, 'topic_key' => 'geo', 'phase' => 'reading', 'score' => 100, 'total' => 100]);
    StudentProgress::create(['session_id' => (string) $alice->id, 'topic_key' => 'ari', 'phase' => 'post', 'score' => 10, 'total' => 10, 'passed' => true]);

    $this->actingAs($this->teacher)->postJson(route('teacher.quiz.published.store'), [
        'topic_key' => 'geo',
        'pretest' => json_encode([['question' => 'brand new', 'options' => ['A' => '1'], 'answer' => 'A']]),
        'posttest' => $this->sampleQuiz,
        'activity' => json_encode([]),
    ])->assertOk();

    expect(StudentProgress::where('topic_key', 'geo')->whereIn('phase', ['pre', 'post'])->count())->toBe(0);
    expect(StudentQuizAnswer::where('topic_key', 'geo')->count())->toBe(0);

    $this->assertDatabaseHas('student_progress', ['session_id' => (string) $alice->id, 'topic_key' => 'geo', 'phase' => 'reading']);
    $this->assertDatabaseHas('student_progress', ['session_id' => (string) $alice->id, 'topic_key' => 'ari', 'phase' => 'post']);
});

it('keeps student progress when a re-publish does not change the questions', function () {
    QuizPublished::create(['topic_key' => 'geo', 'pretest' => $this->sampleQuiz, 'posttest' => $this->sampleQuiz, 'activity' => '[]']);

    $alice = User::factory()->create(['role' => 'student', 'approval_status' => 'approved', 'section_id' => Section::factory()->create()->id]);
    StudentProgress::create(['session_id' => (string) $alice->id, 'topic_key' => 'geo', 'phase' => 'post', 'score' => 9, 'total' => 10, 'passed' => true]);

    $this->actingAs($this->teacher)->postJson(route('teacher.quiz.published.store'), [
        'topic_key' => 'geo',
        'pretest' => $this->sampleQuiz,
        'posttest' => $this->sampleQuiz,
        'activity' => json_encode([]),
    ])->assertOk();

    $this->assertDatabaseHas('student_progress', ['session_id' => (string) $alice->id, 'topic_key' => 'geo', 'phase' => 'post']);
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
