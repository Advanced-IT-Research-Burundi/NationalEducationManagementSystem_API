<?php

test('the application returns a successful response', function () {
    $response = $this->getJson('/api');

    $response->assertStatus(200)
        ->assertJsonPath('status', 'operational');
});
