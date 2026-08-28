<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

it('has no admin registration route', function () {
    $this->get('/admin/register')->assertNotFound();
    $this->post('/admin/register', [
        'firstName' => 'Mal',
        'lastName' => 'Actor',
        'email' => 'tardio@gmail.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertNotFound();

    expect(User::where('email', 'tardio@gmail.com')->exists())->toBeFalse();
});

it('does not create a new admin account through Google sign-in', function () {
    session(['oauth_role' => 'admin']);
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-admin-new',
        'email' => 'tardio@gmail.com',
        'name' => 'Would Be Admin',
        'email_verified' => true,
    ]));

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('admin.login'));
    $this->assertGuest();
    expect(User::where('email', 'tardio@gmail.com')->exists())->toBeFalse();
});

it('still lets an existing admin sign in with email and password', function () {
    $admin = User::factory()->admin()->create([
        'email' => 'realadmin@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post(route('admin.login.submit'), [
        'email' => 'realadmin@example.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($admin);
});
