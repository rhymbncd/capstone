<?php

use App\Models\ModuleStatus;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('supabase');
    Storage::fake('supabase_materials');
    Storage::disk('supabase')->buildTemporaryUrlsUsing(fn ($p, $e) => "https://signed.test/mod/{$p}");
    Storage::disk('supabase_materials')->buildTemporaryUrlsUsing(fn ($p, $e) => "https://signed.test/mat/{$p}");

    $this->student = User::factory()->create([
        'role' => 'student', 'approval_status' => 'approved',
        'section_id' => Section::factory()->create()->id,
    ]);
});

it('lists only approved, topic-tagged teacher modules', function () {
    ModuleStatus::create(['file_name' => '1_a.pdf', 'status' => 'approved', 'module_title' => 'Shown', 'module_topic' => 'Module 1: Sequences and Series']);
    ModuleStatus::create(['file_name' => '2_b.pdf', 'status' => 'pending', 'module_title' => 'Hidden', 'module_topic' => 'Module 1: Sequences and Series']);
    ModuleStatus::create(['file_name' => '3_c.pdf', 'status' => 'approved', 'module_title' => 'NoTopic']);

    $response = $this->actingAs($this->student)->getJson(route('student.modules.list'));

    $response->assertOk();
    expect($response->json('modules'))->toHaveCount(1);
    expect($response->json('modules.0.title'))->toBe('Shown');
});

it('redirects to a signed URL for an approved module and 404s otherwise', function () {
    $approved = ModuleStatus::create(['file_name' => '1_ok.pdf', 'status' => 'approved', 'module_title' => 'OK', 'module_topic' => 'Module 1: Sequences and Series']);
    $pending = ModuleStatus::create(['file_name' => '2_no.pdf', 'status' => 'pending', 'module_title' => 'No']);

    $this->actingAs($this->student)->get(route('student.modules.download', $approved))
        ->assertRedirect('https://signed.test/mod/1_ok.pdf');

    $this->actingAs($this->student)->get(route('student.modules.download', $pending))
        ->assertNotFound();
});

it('serves a curriculum PDF by topic or name, but nothing off the whitelist', function () {
    $this->actingAs($this->student)->get(route('student.modules.file', ['topic' => 'ari']))
        ->assertRedirect('https://signed.test/mat/Arithmetic Sequence.pdf');

    $this->actingAs($this->student)->get(route('student.modules.file', ['name' => 'Geometric Sequence.pdf']))
        ->assertRedirect('https://signed.test/mat/Geometric Sequence.pdf');

    $this->actingAs($this->student)->get(route('student.modules.file', ['name' => '../secrets.pdf']))
        ->assertNotFound();
});

it('requires authentication', function () {
    $this->get(route('student.modules.list'))->assertRedirect(route('student.login'));
});
