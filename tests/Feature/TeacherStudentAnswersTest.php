<?php

use App\Models\Section;
use App\Models\StudentQuizAnswer;
use App\Models\User;

it('returns a student\'s saved pre-test/post-test answers to the owning teacher', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $section = Section::factory()->create(['teacher_id' => $teacher->id]);
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    StudentQuizAnswer::create([
        'session_id' => (string) $student->id,
        'student_name' => $student->name,
        'topic_key' => 'ari',
        'phase' => 'pre',
        'answers' => [
            ['question' => 'Common difference of 2, 5, 8?', 'selected' => '3', 'correct' => '3', 'isCorrect' => true],
        ],
        'score' => 8,
        'total' => 10,
    ]);

    $response = $this->actingAs($teacher)->getJson(route('teacher.students.answers', $student));

    $response->assertOk();
    $response->assertJsonPath('student.id', $student->id);

    $attempts = collect($response->json('attempts'));
    expect($attempts)->toHaveCount(1);
    expect($attempts->first())
        ->topic_key->toBe('ari')
        ->topic_name->toBe('Arithmetic Sequence')
        ->phase->toBe('pre')
        ->score->toBe(8)
        ->total->toBe(10);
    expect($attempts->first()['answers'])->toHaveCount(1);
});

it('blocks a teacher from viewing another teacher\'s student answers', function () {
    $teacherA = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $teacherB = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $sectionB = Section::factory()->create(['teacher_id' => $teacherB->id]);
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $sectionB->id,
    ]);

    StudentQuizAnswer::create([
        'session_id' => (string) $student->id,
        'student_name' => $student->name,
        'topic_key' => 'ari',
        'phase' => 'pre',
        'answers' => [['question' => 'q', 'selected' => 'a', 'correct' => 'a', 'isCorrect' => true]],
        'score' => 5,
        'total' => 10,
    ]);

    $response = $this->actingAs($teacherA)->getJson(route('teacher.students.answers', $student));

    $response->assertNotFound();
});

it('returns an empty list when a student has not attempted anything yet', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $section = Section::factory()->create(['teacher_id' => $teacher->id]);
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    $response = $this->actingAs($teacher)->getJson(route('teacher.students.answers', $student));

    $response->assertOk();
    expect($response->json('attempts'))->toBeEmpty();
});
