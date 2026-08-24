<?php

use App\Models\ActivityLog;
use App\Models\Section;
use App\Models\User;

it('logs an event when a student registers', function () {
    $section = Section::factory()->create();

    $this->post(route('student.register'), [
        'firstName' => 'Ana',
        'lastName' => 'Reyes',
        'email' => 'ana.reyes@example.com',
        'section_id' => $section->id,
        'student_id' => '24-0099',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'type' => 'registration',
        'title' => 'New Student Registered',
    ]);
});

it('logs an event when a teacher registers', function () {
    $this->post(route('teacher.register'), [
        'firstName' => 'Mark',
        'lastName' => 'Santos',
        'email' => 'mark.santos@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'type' => 'registration',
        'title' => 'New Teacher Registered',
    ]);
});

it('logs an event when a student logs in successfully', function () {
    $student = User::factory()->create(['role' => 'student', 'password' => bcrypt('password123')]);

    $this->post(route('student.login.submit'), [
        'email' => $student->email,
        'password' => 'password123',
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'type' => 'login',
        'title' => 'Student Logged In',
    ]);
});

it('logs an event when a teacher logs in successfully', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved', 'password' => bcrypt('password123')]);

    $this->post(route('teacher.login.submit'), [
        'email' => $teacher->email,
        'password' => 'password123',
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'type' => 'login',
        'title' => 'Teacher Logged In',
    ]);
});

it('does not log a login event for a failed login attempt', function () {
    $student = User::factory()->create(['role' => 'student', 'password' => bcrypt('password123')]);

    $this->post(route('student.login.submit'), [
        'email' => $student->email,
        'password' => 'wrong-password',
    ]);

    expect(ActivityLog::where('type', 'login')->count())->toBe(0);
});

it('logs an event when a teacher approves a student', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $section = Section::factory()->create(['teacher_id' => $teacher->id]);
    $student = User::factory()->create(['role' => 'student', 'approval_status' => 'pending', 'section_id' => $section->id]);

    $this->actingAs($teacher)->post(route('teacher.student.approve', $student));

    $this->assertDatabaseHas('activity_logs', [
        'type' => 'system',
        'title' => 'Student Approved',
    ]);
});

it('logs an event when an admin approves a teacher', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $teacher = User::factory()->teacher()->create(['approval_status' => 'pending']);

    $this->actingAs($admin)->post(route('admin.teacher.approve', $teacher));

    $this->assertDatabaseHas('activity_logs', [
        'type' => 'system',
        'title' => 'Teacher Approved',
    ]);
});

it('prunes activity logs older than the retention window', function () {
    $old = ActivityLog::factory()->create(['created_at' => now()->subDays(91)]);
    $recent = ActivityLog::factory()->create(['created_at' => now()->subDays(89)]);

    $this->artisan('model:prune', ['--model' => [ActivityLog::class]]);

    $this->assertModelMissing($old);
    $this->assertModelExists($recent);
});

it('prunes an archived log the same as an active one once past the retention window', function () {
    $oldArchived = ActivityLog::factory()->archived()->create(['created_at' => now()->subDays(91)]);

    $this->artisan('model:prune', ['--model' => [ActivityLog::class]]);

    $this->assertModelMissing($oldArchived);
});
