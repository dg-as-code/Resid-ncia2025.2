<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentsApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_get_agents_status()
    {
        $response = $this->getJson('/api/agents/status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'agents' => [
                    'julia',
                    'pedro',
                    'key',
                    'publish_notify',
                    'cleanup',
                ],
            ]);
    }

    /** @test */
    public function it_can_execute_julia_agent()
    {
        $response = $this->postJson('/api/agents/julia', [
            'symbol' => 'PETR4',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);
    }

    /** @test */
    public function it_can_execute_pedro_agent()
    {
        $response = $this->postJson('/api/agents/pedro');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }

    /** @test */
    public function it_can_execute_key_agent()
    {
        $response = $this->postJson('/api/agents/key');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }

    /** @test */
    public function it_respects_rate_limiting()
    {
        // Fazer múltiplas requisições rapidamente
        for ($i = 0; $i < 12; $i++) {
            $response = $this->postJson('/api/agents/julia');
        }

        // A 11ª requisição deve ser bloqueada
        $response->assertStatus(429);
    }
}

