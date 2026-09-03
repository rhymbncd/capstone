<?php

use App\Models\Section;
use App\Models\StudentProgress;
use App\Models\User;

it('returns the teacher\'s students with real progress computed from student_progress', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $section = Section::factory()->create(['teacher_id' => $teacher->id]);

    $halfway = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);
    $freshStart = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    // 6 of the 12 curriculum topics completed => 50% progress, with a
    // pre-test score of 40% and post-test score of 80% (real improvement).
    foreach (['ari', 'geo', 'har', 'fib', 'fin', 'div'] as $topic) {
        StudentProgress::create([
            'session_id' => (string) $halfway->id,
            'topic_key' => $topic,
            'phase' => 'pre',
            'score' => 4,
            'total' => 10,
            'passed' => false,
            'student_name' => $halfway->name,
        ]);
        StudentProgress::create([
            'session_id' => (string) $halfway->id,
            'topic_key' => $topic,
            'phase' => 'post',
            'score' => 8,
            'total' => 10,
            'passed' => true,
            'student_name' => $halfway->name,
        ]);
    }

    $response = $this->actingAs($teacher)->getJson(route('teacher.students.index'));

    $response->assertOk();
    $students = collect($response->json('students'));

    expect($students)->toHaveCount(2);

    $halfwayData = $students->firstWhere('id', $halfway->id);
    expect($halfwayData['progress'])->toBe(50);
    expect($halfwayData['status'])->toBe('Average');
    expect($halfwayData['avgPre'])->toBe(40);
    expect($halfwayData['avgPost'])->toBe(80);

    $freshData = $students->firstWhere('id', $freshStart->id);
    expect($freshData['progress'])->toBe(0);
    expect($freshData['status'])->toBe('Not Started');
    expect($freshData['avgPre'])->toBeNull();
    expect($freshData['avgPost'])->toBeNull();
});

it('marks an approved student with no test attempts as Not Started, not Needs Help', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $section = Section::factory()->create(['teacher_id' => $teacher->id]);

    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    $response = $this->actingAs($teacher)->getJson(route('teacher.students.index'));

    $response->assertOk();
    $data = collect($response->json('students'))->firstWhere('id', $student->id);
    expect($data['status'])->toBe('Not Started');
});

it('marks an active but low-progress student as Needs Help', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $section = Section::factory()->create(['teacher_id' => $teacher->id]);

    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    // Attempted the pre-test for one topic but completed no post-tests => 0% progress.
    StudentProgress::create([
        'session_id' => (string) $student->id,
        'topic_key' => 'ari',
        'phase' => 'pre',
        'score' => 2,
        'total' => 10,
        'passed' => false,
        'student_name' => $student->name,
    ]);

    $response = $this->actingAs($teacher)->getJson(route('teacher.students.index'));

    $response->assertOk();
    $data = collect($response->json('students'))->firstWhere('id', $student->id);
    expect($data['progress'])->toBe(0);
    expect($data['status'])->toBe('Needs Help');
});

it('computes per-module subject completion rates for the teacher\'s class', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $section = Section::factory()->create(['teacher_id' => $teacher->id]);

    $halfway = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);
    User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    // Completes all of Module 1 (5 topics) plus 'div' from Module 2.
    foreach (['ari', 'geo', 'har', 'fib', 'fin', 'div'] as $topic) {
        StudentProgress::create([
            'session_id' => (string) $halfway->id,
            'topic_key' => $topic,
            'phase' => 'post',
            'score' => 8,
            'total' => 10,
            'passed' => true,
            'student_name' => $halfway->name,
        ]);
    }

    $response = $this->actingAs($teacher)->getJson(route('teacher.students.index'));

    $response->assertOk();
    $subjects = collect($response->json('subjectCompletion'))->keyBy('label');

    // 2 students total in the class; only $halfway completed anything.
    expect($subjects['Module 1: Sequences and Series']['pct'])->toBe(50); // 5/(2*5)
    expect($subjects['Module 2: Polynomials']['pct'])->toBe(17);          // 1/(2*3), rounded
    expect($subjects['Module 3: Advanced Equations']['pct'])->toBe(0);    // 0/(2*4)
});

it('includes each student\'s student ID', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $section = Section::factory()->create(['teacher_id' => $teacher->id]);
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
        'student_id' => '24-4242',
    ]);

    $response = $this->actingAs($teacher)->getJson(route('teacher.students.index'));

    $response->assertOk();
    $data = collect($response->json('students'))->firstWhere('id', $student->id);
    expect($data['studentId'])->toBe('24-4242');
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

    $response = $this->actingAs($teacherA)->getJson(route('teacher.students.index'));

    $response->assertOk();
    expect($response->json('students'))->toBeEmpty();
});

it('blocks non-teachers from the students endpoint', function () {
    $section = Section::factory()->create();
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    $response = $this->actingAs($student)->getJson(route('teacher.students.index'));

    // student role fails the 'role:teacher' middleware and is redirected
    $response->assertRedirect(route('homepage'));
});
