<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

it('emails a student a reset link scoped to the student portal', function () {
    Notification::fake();

    $student = User::factory()->create(['role' => 'student']);

    $response = $this->post(route('student.password.email'), ['email' => $student->email]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    Notification::assertSentTo($student, ResetPassword::class, function (ResetPassword $notification) use ($student) {
        return str_contains($notification->toMail($student)->actionUrl, '/student/reset-password/');
    });
});

it('emails a teacher a reset link scoped to the teacher portal', function () {
    Notification::fake();

    $teacher = User::factory()->teacher()->create();

    $response = $this->post(route('teacher.password.email'), ['email' => $teacher->email]);

    $response->assertRedirect();
    Notification::assertSentTo($teacher, ResetPassword::class, function (ResetPassword $notification) use ($teacher) {
        return str_contains($notification->toMail($teacher)->actionUrl, '/teacher/reset-password/');
    });
});

it('does not leak whether an email exists, and does not cross portals', function () {
    Notification::fake();

    $teacher = User::factory()->teacher()->create();

    // Trying to reset a teacher's account from the student portal must not send anything.
    $response = $this->post(route('student.password.email'), ['email' => $teacher->email]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    Notification::assertNothingSent();
});

it('lets a student actually reset their password with a valid token', function () {
    $student = User::factory()->create(['role' => 'student', 'password' => Hash::make('old-password')]);

    $token = app('auth.password.broker')->createToken($student);

    $response = $this->post(route('student.password.update'), [
        'token' => $token,
        'email' => $student->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertRedirect(route('student.login'));
    $this->assertTrue(Hash::check('new-password-123', $student->fresh()->password));

    $loginResponse = $this->post(route('student.login.submit'), [
        'email' => $student->email,
        'password' => 'new-password-123',
    ]);
    $loginResponse->assertRedirect(route('student.dashboard'));
});

it('rejects a password reset with an invalid token', function () {
    $teacher = User::factory()->teacher()->create(['password' => Hash::make('old-password')]);

    $response = $this->post(route('teacher.password.update'), [
        'token' => 'not-a-real-token',
        'email' => $teacher->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertTrue(Hash::check('old-password', $teacher->fresh()->password));
});

it('shows one generic message for every reset failure', function () {
    $generic = 'That password reset link is invalid or has expired. Please request a new one.';

    // Unknown email
    $this->post(route('teacher.password.update'), [
        'token' => 'x', 'email' => 'nobody@example.com',
        'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
    ])->assertSessionHasErrors(['email' => $generic]);

    // Known email, bad token
    $teacher = User::factory()->teacher()->create();
    $this->post(route('teacher.password.update'), [
        'token' => 'wrong', 'email' => $teacher->email,
        'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
    ])->assertSessionHasErrors(['email' => $generic]);
});

it('rejects a reset token issued for a different role', function () {
    $student = User::factory()->create(['role' => 'student', 'password' => Hash::make('old-password')]);
    $token = app('auth.password.broker')->createToken($student);

    // Same email+token, but submitted against the teacher portal's broker (role mismatch).
    $response = $this->post(route('teacher.password.update'), [
        'token' => $token,
        'email' => $student->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertTrue(Hash::check('old-password', $student->fresh()->password));
});

it('renders the forgot and reset password forms for both portals', function () {
    $this->get(route('student.password.request'))->assertOk();
    $this->get(route('teacher.password.request'))->assertOk();
    $this->get(route('student.password.reset', ['token' => 'abc']))->assertOk();
    $this->get(route('teacher.password.reset', ['token' => 'abc']))->assertOk();
});

it('locks the email field on the reset form to the address from the emailed link', function () {
    $response = $this->get(route('student.password.reset', ['token' => 'abc', 'email' => 'locked@example.com']));

    $response->assertOk();
    $response->assertSee('value="locked@example.com"', false);
    $response->assertSee('readonly', false);
});

afterEach(function () {
    DB::table('password_reset_tokens')->truncate();
});
