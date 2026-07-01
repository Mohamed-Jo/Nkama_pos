<?php

namespace Tests\Feature;

use App\Models\Operator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperatorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_kiosk_screen_can_be_rendered(): void
    {
        $this->get('/kiosk')->assertOk();
    }

    public function test_operator_can_authenticate_with_valid_pin(): void
    {
        $pin = '12345678';
        $operator = Operator::create([
            'name' => 'Operador Teste',
            'pin' => $pin,
            'pin_fingerprint' => Operator::pinFingerprint($pin),
            'role' => 'cashier',
            'active' => true,
        ]);

        $this->postJson('/pos/auth', ['pin' => $pin])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('operator.id', $operator->id);

        $this->assertSame($operator->id, session('operator_id'));
    }

    public function test_operator_cannot_authenticate_with_invalid_pin(): void
    {
        $this->postJson('/pos/auth', ['pin' => '00000000'])
            ->assertUnauthorized()
            ->assertJsonPath('success', false);

        $this->assertNull(session('operator_id'));
    }

    public function test_operator_can_logout(): void
    {
        $this->withSession([
            'operator_id' => 1,
            'operator_name' => 'Operador Teste',
            'operator_role' => 'cashier',
        ])->post('/pos/logout')
            ->assertRedirect('/kiosk');

        $this->assertNull(session('operator_id'));
    }
}
