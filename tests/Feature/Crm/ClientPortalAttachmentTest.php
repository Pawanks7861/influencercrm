<?php

namespace Tests\Feature\Crm;

use App\Enums\RequirementType;
use App\Models\ClientRequirement;
use App\Models\RequirementAttachment;
use App\Services\Client\ClientLoginService;
use App\Support\PermissionRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientPortalAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function createClientAccount(string $email): array
    {
        app(PermissionRegistrar::class)->register();
        $client = \App\Models\Client::factory()->create(['email' => $email]);
        $user = app(ClientLoginService::class)->createLogin($client, 'password', $email);

        return [$client->fresh(), $user->fresh()];
    }

    public function test_client_a_cannot_download_client_b_attachment(): void
    {
        Storage::fake('local');

        [, $userA] = $this->createClientAccount('attach.a@example.com');
        [$clientB] = $this->createClientAccount('attach.b@example.com');

        $requirement = ClientRequirement::query()->create([
            'requirement_number' => 'REQ-2026-0100',
            'client_id' => $clientB->id,
            'requirement_type' => RequirementType::InfluencerMarketing,
            'title' => 'B brief',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $path = UploadedFile::fake()->create('brief.pdf', 100, 'application/pdf')
            ->store("requirement-attachments/{$requirement->id}", 'local');

        $attachment = RequirementAttachment::query()->create([
            'client_requirement_id' => $requirement->id,
            'original_filename' => 'brief.pdf',
            'stored_path' => $path,
            'mime_type' => 'application/pdf',
            'size' => 100,
        ]);

        $this->actingAs($userA);
        $this->get(route('client.requirements.attachments.download', $attachment))->assertNotFound();
    }

    public function test_client_show_does_not_contain_internal_notes(): void
    {
        [, $user] = $this->createClientAccount('notes@example.com');

        $requirement = ClientRequirement::query()->create([
            'requirement_number' => 'REQ-2026-0200',
            'client_id' => $user->client->id,
            'requirement_type' => RequirementType::InfluencerMarketing,
            'title' => 'Public brief',
            'internal_notes' => 'SECRET STAFF NOTES',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($user);

        $this->get(route('client.requirements.show', $requirement))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('ClientPortal/Requirements/Show')
                ->missing('requirement.internal_notes')
                ->where('requirement.title', 'Public brief')
            );
    }
}
