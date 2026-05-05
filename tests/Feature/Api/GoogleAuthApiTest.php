<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class GoogleAuthApiTest extends TestCase
{
    public function test_google_auth_requires_id_token(): void
    {
        $this->postJson('/api/v1/auth/google', [])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation errors',
            ])
            ->assertJsonStructure(['errors']);
    }
}
