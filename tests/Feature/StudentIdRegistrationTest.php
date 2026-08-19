<?php

use App\Models\Section;
use App\Models\User;

it('requires a student ID to register normally', function () {
    $section = Section::factory()->create();

    $response = $this->post(route('student.register'), [
        'firstName' => 'Juan',
        'lastName' => 'dela Cruz',
        'email' => 'juan@example.com',
        'section_id' => $section->id,
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('student_id');
    $this->assertDatabaseMissing('users', ['email' => 'juan@example.com']);
});

it('saves the student ID on normal registration', function () {
    $section = Section::factory()->create();

    $response = $this->post(route('student.register'), [
        'firstName' => 'Juan',
        'lastName' => 'dela Cruz',
        'email' => 'juan2@example.com',
        'section_id' => $section->id,
        'student_id' => '24-1234',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('student.login'));
    $this->assertDatabaseHas('users', ['email' => 'juan2@example.com', 'student_id' => '24-1234']);
});

it('requires a student ID to complete a Google signup', function () {
    $section = Section::factory()->create();
    $user = User::factory()->create([
        'role' => 'student',
        'google_id' => 'google-completion-1',
        'section_id' => null,
        'student_id' => null,
    ]);
    session(['google_user_id' => $user->id]);

    $response = $this->post(route('student.complete-google-signup'), [
        'section_id' => $section->id,
    ]);

    $response->assertSessionHasErrors('student_id');
    expect($user->fresh()->section_id)->toBeNull();
});

it('saves the student ID when completing a Google signup', function () {
    $section = Section::factory()->create();
    $user = User::factory()->create([
        'role' => 'student',
        'google_id' => 'google-completion-2',
        'section_id' => null,
        'student_id' => null,
    ]);
    session(['google_user_id' => $user->id]);

    $response = $this->post(route('student.complete-google-signup'), [
        'section_id' => $section->id,
        'student_id' => '24-5678',
    ]);

    $response->assertRedirect(route('student.login'));
    $user->refresh();
    expect($user->section_id)->toBe($section->id);
    expect($user->student_id)->toBe('24-5678');
    expect($user->approval_status)->toBe('pending');
});
