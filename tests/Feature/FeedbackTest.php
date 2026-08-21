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

it('lets a teacher send multiple feedback messages to the same student over time', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $section = Section::factory()->create(['teacher_id' => $teacher->id]);
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    $this->actingAs($teacher)->postJson(route('teacher.feedback.store'), [
        'student_id' => $student->id,
        'type' => 'encouragement',
        'message' => 'Keep going, you are improving!',
    ])->assertCreated();

    $this->actingAs($teacher)->postJson(route('teacher.feedback.store'), [
        'student_id' => $student->id,
        'type' => 'praise',
        'message' => 'Excellent score on this week\'s quiz!',
    ])->assertCreated();

    expect(TeacherFeedback::where('teacher_id', $teacher->id)->where('student_id', $student->id)->count())->toBe(2);

    // The teacher's feedback list surfaces the full history for that student.
    $index = $this->actingAs($teacher)->getJson(route('teacher.feedback.index'));
    $index->assertOk();
    $forStudent = collect($index->json('feedback'))->where('studentId', $student->id);
    expect($forStudent)->toHaveCount(2);
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

it('returns 304 for a teacher polling their feedback list with an unchanged ETag', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $section = Section::factory()->create(['teacher_id' => $teacher->id]);
    $student = User::factory()->create(['role' => 'student', 'approval_status' => 'approved', 'section_id' => $section->id]);
    TeacherFeedback::factory()->create(['teacher_id' => $teacher->id, 'student_id' => $student->id]);

    $first = $this->actingAs($teacher)->getJson(route('teacher.feedback.index'));
    $first->assertOk();
    $etag = $first->headers->get('ETag');
    expect($etag)->not->toBeNull();

    $second = $this->actingAs($teacher)->getJson(route('teacher.feedback.index'), ['If-None-Match' => $etag]);

    $second->assertStatus(304);
    expect($second->getContent())->toBe('');
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
