<?php

use App\Models\PlatformSetting;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('reads settings as a key/value map', function () {
    PlatformSetting::create(['key' => 'platform_name', 'value' => 'Bubog Math']);

    $this->actingAs($this->admin)->getJson(route('admin.settings.index'))
        ->assertOk()
        ->assertJsonPath('settings.platform_name', 'Bubog Math');
});

it('saves whitelisted keys and ignores unknown ones', function () {
    $this->actingAs($this->admin)->putJson(route('admin.settings.update'), [
        'settings' => [
            'platform_desc' => 'A new description',
            'notif_errors' => 'true',
            'is_admin' => '1',            // not whitelisted
            'role' => 'superadmin',       // not whitelisted
        ],
    ])->assertOk();

    $this->assertDatabaseHas('platform_settings', ['key' => 'platform_desc', 'value' => 'A new description']);
    $this->assertDatabaseHas('platform_settings', ['key' => 'notif_errors', 'value' => 'true']);
    $this->assertDatabaseMissing('platform_settings', ['key' => 'is_admin']);
    $this->assertDatabaseMissing('platform_settings', ['key' => 'role']);
});

it('flows a saved platform_desc through to the homepage', function () {
    $this->actingAs($this->admin)->putJson(route('admin.settings.update'), [
        'settings' => ['platform_desc' => 'Homepage says hi'],
    ])->assertOk();

    $this->get('/')->assertSee('Homepage says hi');
});

it('blocks a teacher', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $this->actingAs($teacher)->get(route('admin.settings.index'))->assertRedirect(route('homepage'));
});

it('blocks a guest', function () {
    $this->get(route('admin.settings.index'))->assertRedirect(route('admin.login'));
    $this->assertGuest();
});
