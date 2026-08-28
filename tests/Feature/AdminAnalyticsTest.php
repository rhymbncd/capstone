<?php

use App\Models\Section;
use App\Models\StudentProgress;
use App\Models\User;

it('returns platform-wide progress rows to an admin', function () {
    $admin = User::factory()->admin()->create();

    StudentProgress::create(['session_id' => '1', 'topic_key' => 'ari', 'phase' => 'post', 'score' => 9, 'total' => 10, 'passed' => true]);
    StudentProgress::create(['session_id' => '2', 'topic_key' => 'geo', 'phase' => 'pre', 'score' => 4, 'total' => 10, 'passed' => false]);

    $response = $this->actingAs($admin)->getJson(route('admin.analytics.progress'));

    $response->assertOk();
    expect($response->json('progress'))->toHaveCount(2);
});

it('blocks non-admins from analytics', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $this->actingAs($teacher)->get(route('admin.analytics.progress'))->assertRedirect(route('homepage'));

    $student = User::factory()->create([
        'role' => 'student', 'approval_status' => 'approved',
        'section_id' => Section::factory()->create()->id,
    ]);
    $this->actingAs($student)->get(route('admin.analytics.progress'))->assertRedirect(route('homepage'));
});
