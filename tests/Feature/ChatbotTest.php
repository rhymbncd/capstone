<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

it('lets an approved student ask the chatbot a question', function () {
    config(['services.openrouter.key' => 'test-key']);
    $student = User::factory()->create(['role' => 'student', 'approval_status' => 'approved']);

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'The answer is 4.']],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($student)->postJson(route('chatbot.ask'), [
        'message' => 'What is 2 + 2?',
    ]);

    $response->assertOk();
    $response->assertJson(['status' => 'success', 'reply' => 'The answer is 4.']);
});

it('blocks guests from the chatbot endpoint', function () {
    $response = $this->postJson(route('chatbot.ask'), ['message' => 'Hello']);

    $response->assertUnauthorized();
});

it('blocks teachers from the chatbot endpoint', function () {
    $teacher = User::factory()->teacher()->create(['approval_status' => 'approved']);

    $response = $this->actingAs($teacher)->postJson(route('chatbot.ask'), ['message' => 'Hello']);

    $response->assertRedirect(route('homepage'));
});

it('blocks admins from the chatbot endpoint', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->postJson(route('chatbot.ask'), ['message' => 'Hello']);

    $response->assertRedirect(route('homepage'));
});

it('blocks a pending (unapproved) student from the chatbot endpoint', function () {
    $student = User::factory()->create(['role' => 'student', 'approval_status' => 'pending']);

    $response = $this->actingAs($student)->postJson(route('chatbot.ask'), ['message' => 'Hello']);

    $response->assertRedirect(route('student.login'));
});

it('requires a message', function () {
    $student = User::factory()->create(['role' => 'student', 'approval_status' => 'approved']);

    $response = $this->actingAs($student)->postJson(route('chatbot.ask'), ['message' => '']);

    $response->assertStatus(422);
});

it('rejects a message over 1000 characters', function () {
    $student = User::factory()->create(['role' => 'student', 'approval_status' => 'approved']);

    $response = $this->actingAs($student)->postJson(route('chatbot.ask'), [
        'message' => str_repeat('a', 1001),
    ]);

    $response->assertStatus(422);
});

it('handles the AI provider failing gracefully', function () {
    config(['services.openrouter.key' => 'test-key']);
    $student = User::factory()->create(['role' => 'student', 'approval_status' => 'approved']);

    Http::fake([
        'openrouter.ai/*' => Http::response(['error' => 'server error'], 500),
    ]);

    $response = $this->actingAs($student)->postJson(route('chatbot.ask'), [
        'message' => 'What is 2 + 2?',
    ]);

    $response->assertStatus(500);
    $response->assertJson(['status' => 'error']);
});

it('throttles a student sending too many chatbot requests too quickly', function () {
    config(['services.openrouter.key' => 'test-key']);
    $student = User::factory()->create(['role' => 'student', 'approval_status' => 'approved']);

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => 'ok']]],
        ], 200),
    ]);

    for ($i = 0; $i < 20; $i++) {
        $this->actingAs($student)->postJson(route('chatbot.ask'), ['message' => "Question {$i}"])
            ->assertOk();
    }

    $this->actingAs($student)->postJson(route('chatbot.ask'), ['message' => 'One too many'])
        ->assertStatus(429);
});
