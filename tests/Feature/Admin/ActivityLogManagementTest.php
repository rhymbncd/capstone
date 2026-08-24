<?php

use App\Models\ActivityLog;
use App\Models\User;

function actingAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('lists active activity logs with pagination', function () {
    $admin = actingAdmin();
    ActivityLog::factory()->count(30)->create();

    $response = $this->actingAs($admin)->getJson(route('admin.activity.index'));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(25);
    expect($response->json('meta.total'))->toBe(30);
    expect($response->json('meta.last_page'))->toBe(2);
});

it('excludes archived logs from the default listing', function () {
    $admin = actingAdmin();
    ActivityLog::factory()->create(['title' => 'Active Event']);
    ActivityLog::factory()->archived()->create(['title' => 'Archived Event']);

    $response = $this->actingAs($admin)->getJson(route('admin.activity.index'));

    $titles = collect($response->json('data'))->pluck('title');
    expect($titles)->toContain('Active Event');
    expect($titles)->not->toContain('Archived Event');
});

it('lists archived logs when requested', function () {
    $admin = actingAdmin();
    ActivityLog::factory()->create(['title' => 'Active Event']);
    ActivityLog::factory()->archived()->create(['title' => 'Archived Event']);

    $response = $this->actingAs($admin)->getJson(route('admin.activity.index', ['archived' => 1]));

    $titles = collect($response->json('data'))->pluck('title');
    expect($titles)->toContain('Archived Event');
    expect($titles)->not->toContain('Active Event');
});

it('searches by title, sub, and user name', function () {
    $admin = actingAdmin();
    ActivityLog::factory()->create(['title' => 'Student Logged In', 'sub' => 'Juan Dela Cruz (juan@example.com)', 'user_name' => 'Juan Dela Cruz']);
    ActivityLog::factory()->create(['title' => 'Teacher Logged In', 'sub' => 'Maria Santos (maria@example.com)', 'user_name' => 'Maria Santos']);

    $response = $this->actingAs($admin)->getJson(route('admin.activity.index', ['q' => 'juan@example.com']));

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.title'))->toBe('Student Logged In');
});

it('filters by activity type', function () {
    $admin = actingAdmin();
    ActivityLog::factory()->create(['type' => 'login']);
    ActivityLog::factory()->create(['type' => 'registration']);

    $response = $this->actingAs($admin)->getJson(route('admin.activity.index', ['type' => 'login']));

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.type'))->toBe('login');
});

it('filters by user role', function () {
    $admin = actingAdmin();
    ActivityLog::factory()->create(['user_role' => 'student']);
    ActivityLog::factory()->create(['user_role' => 'teacher']);

    $response = $this->actingAs($admin)->getJson(route('admin.activity.index', ['role' => 'teacher']));

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.userRole'))->toBe('teacher');
});

it('filters by date range', function () {
    $admin = actingAdmin();
    ActivityLog::factory()->create(['title' => 'Old Event', 'created_at' => now()->subDays(10)]);
    ActivityLog::factory()->create(['title' => 'Recent Event', 'created_at' => now()->subDay()]);

    $response = $this->actingAs($admin)->getJson(route('admin.activity.index', [
        'from' => now()->subDays(2)->toDateString(),
        'to' => now()->toDateString(),
    ]));

    $titles = collect($response->json('data'))->pluck('title');
    expect($titles)->toContain('Recent Event');
    expect($titles)->not->toContain('Old Event');
});

it('deletes a single activity log', function () {
    $admin = actingAdmin();
    $log = ActivityLog::factory()->create();

    $response = $this->actingAs($admin)->deleteJson(route('admin.activity.destroy', $log));

    $response->assertOk();
    $this->assertDatabaseMissing('activity_logs', ['id' => $log->id]);
});

it('archives logs older than the chosen retention period instead of deleting them', function () {
    $admin = actingAdmin();
    $old = ActivityLog::factory()->create(['created_at' => now()->subDays(45)]);
    $recent = ActivityLog::factory()->create(['created_at' => now()->subDays(5)]);

    $response = $this->actingAs($admin)->postJson(route('admin.activity.archive-old'), ['days' => 30]);

    $response->assertOk();
    expect($response->json('count'))->toBe(1);
    expect($old->fresh()->archived_at)->not->toBeNull();
    expect($recent->fresh()->archived_at)->toBeNull();
    $this->assertDatabaseHas('activity_logs', ['id' => $old->id]); // archived, not deleted
});

it('rejects an invalid retention period for clearing old logs', function () {
    $admin = actingAdmin();

    $response = $this->actingAs($admin)->postJson(route('admin.activity.archive-old'), ['days' => 45]);

    $response->assertStatus(422);
});

it('restores an archived log back to the active timeline', function () {
    $admin = actingAdmin();
    $log = ActivityLog::factory()->archived()->create();

    $response = $this->actingAs($admin)->postJson(route('admin.activity.restore', $log));

    $response->assertOk();
    expect($log->fresh()->archived_at)->toBeNull();
});

it('returns unpaginated export data honoring the active filters', function () {
    $admin = actingAdmin();
    ActivityLog::factory()->count(3)->create(['type' => 'login']);
    ActivityLog::factory()->count(2)->create(['type' => 'registration']);

    $response = $this->actingAs($admin)->getJson(route('admin.activity.export-data', ['type' => 'login']));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(3);
});

it('blocks non-admins from viewing the activity log', function () {
    $student = User::factory()->create(['role' => 'student']);

    $response = $this->actingAs($student)->getJson(route('admin.activity.index'));

    $response->assertRedirect(route('homepage'));
});

it('blocks non-admins from deleting activity logs', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $log = ActivityLog::factory()->create();

    $response = $this->actingAs($teacher)->deleteJson(route('admin.activity.destroy', $log));

    $response->assertRedirect(route('homepage'));
    $this->assertDatabaseHas('activity_logs', ['id' => $log->id]);
});

it('blocks non-admins from archiving old logs', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);

    $response = $this->actingAs($teacher)->postJson(route('admin.activity.archive-old'), ['days' => 30]);

    $response->assertRedirect(route('homepage'));
});

it('blocks guests entirely', function () {
    $response = $this->getJson(route('admin.activity.index'));

    $response->assertUnauthorized();
});

it('returns 304 with an empty body when polled again with the same ETag', function () {
    $admin = actingAdmin();
    ActivityLog::factory()->count(3)->create();

    $first = $this->actingAs($admin)->getJson(route('admin.activity.index'));
    $first->assertOk();
    $etag = $first->headers->get('ETag');
    expect($etag)->not->toBeNull();

    $second = $this->actingAs($admin)->getJson(route('admin.activity.index'), ['If-None-Match' => $etag]);

    $second->assertStatus(304);
    expect($second->getContent())->toBe('');
});

it('returns a fresh ETag and payload once a new event is recorded', function () {
    $admin = actingAdmin();
    ActivityLog::factory()->create();

    $first = $this->actingAs($admin)->getJson(route('admin.activity.index'));
    $etag = $first->headers->get('ETag');

    ActivityLog::factory()->create(['title' => 'Brand New Event']);

    $second = $this->actingAs($admin)->getJson(route('admin.activity.index'), ['If-None-Match' => $etag]);

    $second->assertOk();
    expect($second->headers->get('ETag'))->not->toBe($etag);
    expect(collect($second->json('data'))->pluck('title'))->toContain('Brand New Event');
});
