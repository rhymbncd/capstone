<?php

use App\Models\Section;
use App\Models\StudentProgress;
use App\Models\User;

beforeEach(function () {
    $this->student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => Section::factory()->create()->id,
    ]);
});

it('saves a post-test attempt keyed to the authenticated student', function () {
    $this->actingAs($this->student)
        ->postJson(route('student.progress.store'), [
            'topic_key' => 'ari',
            'phase' => 'post',
            'score' => 9,
            'total' => 10,
            'passed' => true,
        ])
        ->assertOk()
        ->assertJson(['saved' => true]);

    $this->assertDatabaseHas('student_progress', [
        'session_id' => (string) $this->student->id,
        'student_name' => $this->student->name,
        'topic_key' => 'ari',
        'phase' => 'post',
        'score' => 9,
        'passed' => true,
    ]);
});

it('ignores a session_id supplied in the request body', function () {
    $victim = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => Section::factory()->create()->id,
    ]);

    $this->actingAs($this->student)->postJson(route('student.progress.store'), [
        'session_id' => (string) $victim->id,
        'student_name' => 'Someone Else',
        'topic_key' => 'geo',
        'phase' => 'post',
        'score' => 1,
        'total' => 10,
    ])->assertOk();

    $this->assertDatabaseHas('student_progress', [
        'session_id' => (string) $this->student->id,
        'topic_key' => 'geo',
    ]);
    $this->assertDatabaseMissing('student_progress', [
        'session_id' => (string) $victim->id,
    ]);
});

it('upserts on topic + phase instead of creating duplicates', function () {
    foreach ([5, 8] as $score) {
        $this->actingAs($this->student)->postJson(route('student.progress.store'), [
            'topic_key' => 'ari',
            'phase' => 'pre',
            'score' => $score,
            'total' => 10,
        ])->assertOk();
    }

    expect(StudentProgress::where('session_id', (string) $this->student->id)->where('topic_key', 'ari')->count())->toBe(1);
    $this->assertDatabaseHas('student_progress', [
        'session_id' => (string) $this->student->id,
        'topic_key' => 'ari',
        'phase' => 'pre',
        'score' => 8,
    ]);
});

it('accepts the reading phase and rejects an unknown phase', function () {
    $this->actingAs($this->student)->postJson(route('student.progress.store'), [
        'topic_key' => 'ari',
        'phase' => 'reading',
        'score' => 40,
        'total' => 100,
    ])->assertOk();

    $this->actingAs($this->student)->postJson(route('student.progress.store'), [
        'topic_key' => 'ari',
        'phase' => 'summative',
        'score' => 1,
        'total' => 1,
    ])->assertUnprocessable();
});

it('returns only the authenticated student\'s progress rows', function () {
    $other = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => Section::factory()->create()->id,
    ]);

    StudentProgress::create(['session_id' => (string) $this->student->id, 'topic_key' => 'ari', 'phase' => 'post', 'score' => 9, 'total' => 10, 'passed' => true]);
    StudentProgress::create(['session_id' => (string) $other->id, 'topic_key' => 'geo', 'phase' => 'post', 'score' => 3, 'total' => 10, 'passed' => false]);

    $response = $this->actingAs($this->student)->getJson(route('student.progress.index'));

    $response->assertOk();
    $rows = collect($response->json('progress'));
    expect($rows)->toHaveCount(1);
    expect($rows->first()['topic_key'])->toBe('ari');
});

it('requires authentication', function () {
    $this->postJson(route('student.progress.store'), [
        'topic_key' => 'ari', 'phase' => 'post', 'score' => 1, 'total' => 1,
    ])->assertUnauthorized();

    $this->post(route('student.progress.store'), [
        'topic_key' => 'ari', 'phase' => 'post', 'score' => 1, 'total' => 1,
    ])->assertRedirect(route('student.login'));
});

it('blocks a teacher from the student progress endpoint', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);

    $this->actingAs($teacher)->get(route('student.progress.index'))
        ->assertRedirect(route('homepage'));
});
