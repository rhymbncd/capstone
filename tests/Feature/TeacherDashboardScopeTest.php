<?php

use App\Models\Section;
use App\Models\User;

it('only shows a teacher their own pending students on the dashboard, not every teacher\'s', function () {
    $teacherA = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $teacherB = User::factory()->teacher()->create(['approval_status' => 'approved']);

    $sectionA = Section::factory()->create(['teacher_id' => $teacherA->id]);
    $sectionB = Section::factory()->create(['teacher_id' => $teacherB->id]);

    $ownPending = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'pending',
        'section_id' => $sectionA->id,
    ]);
    $otherTeachersPending = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'pending',
        'section_id' => $sectionB->id,
        'name' => 'Should Not Leak',
    ]);

    $response = $this->actingAs($teacherA)->get(route('teacher.dashboard'));

    $response->assertOk();
    $response->assertSee($ownPending->name);
    $response->assertDontSee('Should Not Leak');
});

it('serves the dashboard shell with no-store so a redeploy is not masked by browser cache', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);

    $response = $this->actingAs($teacher)->get(route('teacher.dashboard'));

    $response->assertOk();
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});
