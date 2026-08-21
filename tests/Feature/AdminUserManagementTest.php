<?php

use App\Models\ActivityLog;
use App\Models\Section;
use App\Models\StudentProgress;
use App\Models\StudentQuizAnswer;
use App\Models\User;

it('lists real registered users for the admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $section = Section::factory()->create();
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    $response = $this->actingAs($admin)->getJson(route('admin.users.index'));

    $response->assertOk();
    $ids = collect($response->json('users'))->pluck('id');
    expect($ids)->toContain($admin->id, $student->id);
});

it('returns 304 when the admin polls the user list with an unchanged ETag', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->create(['role' => 'student', 'approval_status' => 'approved']);

    $first = $this->actingAs($admin)->getJson(route('admin.users.index'));
    $first->assertOk();
    $etag = $first->headers->get('ETag');
    expect($etag)->not->toBeNull();

    $second = $this->actingAs($admin)->getJson(route('admin.users.index'), ['If-None-Match' => $etag]);

    $second->assertStatus(304);
    expect($second->getContent())->toBe('');
});

it('returns a fresh ETag once a user is updated', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create(['role' => 'student', 'approval_status' => 'pending']);

    $first = $this->actingAs($admin)->getJson(route('admin.users.index'));
    $etag = $first->headers->get('ETag');

    $student->update(['approval_status' => 'approved']);

    $second = $this->actingAs($admin)->getJson(route('admin.users.index'), ['If-None-Match' => $etag]);

    $second->assertOk();
    expect($second->headers->get('ETag'))->not->toBe($etag);
});

it('includes each student\'s student ID for the admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $section = Section::factory()->create();
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
        'student_id' => '24-8181',
    ]);

    $response = $this->actingAs($admin)->getJson(route('admin.users.index'));

    $response->assertOk();
    $data = collect($response->json('users'))->firstWhere('id', $student->id);
    expect($data['studentId'])->toBe('24-8181');
});

it('lets an admin update another user\'s name, email, and role', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $section = Section::factory()->create();
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    $response = $this->actingAs($admin)->patchJson(route('admin.users.update', $student), [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'role' => 'teacher',
        'status' => 'Active',
    ]);

    $response->assertOk();
    $student->refresh();
    expect($student->name)->toBe('Updated Name');
    expect($student->email)->toBe('updated@example.com');
    expect($student->role)->toBe('teacher');
    expect($student->section_id)->toBeNull(); // no longer a student, so unassigned from section
});

it('prevents an admin from editing their own account here', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->patchJson(route('admin.users.update', $admin), [
        'name' => 'New Name',
        'email' => $admin->email,
        'role' => 'admin',
        'status' => 'Active',
    ]);

    $response->assertForbidden();
});

it('lets an admin delete another user', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);

    $response = $this->actingAs($admin)->deleteJson(route('admin.users.destroy', $teacher));

    $response->assertOk();
    $this->assertDatabaseMissing('users', ['id' => $teacher->id]);
});

it('deletes a student\'s progress and quiz answers along with their account', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $section = Section::factory()->create();
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    StudentProgress::create([
        'session_id' => (string) $student->id,
        'topic_key' => 'ari',
        'phase' => 'post',
        'score' => 8,
        'total' => 10,
        'passed' => true,
        'student_name' => $student->name,
    ]);
    StudentQuizAnswer::create([
        'session_id' => (string) $student->id,
        'student_name' => $student->name,
        'topic_key' => 'ari',
        'phase' => 'post',
        'answers' => [['question' => '1+1', 'selected' => '2', 'correct' => '2', 'isCorrect' => true]],
        'score' => 8,
        'total' => 10,
    ]);
    ActivityLog::record('login', 'Student Logged In', "{$student->name} ({$student->email})", user: $student);

    $response = $this->actingAs($admin)->deleteJson(route('admin.users.destroy', $student));

    $response->assertOk();
    $this->assertDatabaseMissing('users', ['id' => $student->id]);
    $this->assertDatabaseMissing('student_progress', ['session_id' => (string) $student->id]);
    $this->assertDatabaseMissing('student_quiz_answers', ['session_id' => (string) $student->id]);
    $this->assertDatabaseMissing('activity_logs', ['user_id' => $student->id]);
});

it('prevents an admin from deleting their own account', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->deleteJson(route('admin.users.destroy', $admin));

    $response->assertForbidden();
    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});
