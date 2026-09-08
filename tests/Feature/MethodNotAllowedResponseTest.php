<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MethodNotAllowedResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_requests_with_an_unsupported_method_receive_a_405_response(): void
    {
        config(['app.debug' => false]);

        $this->get('/logout')
            ->assertMethodNotAllowed()
            ->assertHeader('Allow', 'POST')
            ->assertSee('Method Not Allowed');
    }

    public function test_json_requests_with_an_unsupported_method_receive_a_generic_405_response(): void
    {
        config(['app.debug' => false]);

        $this->getJson('/logout')
            ->assertMethodNotAllowed()
            ->assertJson(['message' => 'Method Not Allowed'])
            ->assertJsonMissing(['message' => 'The GET method is not supported for route logout. Supported methods: POST.']);
    }

    public function test_logout_form_post_still_logs_out_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }
}
