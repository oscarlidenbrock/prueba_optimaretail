<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users are redirected from the dashboard to incidents', function (string $method) {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->{$method}(route('dashboard'));
    $response->assertRedirect(route('incidents.index'));
})->with(['get', 'head']);
