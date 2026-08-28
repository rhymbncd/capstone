<?php

use App\Models\Quiz;
use App\Models\Section;
use App\Models\User;

beforeEach(function () {
    $this->teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);
    $this->payload = [
        'topic' => 'Sequences',
        'activity_label' => 'Arithmetic Sequence',
        'grade' => '10',
        'difficulty' => 'medium',
        'pretest' => json_encode([['question' => 'q', 'options' => ['A' => '1'], 'answer' => 'A']]),
        'posttest' => json_encode([]),
        'activity' => json_encode([]),
    ];
});

it('creates, lists, updates and deletes a draft', function () {
    $created = $this->actingAs($this->teacher)
        ->postJson(route('teacher.quiz.drafts.store'), $this->payload)
        ->assertStatus(201)
        ->json('draft');

    $this->actingAs($this->teacher)->getJson(route('teacher.quiz.drafts.index'))
        ->assertOk()
        ->assertJsonCount(1, 'drafts');

    $this->actingAs($this->teacher)->putJson(route('teacher.quiz.drafts.update', $created['id']), [
        ...$this->payload,
        'topic' => 'Renamed',
    ])->assertOk();
    expect(Quiz::find($created['id'])->topic)->toBe('Renamed');

    $this->actingAs($this->teacher)->deleteJson(route('teacher.quiz.drafts.destroy', $created['id']))
        ->assertOk();
    $this->assertDatabaseMissing('quizzes', ['id' => $created['id']]);
});

it('caps the draft list at 20 newest', function () {
    foreach (range(1, 25) as $i) {
        Quiz::create([...$this->payload, 'topic' => "Q{$i}"]);
    }

    $this->actingAs($this->teacher)->getJson(route('teacher.quiz.drafts.index'))
        ->assertOk()
        ->assertJsonCount(20, 'drafts');
});

it('rejects a non-JSON pretest', function () {
    $this->actingAs($this->teacher)
        ->postJson(route('teacher.quiz.drafts.store'), [...$this->payload, 'pretest' => 'nope'])
        ->assertUnprocessable();
});

it('blocks a student', function () {
    $student = User::factory()->create([
        'role' => 'student',
        'approval_status' => 'approved',
        'section_id' => Section::factory()->create()->id,
    ]);

    $this->actingAs($student)->get(route('teacher.quiz.drafts.index'))
        ->assertRedirect(route('homepage'));
});
