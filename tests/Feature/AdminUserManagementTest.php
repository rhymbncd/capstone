<?php

use App\Models\Section;
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

it('prevents an admin from deleting their own account', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->deleteJson(route('admin.users.destroy', $admin));

    $response->assertForbidden();
    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});
