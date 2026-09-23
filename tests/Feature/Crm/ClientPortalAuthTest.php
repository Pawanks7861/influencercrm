<?php

namespace Tests\Feature\Crm;

use App\Models\Client;
use App\Models\User;
use App\Services\Client\ClientLoginService;
use App\Support\PermissionRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ClientPortalAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Client, 1: User}
     */
    protected function createClientAccount(array $clientAttributes = [], string $password = 'password'): array
    {
        app(PermissionRegistrar::class)->register();

        $client = Client::factory()->create(array_merge([
            'email' => 'brand.portal@example.com',
            'name' => 'Brand Co',
            'company_name' => 'Brand Co',
        ], $clientAttributes));

        $user = app(ClientLoginService::class)->createLogin(
            $client,
            $password,
            $client->email
        );

        return [$client->fresh(), $user->fresh()];
    }

    public function test_admin_can_create_login_for_client(): void
    {
        $this->actingAsAdmin();

        $client = Client::factory()->create([
            'email' => 'create.client.login@example.com',
            'user_id' => null,
            'login_enabled' => false,
        ]);

        $response = $this->post(route('clients.login.store', $client), [
            'email' => 'create.client.login@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect();
        $client->refresh();

        $this->assertNotNull($client->user_id);
        $this->assertTrue($client->login_enabled);
        $this->assertTrue($client->user->hasRole('client'));
        $this->assertTrue(Hash::check('password', $client->user->password));
        $this->assertTrue($client->user->can('client_portal.access'));
    }

    public function test_client_can_login_and_redirects_to_portal_dashboard(): void
    {
        [, $user] = $this->createClientAccount();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('client.portal.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_client_cannot_access_staff_crm_routes(): void
    {
        [, $user] = $this->createClientAccount();
        $this->actingAs($user);

        $this->get('/influencers')->assertForbidden();
        $this->get('/clients')->assertForbidden();
        $this->get('/reports')->assertForbidden();
        $this->get('/settings')->assertForbidden();
        $this->get('/dashboard')->assertForbidden();
    }
}
