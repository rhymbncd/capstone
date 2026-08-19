<?php

use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

it('links Google to an existing password-based account instead of crashing', function () {
    $existing = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'email' => 'already-registered@example.com',
        'password' => Hash::make('password123'),
        'google_id' => null,
        'section_id' => Section::factory()->create()->id,
    ]);

    session(['oauth_role' => 'student']);
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-id-1',
        'email' => 'already-registered@example.com',
        'name' => 'Already Registered',
    ]));

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('student.dashboard'));
    $this->assertAuthenticatedAs($existing);

    $existing->refresh();
    expect($existing->google_id)->toBe('google-id-1');
    // Linking must not touch the account's existing approval — it was already approved.
    expect($existing->approval_status)->toBe('approved');
});

it('does not silently re-approve an account a teacher/admin has since rejected', function () {
    $section = Section::factory()->create();
    $existing = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'email' => 'reset-after-google@example.com',
        'google_id' => 'google-id-2',
        'section_id' => $section->id,
    ]);

    // Teacher resets them back to pending after they'd already linked Google once.
    $existing->update(['approval_status' => 'pending']);

    session(['oauth_role' => 'student']);
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-id-2',
        'email' => 'reset-after-google@example.com',
        'name' => 'Reset After Google',
    ]));

    $response = $this->get(route('auth.google.callback'));

    // Must not be silently logged in / re-approved — should be sent back to login, still pending.
    $response->assertRedirect(route('student.login'));
    $this->assertGuest();
    expect($existing->fresh()->approval_status)->toBe('pending');
});

it('sends a brand-new Google student signup to section selection', function () {
    session(['oauth_role' => 'student']);
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-id-3',
        'email' => 'new-google-student@example.com',
        'name' => 'New Google Student',
    ]));

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('student.complete-google-signup'));
    $user = User::where('email', 'new-google-student@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->section_id)->toBeNull();
});

it('sends an existing student missing a section to section selection too', function () {
    // e.g. an account that was linked to Google before section selection existed,
    // or was otherwise left without a section for any reason.
    $existing = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'email' => 'no-section-yet@example.com',
        'google_id' => 'google-id-4',
        'section_id' => null,
    ]);

    session(['oauth_role' => 'student']);
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-id-4',
        'email' => 'no-section-yet@example.com',
        'name' => 'No Section Yet',
    ]));

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('student.complete-google-signup'));
    $this->assertGuest();
});
