<?php

use App\Models\QuizCustomTopic;
use App\Models\Section;
use App\Models\User;

beforeEach(function () {
    $this->teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
});

it('creates, lists and deletes a custom topic', function () {
    $id = $this->actingAs($this->teacher)->postJson(route('teacher.quiz.custom-topics.store'), [
        'module_key' => 'sequences',
        'topic_key' => 'custom_foo_1234',
        'topic_name' => 'My Custom Topic',
    ])->assertStatus(201)->json('customTopic.id');

    $this->actingAs($this->teacher)->getJson(route('teacher.quiz.custom-topics.index'))
        ->assertOk()
        ->assertJsonCount(1, 'customTopics');

    $this->actingAs($this->teacher)->deleteJson(route('teacher.quiz.custom-topics.destroy', $id))
        ->assertOk();

    $this->assertDatabaseMissing('quiz_custom_topics', ['id' => $id]);
});

it('rejects a topic name shorter than 3 characters', function () {
    $this->actingAs($this->teacher)->postJson(route('teacher.quiz.custom-topics.store'), [
        'module_key' => 'sequences',
        'topic_key' => 'custom_x_1234',
        'topic_name' => 'ab',
    ])->assertUnprocessable();
});

it('blocks a student', function () {
    QuizCustomTopic::create(['module_key' => 'm', 'topic_key' => 'k', 'topic_name' => 'Name']);
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => Section::factory()->create()->id,
    ]);

    $this->actingAs($student)->get(route('teacher.quiz.custom-topics.index'))
        ->assertRedirect(route('homepage'));
});
