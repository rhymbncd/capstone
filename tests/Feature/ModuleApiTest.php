<?php

use App\Models\ModuleStatus;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('supabase');
    Storage::disk('supabase')->buildTemporaryUrlsUsing(
        fn (string $path, $expiry) => "https://signed.test/{$path}",
    );
    $this->teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $this->admin = User::factory()->admin()->create();
});

it('lets a teacher upload a module which lands pending', function () {
    $this->actingAs($this->teacher)->post(route('modules.store'), [
        'file' => UploadedFile::fake()->create('lesson.pdf', 200, 'application/pdf'),
        'module_title' => 'Arithmetic Sequences',
        'module_desc' => 'Intro lesson',
        'module_topic' => 'Module 1: Sequences and Series',
    ])->assertStatus(201);

    $row = ModuleStatus::first();
    expect($row->status)->toBe('pending');
    expect($row->module_title)->toBe('Arithmetic Sequences');
    Storage::disk('supabase')->assertExists($row->file_name);
});

it('lets an admin approve, and a teacher cannot', function () {
    $module = ModuleStatus::create(['file_name' => '1_x.pdf', 'status' => 'pending', 'module_title' => 'X']);
    Storage::disk('supabase')->put('1_x.pdf', 'data');

    $this->actingAs($this->teacher)
        ->patchJson(route('modules.updateStatus', $module), ['status' => 'approved'])
        ->assertForbidden();

    $this->actingAs($this->admin)
        ->patchJson(route('modules.updateStatus', $module), ['status' => 'approved'])
        ->assertOk();

    $module->refresh();
    expect($module->status)->toBe('approved');
    expect($module->reviewed_at)->not->toBeNull();
});

it('updates module metadata via POST method-spoofing', function () {
    $module = ModuleStatus::create(['file_name' => '1_old.pdf', 'status' => 'pending', 'module_title' => 'Old']);
    Storage::disk('supabase')->put('1_old.pdf', 'data');

    $this->actingAs($this->teacher)->post(route('modules.update', $module), [
        '_method' => 'PATCH',
        'module_title' => 'New Title',
        'module_topic' => 'Module 1: Sequences and Series',
    ])->assertOk();

    expect($module->fresh()->module_title)->toBe('New Title');
});

it('deletes the file and the row', function () {
    $module = ModuleStatus::create(['file_name' => '1_gone.pdf', 'status' => 'pending', 'module_title' => 'Gone']);
    Storage::disk('supabase')->put('1_gone.pdf', 'data');

    $this->actingAs($this->admin)->deleteJson(route('modules.destroy', $module))->assertOk();

    Storage::disk('supabase')->assertMissing('1_gone.pdf');
    $this->assertDatabaseMissing('module_status', ['id' => $module->id]);
});

it('opens a pending row for a file that has no moderation record yet', function () {
    Storage::disk('supabase')->put('1_orphan.pdf', 'data');

    $response = $this->actingAs($this->admin)->getJson(route('modules.index'));

    $response->assertOk();
    expect($response->json('modules'))->toHaveCount(1);
    $this->assertDatabaseHas('module_status', ['file_name' => '1_orphan.pdf', 'status' => 'pending']);
});

it('blocks students from the staff module endpoints', function () {
    $student = User::factory()->create([
        'role' => 'student', 'approval_status' => 'approved',
        'section_id' => Section::factory()->create()->id,
    ]);

    $this->actingAs($student)->getJson(route('modules.index'))->assertForbidden();
});

it('rejects a non-file upload', function () {
    $this->actingAs($this->teacher)->postJson(route('modules.store'), [
        'module_title' => 'No file here',
    ])->assertUnprocessable();
});
