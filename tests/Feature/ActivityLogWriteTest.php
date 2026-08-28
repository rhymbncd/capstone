<?php

use App\Models\ActivityLog;
use App\Models\Section;
use App\Models\User;

it('records a client event tied to the authenticated actor', function () {
    $admin = User::factory()->admin()->create(['name' => 'Real Admin']);

    $this->actingAs($admin)->postJson(route('activity-log.store'), [
        'type' => 'content',
        'title' => 'Report Generated',
        'sub' => 'Users PDF downloaded',
        'badge' => 'Exported',
    ])->assertOk()->assertJson(['logged' => true]);

    $log = ActivityLog::where('title', 'Report Generated')->first();
    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($admin->id);
    expect($log->user_name)->toBe('Real Admin');
    expect($log->user_role)->toBe('admin');
});

it('cannot forge the actor via the request body', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved', 'name' => 'Teacher T']);
    $victim = User::factory()->admin()->create();

    $this->actingAs($teacher)->postJson(route('activity-log.store'), [
        'type' => 'system',
        'title' => 'Settings Updated',
        'user_id' => $victim->id,
        'user_name' => 'Someone Else',
        'user_role' => 'admin',
    ])->assertOk();

    $log = ActivityLog::where('title', 'Settings Updated')->first();
    expect($log->user_id)->toBe($teacher->id);
    expect($log->user_name)->toBe('Teacher T');
    expect($log->user_role)->toBe('teacher');
});

it('rejects an unknown activity type', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->postJson(route('activity-log.store'), [
        'type' => 'hacking',
        'title' => 'Nope',
    ])->assertUnprocessable();
});

it('requires authentication', function () {
    $this->postJson(route('activity-log.store'), ['type' => 'system', 'title' => 'x'])
        ->assertUnauthorized();
});

it('is available to students too but still bound to their session', function () {
    $student = User::factory()->create([
        'role' => 'student', 'approval_status' => 'approved',
        'section_id' => Section::factory()->create()->id,
    ]);

    $this->actingAs($student)->postJson(route('activity-log.store'), [
        'type' => 'content', 'title' => 'Something',
    ])->assertOk();

    expect(ActivityLog::where('title', 'Something')->value('user_role'))->toBe('student');
});
