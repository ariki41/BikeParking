<?php

namespace Tests\Feature\Auth;

use App\Models\Prefecture;
use App\Models\RetiredUserId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_registration_screen_displays_prefectures_in_id_order(): void
    {
        collect(['沖縄県', '東京都', '海外', '北海道'])
            ->each(fn (string $name) => Prefecture::factory()->create(['name' => $name]));

        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSeeInOrder(['沖縄県', '東京都', '海外', '北海道']);
    }

    public function test_new_users_can_register(): void
    {
        $prefecture = Prefecture::factory()->create();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'user_id' => 'test-user',
            'prefecture' => $prefecture->id,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('home', absolute: false));
        $this->assertDatabaseHas('users', [
            'user_id' => 'test-user',
            'prefecture_id' => $prefecture->id,
        ]);
    }

    public function test_retired_user_id_cannot_be_registered_again(): void
    {
        $prefecture = Prefecture::factory()->create();
        RetiredUserId::query()->create([
            'user_id_hash' => RetiredUserId::hashFor('retired-user'),
        ]);

        $response = $this->from('/register')->post('/register', [
            'name' => 'Test User',
            'user_id' => 'retired-user',
            'prefecture' => $prefecture->id,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasErrors('user_id')
            ->assertRedirect('/register');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['user_id' => 'retired-user']);
    }
}
