<?php

use App\Models\Section;
use App\Models\User;

it('shows the student\'s real section on their profile page', function () {
    $section = Section::factory()->create(['name' => 'Grade 10 - Newton']);
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    $response = $this->actingAs($student)->get(route('student.dashboard'));

    $response->assertOk();
    $response->assertSee('Grade 10 - Newton');
});

it('shows an empty section field for a student without one yet', function () {
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => null,
    ]);

    $response = $this->actingAs($student)->get(route('student.dashboard'));

    $response->assertOk();
    $response->assertSee('No section selected yet');
});
