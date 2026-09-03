<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSeeText('アカウントをお持ちでない方は新規登録');
        $response->assertSee('href="'.route('register').'"', false);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'user_id' => $user->user_id,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('home', absolute: false));
    }

    public function test_users_return_to_the_intended_page_after_login(): void
    {
        $user = User::factory()->create();

        $this->get(route('favorites.index'))
            ->assertRedirect(route('login'));

        $response = $this->post('/login', [
            'user_id' => $user->user_id,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('favorites.index'));
    }

    public function test_dashboard_route_has_been_removed(): void
    {
        $this->assertNull(Route::getRoutes()->getByName('dashboard'));

        $this->get('/dashboard')->assertNotFound();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'user_id' => $user->user_id,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
