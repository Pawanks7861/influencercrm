<?php

namespace Tests\Feature\Crm;

use App\Enums\ClientStatus;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_be_created(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('clients.store'), [
            'company_name' => 'ABC Foods',
            'contact_person' => 'Priya Shah',
            'mobile' => '9876501234',
            'email' => 'priya@abcfoods.com',
            'status' => ClientStatus::Lead->value,
        ]);

        $client = Client::query()->where('company_name', 'ABC Foods')->first();

        $this->assertNotNull($client);
        $this->assertSame('Priya Shah', $client->contact_person);
        $this->assertSame('9876501234', $client->mobile);
        $response->assertRedirect(route('clients.show', $client));
    }

    public function test_client_can_be_edited(): void
    {
        $this->actingAsAdmin();

        $client = Client::factory()->create([
            'company_name' => 'ABC Foods',
            'contact_person' => 'Priya Shah',
            'mobile' => '9876501234',
        ]);

        $response = $this->put(route('clients.update', $client), [
            'company_name' => 'ABC Foods Pvt Ltd',
            'contact_person' => 'Priya S.',
            'mobile' => '9876501234',
            'email' => $client->email,
            'status' => ClientStatus::Active->value,
        ]);

        $response->assertRedirect(route('clients.show', $client));
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'company_name' => 'ABC Foods Pvt Ltd',
            'contact_person' => 'Priya S.',
        ]);
    }

    public function test_client_profile_loads(): void
    {
        $this->actingAsAdmin();

        $client = Client::factory()->create(['company_name' => 'Satvam']);

        $response = $this->get(route('clients.show', $client));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Clients/Show')
            ->where('client.id', $client->id)
            ->where('client.company_name', 'Satvam'));
    }

    public function test_duplicate_client_detection_works(): void
    {
        $this->actingAsAdmin();

        Client::factory()->create([
            'company_name' => 'ABC Foods',
            'mobile' => '9876501234',
            'email' => 'hello@abcfoods.com',
        ]);

        $response = $this->getJson(route('clients.check-duplicate', [
            'mobile' => '9876501234',
            'email' => 'hello@abcfoods.com',
        ]));

        $response->assertOk()->assertJson(['has_duplicates' => true]);
        $this->assertNotEmpty($response->json('duplicates'));
    }
}
