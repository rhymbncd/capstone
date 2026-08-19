<?php

use App\Models\Section;
use App\Models\User;

it('renders the student dashboard for an approved student', function () {
    $section = Section::factory()->create();
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    $response = $this->actingAs($student)->get(route('student.dashboard'));

    $response->assertOk();
});

it('renders the learning modules page for an approved student', function () {
    $section = Section::factory()->create();
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    $response = $this->actingAs($student)->get(route('student.modules'));

    $response->assertOk();
});

it('blocks guests from the learning modules page', function () {
    $response = $this->get(route('student.modules'));

    $response->assertRedirect(route('student.login'));
});
