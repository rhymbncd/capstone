<?php

use App\Models\Section;
use App\Models\User;

it('logs a student out', function () {
    $section = Section::factory()->create();
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    $response = $this->actingAs($student)->post(route('student.logout'));

    $response->assertRedirect(route('homepage'));
    $this->assertGuest();
});

it('logs a teacher out', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);

    $response = $this->actingAs($teacher)->post(route('teacher.logout'));

    $response->assertRedirect(route('homepage'));
    $this->assertGuest();
});

it('logs an admin out', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->post(route('admin.logout'));

    $response->assertRedirect(route('homepage'));
    $this->assertGuest();
});

it('allows an admin to log in with the correct credentials', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post(route('admin.login.submit'), [
        'email' => $admin->email,
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($admin);
});
