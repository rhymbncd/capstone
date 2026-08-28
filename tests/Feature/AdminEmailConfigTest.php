<?php

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

it('honours config(admin.emails) for Google admin sign-in', function () {
    config(['admin.emails' => ['boss@school.edu']]);

    User::factory()->admin()->create(['email' => 'boss@school.edu', 'google_id' => 'g-boss']);

    session(['oauth_role' => 'admin']);
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'g-boss',
        'email' => 'boss@school.edu',
        'name' => 'The Boss',
        'email_verified' => true,
    ]));

    $this->get(route('auth.google.callback'))->assertRedirect(route('admin.dashboard'));
});

it('rejects an admin email that is not in config', function () {
    config(['admin.emails' => ['boss@school.edu']]);

    session(['oauth_role' => 'admin']);
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'g-rando',
        'email' => 'random@example.com',
        'name' => 'Random',
        'email_verified' => true,
    ]));

    $this->get(route('auth.google.callback'))->assertRedirect(route('admin.login'));
    $this->assertGuest();
});

it('AdminSeeder does nothing without ADMIN_SEED_PASSWORD', function () {
    config(['admin.emails' => ['a@school.edu', 'b@school.edu']]);
    putenv('ADMIN_SEED_PASSWORD');

    (new AdminSeeder)->run();

    expect(User::where('role', 'admin')->count())->toBe(0);
});

it('AdminSeeder creates admins with the supplied password and never overwrites', function () {
    config(['admin.emails' => ['a@school.edu']]);
    putenv('ADMIN_SEED_PASSWORD=s3cret-seed-pw');

    $existing = User::factory()->admin()->create(['email' => 'a@school.edu', 'password' => Hash::make('their-own-password')]);

    (new AdminSeeder)->run();

    expect(User::where('email', 'a@school.edu')->count())->toBe(1);
    // Existing account's password is left untouched.
    expect(Hash::check('their-own-password', $existing->fresh()->password))->toBeTrue();

    putenv('ADMIN_SEED_PASSWORD');
});
