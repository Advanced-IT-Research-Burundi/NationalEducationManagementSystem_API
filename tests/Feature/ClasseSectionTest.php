<?php

test('example', function () {
    $response = $this->getJson('/api/health');

    $response->assertStatus(200)
        ->assertJsonPath('status', 'healthy');
});
