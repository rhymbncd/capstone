<?php

use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::post('/__test/token-mismatch', function (): never {
        throw new TokenMismatchException;
    })->middleware('web');
});

it('sends the user back with a friendly message instead of the raw 419 page', function () {
    $response = $this->from('/previous-page')->post('/__test/token-mismatch', ['answer' => '42']);

    $response->assertRedirect('/previous-page');
    $response->assertSessionHasErrors('error');
    $response->assertSessionHasInput('answer', '42');
});

it('returns a friendly json message instead of the raw 419 page for ajax requests', function () {
    $response = $this->postJson('/__test/token-mismatch');

    $response->assertStatus(419);
    $response->assertJson(['message' => 'Your session expired. Please try again.']);
});
