<?php

use App\Models\Section;
use App\Models\TeacherFeedback;
use App\Models\User;

it('lets a teacher send feedback to a student in their own section', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $section = Section::factory()->create(['teacher_id' => $teacher->id]);
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    $response = $this->actingAs($teacher)->postJson(route('teacher.feedback.store'), [
        'student_id' => $student->id,
        'type' => 'praise',
        'message' => 'Great work on the arithmetic sequence quiz!',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('teacher_feedback', [
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
        'type' => 'praise',
    ]);
});

it('prevents a teacher from sending feedback to a student outside their section', function () {
    $teacherA = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $teacherB = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $sectionB = Section::factory()->create(['teacher_id' => $teacherB->id]);
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $sectionB->id,
    ]);

    $response = $this->actingAs($teacherA)->postJson(route('teacher.feedback.store'), [
        'student_id' => $student->id,
        'type' => 'praise',
        'message' => 'This should not be allowed.',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('teacher_feedback', ['student_id' => $student->id]);
});

it('validates the feedback message and type', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $section = Section::factory()->create(['teacher_id' => $teacher->id]);
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    $response = $this->actingAs($teacher)->postJson(route('teacher.feedback.store'), [
        'student_id' => $student->id,
        'type' => 'not-a-real-type',
        'message' => 'hi',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['type', 'message']);
});

it('lets a student see only their own received feedback', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $section = Section::factory()->create(['teacher_id' => $teacher->id]);
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);
    $otherStudent = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    TeacherFeedback::factory()->create([
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
        'message' => 'For you',
    ]);
    TeacherFeedback::factory()->create([
        'teacher_id' => $teacher->id,
        'student_id' => $otherStudent->id,
        'message' => 'Not for you',
    ]);

    $response = $this->actingAs($student)->getJson(route('student.feedback.index'));

    $response->assertOk();
    $messages = collect($response->json('feedback'))->pluck('message');
    expect($messages)->toContain('For you');
    expect($messages)->not->toContain('Not for you');
});

it('marks all of a student\'s feedback as read', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $section = Section::factory()->create(['teacher_id' => $teacher->id]);
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    TeacherFeedback::factory()->count(3)->create([
        'teacher_id' => $teacher->id,
        'student_id' => $student->id,
    ]);

    $response = $this->actingAs($student)->postJson(route('student.feedback.read-all'));

    $response->assertOk();
    expect(TeacherFeedback::where('student_id', $student->id)->whereNull('read_at')->count())->toBe(0);
});

it('does not let a student mark another student\'s feedback as read via mismatched ownership', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $section = Section::factory()->create(['teacher_id' => $teacher->id]);
    $studentA = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);
    $studentB = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    TeacherFeedback::factory()->create([
        'teacher_id' => $teacher->id,
        'student_id' => $studentB->id,
    ]);

    $this->actingAs($studentA)->postJson(route('student.feedback.read-all'))->assertOk();

    expect(TeacherFeedback::where('student_id', $studentB->id)->whereNull('read_at')->count())->toBe(1);
});
