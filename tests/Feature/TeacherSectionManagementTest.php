<?php

use App\Models\Section;
use App\Models\User;

it('lets a teacher create a section', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);

    $response = $this->actingAs($teacher)->postJson(route('teacher.section.store'), [
        'name' => 'Section Alpha',
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);
    $this->assertDatabaseHas('sections', ['name' => 'Section Alpha', 'teacher_id' => $teacher->id]);
});

it('rejects a duplicate section name', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    Section::factory()->create(['name' => 'Section Alpha', 'teacher_id' => $teacher->id]);

    $response = $this->actingAs($teacher)->postJson(route('teacher.section.store'), [
        'name' => 'Section Alpha',
    ]);

    $response->assertUnprocessable();
});

it('lets a teacher update their own section', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $section = Section::factory()->create(['teacher_id' => $teacher->id]);

    $response = $this->actingAs($teacher)->putJson(route('teacher.section.update', $section), [
        'name' => 'Renamed Section',
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('sections', ['id' => $section->id, 'name' => 'Renamed Section']);
});

it('prevents a teacher from updating another teacher\'s section', function () {
    $owner = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $intruder = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $section = Section::factory()->create(['teacher_id' => $owner->id, 'name' => 'Original']);

    $response = $this->actingAs($intruder)->putJson(route('teacher.section.update', $section), [
        'name' => 'Hijacked',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseHas('sections', ['id' => $section->id, 'name' => 'Original']);
});

it('lets a teacher delete their own section and unassigns its students', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $section = Section::factory()->create(['teacher_id' => $teacher->id]);
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => $section->id,
    ]);

    $response = $this->actingAs($teacher)->deleteJson(route('teacher.section.destroy', $section));

    $response->assertOk();
    $this->assertDatabaseMissing('sections', ['id' => $section->id]);
    $this->assertDatabaseHas('users', ['id' => $student->id, 'section_id' => null]);
});

it('prevents a teacher from deleting another teacher\'s section', function () {
    $owner = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $intruder = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $section = Section::factory()->create(['teacher_id' => $owner->id]);

    $response = $this->actingAs($intruder)->deleteJson(route('teacher.section.destroy', $section));

    $response->assertForbidden();
    $this->assertDatabaseHas('sections', ['id' => $section->id]);
});

it('only lists the authenticated teacher\'s own sections', function () {
    $teacherA = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $teacherB = User::factory()->teacher()->create(['approval_status' => 'approved']);
    Section::factory()->create(['teacher_id' => $teacherA->id, 'name' => 'Mine']);
    Section::factory()->create(['teacher_id' => $teacherB->id, 'name' => 'Not Mine']);

    $response = $this->actingAs($teacherA)->getJson(route('teacher.section.list'));

    $response->assertOk();
    $names = collect($response->json('sections'))->pluck('name');
    expect($names)->toContain('Mine');
    expect($names)->not->toContain('Not Mine');
});
