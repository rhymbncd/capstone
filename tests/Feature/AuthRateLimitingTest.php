<?php

use App\Models\User;

it('locks a single account after 5 failed login attempts in a minute', function () {
    $user = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'email' => 'target@example.com',
        'password' => bcrypt('correct-horse'),
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('student.login.submit'), [
            'email' => 'target@example.com',
            'password' => 'wrong',
        ])->assertRedirect(route('student.login'));
    }

    $this->post(route('student.login.submit'), [
        'email' => 'target@example.com',
        'password' => 'wrong',
    ])->assertStatus(429);

    // Even the correct password is refused while the limit is in effect.
    $this->post(route('student.login.submit'), [
        'email' => 'target@example.com',
        'password' => 'correct-horse',
    ])->assertStatus(429);

    $this->assertGuest();
});

it('does not lock other accounts on the same IP', function () {
    User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'email' => 'victim@example.com',
        'password' => bcrypt('secret'),
    ]);
    $other = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'email' => 'bystander@example.com',
        'password' => bcrypt('secret'),
    ]);

    for ($i = 0; $i < 6; $i++) {
        $this->post(route('student.login.submit'), [
            'email' => 'victim@example.com',
            'password' => 'wrong',
        ]);
    }

    $this->post(route('student.login.submit'), [
        'email' => 'bystander@example.com',
        'password' => 'secret',
    ])->assertRedirect(route('student.dashboard'));

    $this->assertAuthenticatedAs($other);
});
