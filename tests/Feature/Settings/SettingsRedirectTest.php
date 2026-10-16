<?php

use App\Models\User;

test('authenticated users are redirected to their profile settings', function (string $method) {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->call($method, '/settings');

    $response
        ->assertFound()
        ->assertHeader('Location', '/settings/profile');
})->with(['GET', 'HEAD']);

test('guests are redirected to login when opening settings', function () {
    $response = $this->get('/settings');

    $response->assertRedirect(route('login'));
});

test('settings navigation returns 405 for unsupported methods', function (string $method) {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->call($method, '/settings');

    $response->assertMethodNotAllowed();
})->with(['POST', 'PUT', 'PATCH', 'DELETE', 'QUERY']);
