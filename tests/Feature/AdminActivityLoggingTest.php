<?php

use App\Models\ActivityLog;
use App\Models\User;

it('records who edited a user account', function () {
    $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin One']);
    $student = User::factory()->create(['role' => 'student', 'approval_status' => 'approved']);

    $this->actingAs($admin)->patchJson(route('admin.users.update', $student), [
        'name' => 'Renamed Student',
        'email' => 'renamed@example.com',
        'role' => 'student',
        'status' => 'Inactive',
    ])->assertOk();

    $log = ActivityLog::where('title', 'User Account Edited')->first();
    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($admin->id);
    expect($log->sub)->toContain('renamed@example.com');
});

it('records who deleted a user account, naming the deleted user', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $teacher = User::factory()->teacher()->create([
        'approval_status' => 'approved',
        'email' => 'gone@example.com',
    ]);

    $this->actingAs($admin)->deleteJson(route('admin.users.destroy', $teacher))->assertOk();

    $log = ActivityLog::where('title', 'User Account Deleted')->first();
    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($admin->id);
    expect($log->sub)->toContain('gone@example.com');
});

it('records a failed login attempt without the password', function () {
    User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'email' => 'realuser@example.com',
        'password' => bcrypt('the-real-password'),
    ]);

    $this->post(route('student.login.submit'), [
        'email' => 'realuser@example.com',
        'password' => 'guessing-wrong',
    ]);

    $log = ActivityLog::where('title', 'Failed Login Attempt')->first();
    expect($log)->not->toBeNull();
    expect($log->sub)->toContain('realuser@example.com');
    expect($log->sub)->not->toContain('guessing-wrong');
});
