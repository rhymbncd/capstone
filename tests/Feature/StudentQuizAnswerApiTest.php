<?php

use App\Models\Section;
use App\Models\StudentQuizAnswer;
use App\Models\User;

beforeEach(function () {
    $this->section = Section::factory()->create();
    $this->student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $this->section->id,
    ]);
});

it('saves an answer list keyed to the authenticated student', function () {
    $answers = [
        ['question' => '2 + 2?', 'selected' => '4', 'correct' => '4', 'isCorrect' => true],
        ['question' => '3 x 3?', 'selected' => '6', 'correct' => '9', 'isCorrect' => false],
    ];

    $this->actingAs($this->student)
        ->postJson(route('student.quiz-answers.store'), [
            'topic_key' => 'ari',
            'phase' => 'post',
            'answers' => $answers,
            'score' => 1,
            'total' => 2,
        ])
        ->assertOk()
        ->assertJson(['saved' => true]);

    $row = StudentQuizAnswer::where('session_id', (string) $this->student->id)->first();
    expect($row)->not->toBeNull();
    expect($row->student_name)->toBe($this->student->name);
    expect($row->answers)->toHaveCount(2);
    expect($row->phase)->toBe('post');
});

it('ignores a session_id in the request body', function () {
    $victim = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => Section::factory()->create()->id,
    ]);

    $this->actingAs($this->student)->postJson(route('student.quiz-answers.store'), [
        'session_id' => (string) $victim->id,
        'topic_key' => 'geo',
        'phase' => 'activity',
        'answers' => [['question' => 'q', 'selected' => 'a']],
        'score' => 1,
        'total' => 1,
    ])->assertOk();

    $this->assertDatabaseHas('student_quiz_answers', ['session_id' => (string) $this->student->id, 'topic_key' => 'geo']);
    $this->assertDatabaseMissing('student_quiz_answers', ['session_id' => (string) $victim->id]);
});

it('is visible to the owning teacher through the existing read path', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $this->section->update(['teacher_id' => $teacher->id]);

    $this->actingAs($this->student)->postJson(route('student.quiz-answers.store'), [
        'topic_key' => 'ari',
        'phase' => 'pre',
        'answers' => [['question' => 'q', 'selected' => 'a', 'correct' => 'a', 'isCorrect' => true]],
        'score' => 5,
        'total' => 10,
    ])->assertOk();

    $response = $this->actingAs($teacher)->getJson(route('teacher.students.answers', $this->student));

    $response->assertOk();
    $attempts = collect($response->json('attempts'));
    expect($attempts)->toHaveCount(1);
    expect($attempts->first())->topic_key->toBe('ari')->phase->toBe('pre');
});

it('rejects an unknown phase', function () {
    $this->actingAs($this->student)->postJson(route('student.quiz-answers.store'), [
        'topic_key' => 'ari',
        'phase' => 'reading',
        'answers' => [['q' => 1]],
        'score' => 1,
        'total' => 1,
    ])->assertUnprocessable();
});

it('rejects a second attempt at the same topic + phase as already submitted', function () {
    $firstAnswers = [['question' => '2 + 2?', 'selected' => '4', 'correct' => '4', 'isCorrect' => true]];
    $retryAnswers = [['question' => '2 + 2?', 'selected' => '5', 'correct' => '4', 'isCorrect' => false]];

    $this->actingAs($this->student)->postJson(route('student.quiz-answers.store'), [
        'topic_key' => 'ari',
        'phase' => 'activity',
        'answers' => $firstAnswers,
        'score' => 1,
        'total' => 1,
    ])->assertOk();

    $this->actingAs($this->student)->postJson(route('student.quiz-answers.store'), [
        'topic_key' => 'ari',
        'phase' => 'activity',
        'answers' => $retryAnswers,
        'score' => 0,
        'total' => 1,
    ])
        ->assertStatus(409)
        ->assertJson(['saved' => false, 'already_submitted' => true]);

    expect(StudentQuizAnswer::where('session_id', (string) $this->student->id)->where('topic_key', 'ari')->count())->toBe(1);
    $this->assertDatabaseHas('student_quiz_answers', [
        'session_id' => (string) $this->student->id,
        'topic_key' => 'ari',
        'phase' => 'activity',
        'score' => 1,
    ]);
});

it('lets a student review their own submitted attempts', function () {
    $this->actingAs($this->student)->postJson(route('student.quiz-answers.store'), [
        'topic_key' => 'ari',
        'phase' => 'pre',
        'answers' => [['question' => 'q', 'selected' => 'a', 'correct' => 'a', 'isCorrect' => true]],
        'score' => 1,
        'total' => 1,
    ])->assertOk();

    $other = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => Section::factory()->create()->id,
    ]);
    $this->actingAs($other)->postJson(route('student.quiz-answers.store'), [
        'topic_key' => 'geo',
        'phase' => 'pre',
        'answers' => [['question' => 'other', 'selected' => 'x']],
        'score' => 0,
        'total' => 1,
    ])->assertOk();

    $response = $this->actingAs($this->student)->getJson(route('student.quiz-answers.index'));

    $response->assertOk();
    $attempts = collect($response->json('attempts'));
    expect($attempts)->toHaveCount(1);
    expect($attempts->first()['topic_key'])->toBe('ari')->and($attempts->first()['phase'])->toBe('pre');
});

it('requires authentication for the quiz-answers endpoints', function () {
    $this->getJson(route('student.quiz-answers.index'))->assertUnauthorized();
    $this->postJson(route('student.quiz-answers.store'), [
        'topic_key' => 'ari', 'phase' => 'pre', 'answers' => [['q' => 1]], 'score' => 1, 'total' => 1,
    ])->assertUnauthorized();
});
