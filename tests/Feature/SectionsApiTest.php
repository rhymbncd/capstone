<?php

use App\Models\Section;
use App\Models\User;

it('exposes only section id and name, never the roster', function () {
    $section = Section::factory()->create(['name' => 'Grade 10 - Rizal']);
    User::factory()->count(3)->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    $response = $this->getJson(route('api.sections'));

    $response->assertOk();
    $payload = $response->json('sections');

    expect($payload)->toHaveCount(1);
    expect(array_keys($payload[0]))->toBe(['id', 'name']);
    expect($payload[0])->toMatchArray(['id' => $section->id, 'name' => 'Grade 10 - Rizal']);
});

it('is reachable without authentication', function () {
    Section::factory()->create();

    $this->getJson(route('api.sections'))->assertOk();
});
