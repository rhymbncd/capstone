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
    expect($freshData['status'])->toBe('Needs Help');
    expect($freshData['avgPre'])->toBeNull();
    expect($freshData['avgPost'])->toBeNull();
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
