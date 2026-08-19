<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

afterEach(function () {
    // Never let maintenance mode leak from one test into the next.
    Artisan::call('up');
});

it('reports maintenance mode as off by default', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->getJson(route('admin.maintenance.status'));

    $response->assertOk();
    $response->assertJson(['down' => false]);
});

it('lets an admin enable maintenance mode, which blocks public routes', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $toggle = $this->actingAs($admin)->postJson(route('admin.maintenance.toggle'), ['enable' => true]);
    $toggle->assertOk();
    $toggle->assertJson(['down' => true]);

    $this->get(route('homepage'))->assertStatus(503);
});

it('keeps the admin portal reachable while maintenance mode is on', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)->postJson(route('admin.maintenance.toggle'), ['enable' => true]);

    // A fresh admin-authenticated request must still get through.
    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
});

it('lets an admin disable maintenance mode again', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)->postJson(route('admin.maintenance.toggle'), ['enable' => true]);

    $toggle = $this->actingAs($admin)->postJson(route('admin.maintenance.toggle'), ['enable' => false]);
    $toggle->assertOk();
    $toggle->assertJson(['down' => false]);

    $this->get(route('homepage'))->assertOk();
});

it('logs an activity event for each maintenance mode toggle', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->postJson(route('admin.maintenance.toggle'), ['enable' => true]);
    $this->assertDatabaseHas('activity_logs', ['type' => 'system', 'title' => 'Maintenance Mode Enabled']);

    $this->actingAs($admin)->postJson(route('admin.maintenance.toggle'), ['enable' => false]);
    $this->assertDatabaseHas('activity_logs', ['type' => 'system', 'title' => 'Maintenance Mode Disabled']);
});
