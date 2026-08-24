<?php

use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('lets a teacher change their password with the correct current password', function () {
    $teacher = User::factory()->teacher()->create([
        'approval_status' => 'approved',
        'password' => Hash::make('old-password'),
    ]);

    $response = $this->actingAs($teacher)->postJson(route('teacher.account.password'), [
        'current_password' => 'old-password',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertOk();
    $teacher->refresh();
    expect(Hash::check('new-password-123', $teacher->password))->toBeTrue();
    expect(Hash::check('old-password', $teacher->password))->toBeFalse();
});

it('lets a student change their password with the correct current password', function () {
    $section = Section::factory()->create();
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
        'password' => Hash::make('old-password'),
    ]);

    $response = $this->actingAs($student)->postJson(route('student.account.password'), [
        'current_password' => 'old-password',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertOk();
    $student->refresh();
    expect(Hash::check('new-password-123', $student->password))->toBeTrue();
});

it('rejects a password change with the wrong current password', function () {
    $teacher = User::factory()->teacher()->create([
        'approval_status' => 'approved',
        'password' => Hash::make('old-password'),
    ]);

    $response = $this->actingAs($teacher)->postJson(route('teacher.account.password'), [
        'current_password' => 'totally-wrong',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('current_password');
    $teacher->refresh();
    expect(Hash::check('old-password', $teacher->password))->toBeTrue();
});

it('rejects a password change when the confirmation does not match', function () {
    $teacher = User::factory()->teacher()->create([
        'approval_status' => 'approved',
        'password' => Hash::make('old-password'),
    ]);

    $response = $this->actingAs($teacher)->postJson(route('teacher.account.password'), [
        'current_password' => 'old-password',
        'password' => 'new-password-123',
        'password_confirmation' => 'does-not-match',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('password');
});

it('rejects a password shorter than 8 characters', function () {
    $teacher = User::factory()->teacher()->create([
        'approval_status' => 'approved',
        'password' => Hash::make('old-password'),
    ]);

    $response = $this->actingAs($teacher)->postJson(route('teacher.account.password'), [
        'current_password' => 'old-password',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('password');
});

it('blocks guests from changing a password', function () {
    $response = $this->postJson(route('teacher.account.password'), [
        'current_password' => 'whatever',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertUnauthorized();
});
