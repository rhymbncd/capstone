<?php

use App\Models\Section;
use App\Models\StudentProgress;
use App\Models\StudentQuizAnswer;
use App\Models\User;

it('returns raw pretest, posttest, activity, and summative scores per lesson', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $section = Section::factory()->create(['teacher_id' => $teacher->id]);
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    StudentProgress::create([
        'session_id' => (string) $student->id,
        'topic_key' => 'ari',
        'phase' => 'pre',
        'score' => 3,
        'total' => 15,
        'passed' => false,
        'student_name' => $student->name,
    ]);
    StudentProgress::create([
        'session_id' => (string) $student->id,
        'topic_key' => 'ari',
        'phase' => 'post',
        'score' => 12,
        'total' => 15,
        'passed' => true,
        'student_name' => $student->name,
    ]);
    StudentProgress::create([
        'session_id' => (string) $student->id,
        'topic_key' => 'summative',
        'phase' => 'post',
        'score' => 18,
        'total' => 20,
        'passed' => true,
        'student_name' => $student->name,
    ]);
    StudentQuizAnswer::create([
        'session_id' => (string) $student->id,
        'student_name' => $student->name,
        'topic_key' => 'ari',
        'phase' => 'activity',
        'answers' => [],
        'score' => 7,
        'total' => 10,
    ]);

    $response = $this->actingAs($teacher)->getJson(route('teacher.class-record.index'));

    $response->assertOk();
    $data = collect($response->json('students'))->firstWhere('id', $student->id);

    expect($data['pretest']['ari'])->toBe(['score' => 3, 'total' => 15]);
    expect($data['posttest']['ari'])->toBe(['score' => 12, 'total' => 15]);
    expect($data['activity']['ari'])->toBe(['score' => 7, 'total' => 10]);
    expect($data['summative'])->toBe(['score' => 18, 'total' => 20]);

    // Untouched lessons are null, not zeroed or omitted.
    expect($data['pretest']['geo'])->toBeNull();
    expect($data['posttest']['geo'])->toBeNull();
    expect($data['activity']['geo'])->toBeNull();
});

it('lists every curriculum lesson in order, for building table columns', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);

    $response = $this->actingAs($teacher)->getJson(route('teacher.class-record.index'));

    $response->assertOk();
    $keys = collect($response->json('topics'))->pluck('key')->all();
    expect($keys)->toBe(['ari', 'geo', 'har', 'fib', 'fin', 'div', 'rem', 'poly', 'rat', 'rad', 'exp', 'log']);
});

it('reports null for a student with no attempts yet, not zero', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $section = Section::factory()->create(['teacher_id' => $teacher->id]);
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    $response = $this->actingAs($teacher)->getJson(route('teacher.class-record.index'));

    $response->assertOk();
    $data = collect($response->json('students'))->firstWhere('id', $student->id);
    expect($data['summative'])->toBeNull();
    expect($data['pretest']['ari'])->toBeNull();
});

it('excludes students belonging to other teachers\' sections', function () {
    $teacherA = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $teacherB = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $sectionB = Section::factory()->create(['teacher_id' => $teacherB->id]);

    User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $sectionB->id,
    ]);

    $response = $this->actingAs($teacherA)->getJson(route('teacher.class-record.index'));

    $response->assertOk();
    expect($response->json('students'))->toBeEmpty();
});

it('blocks non-teachers from the class record endpoint', function () {
    $section = Section::factory()->create();
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    $response = $this->actingAs($student)->getJson(route('teacher.class-record.index'));

    $response->assertRedirect(route('homepage'));
});
