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

it('paginates the user list at 25 per page', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->count(30)->create(['role' => 'student', 'approval_status' => 'approved']);

    $response = $this->actingAs($admin)->getJson(route('admin.users.index'));

    $response->assertOk();
    expect($response->json('users'))->toHaveCount(25);
    expect($response->json('meta.total'))->toBe(31); // 30 students + the admin
    expect($response->json('meta.last_page'))->toBe(2);
});

it('returns platform-wide counts unaffected by the current search/role filter', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->count(3)->create(['role' => 'student', 'approval_status' => 'approved']);
    User::factory()->teacher()->count(2)->create(['approval_status' => 'approved']);

    $response = $this->actingAs($admin)->getJson(route('admin.users.index', ['role' => 'teacher']));

    $response->assertOk();
    // The filtered list only has teachers...
    expect(collect($response->json('users'))->pluck('role')->unique()->all())->toBe(['teacher']);
    // ...but counts stay platform-wide, not scoped to that filter.
    expect($response->json('counts.total'))->toBe(6);
    expect($response->json('counts.students'))->toBe(3);
    expect($response->json('counts.teachers'))->toBe(2);
});

it('searches the user list by name or email', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $match = User::factory()->create(['name' => 'Juan Dela Cruz', 'role' => 'student', 'approval_status' => 'approved']);
    User::factory()->create(['name' => 'Someone Else', 'role' => 'student', 'approval_status' => 'approved']);

    $response = $this->actingAs($admin)->getJson(route('admin.users.index', ['q' => 'Juan']));

    $response->assertOk();
    $ids = collect($response->json('users'))->pluck('id');
    expect($ids)->toContain($match->id);
    expect($ids)->toHaveCount(1);
});

it('caches the Home tab counts across requests', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->create(['role' => 'student', 'approval_status' => 'approved']);

    $first = $this->actingAs($admin)->getJson(route('admin.users.index'));
    expect($first->json('counts.total'))->toBe(2);

    // Created directly in the DB, bypassing the controller — the cached
    // count should not see this yet.
    User::factory()->create(['role' => 'student', 'approval_status' => 'approved']);

    $second = $this->actingAs($admin)->getJson(route('admin.users.index'));
    expect($second->json('counts.total'))->toBe(2); // still the cached, stale value
});

it('busts the counts cache immediately when an admin deletes a user', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);

    $first = $this->actingAs($admin)->getJson(route('admin.users.index'));
    expect($first->json('counts.total'))->toBe(2);

    $this->actingAs($admin)->deleteJson(route('admin.users.destroy', $teacher))->assertOk();

    $second = $this->actingAs($admin)->getJson(route('admin.users.index'));
    expect($second->json('counts.total'))->toBe(1); // reflects the delete right away, not after 5 minutes
});

it('busts the counts cache immediately when an admin updates a user\'s role', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create(['role' => 'student', 'approval_status' => 'approved']);

    $first = $this->actingAs($admin)->getJson(route('admin.users.index'));
    expect($first->json('counts.students'))->toBe(1);
    expect($first->json('counts.teachers'))->toBe(0);

    $this->actingAs($admin)->patchJson(route('admin.users.update', $student), [
        'name' => $student->name,
        'email' => $student->email,
        'role' => 'teacher',
        'status' => 'Active',
    ])->assertOk();

    $second = $this->actingAs($admin)->getJson(route('admin.users.index'));
    expect($second->json('counts.students'))->toBe(0);
    expect($second->json('counts.teachers'))->toBe(1);
});

it('filters the user list by role', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->count(2)->create(['role' => 'student', 'approval_status' => 'approved']);
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);

    $response = $this->actingAs($admin)->getJson(route('admin.users.index', ['role' => 'teacher']));

    $response->assertOk();
    $ids = collect($response->json('users'))->pluck('id');
    expect($ids)->toContain($teacher->id);
    expect($ids)->toHaveCount(1);
});
