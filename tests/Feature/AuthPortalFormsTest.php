<?php

use function Pest\Laravel\get;

it('renders the teacher registration form without error', function () {
    get(route('teacher.register.form'))->assertOk();
});

it('renders the admin registration form without error', function () {
    get(route('admin.register.form'))->assertOk();
});

it('renders the student registration form without error', function () {
    get(route('student.register.form'))->assertOk();
});

it('defaults the teacher login form to submit as a teacher login', function () {
    $response = get(route('teacher.login'));

    $response->assertOk();
    $response->assertSee("let currentRole = 'teacher'", false);
});

it('defaults the admin login form to submit as an admin login', function () {
    $response = get(route('admin.login'));

    $response->assertOk();
    $response->assertSee("let currentRole = 'admin'", false);
});

it('defaults the student login form to submit as a student login', function () {
    $response = get(route('student.login'));

    $response->assertOk();
    $response->assertSee("let currentRole = 'student'", false);
});

it('defaults the teacher registration form to submit as a teacher registration', function () {
    $response = get(route('teacher.register.form'));

    $response->assertOk();
    $response->assertSee("let currentRole = 'teacher'", false);
});

it('defaults the admin registration form to submit as an admin registration', function () {
    $response = get(route('admin.register.form'));

    $response->assertOk();
    $response->assertSee("let currentRole = 'admin'", false);
});
