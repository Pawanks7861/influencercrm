<?php

namespace Tests\Feature\Crm;

use App\Enums\InfluencerType;
use App\Models\Influencer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfluencerTest extends TestCase
{
    use RefreshDatabase;

    public function test_influencer_can_be_created(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('influencers.store'), [
            'name' => 'Riya Patel',
            'instagram_username' => '@riyapatel',
            'instagram_url' => 'https://instagram.com/riyapatel',
            'youtube_url' => 'https://youtube.com/@riyapatel',
            'mobile' => '9876543210',
            'email' => 'riya@example.com',
            'location' => 'Ahmedabad',
            'influencer_type' => InfluencerType::Premium->value,
            'default_price' => 15000,
            'status' => 'active',
        ]);

        $influencer = Influencer::query()->where('email', 'riya@example.com')->first();

        $this->assertNotNull($influencer);
        $this->assertSame('riyapatel', $influencer->instagram_username);
        $this->assertSame('9876543210', $influencer->mobile);
        $this->assertSame('https://youtube.com/@riyapatel', $influencer->youtube_url);
        $response->assertRedirect(route('influencers.show', $influencer));
    }

    public function test_creating_without_required_fields_fails(): void
    {
        $this->actingAsAdmin();

        $response = $this->from(route('influencers.create'))->post(route('influencers.store'), [
            'name' => '',
            'email' => 'optional@example.com',
        ]);

        $response->assertSessionHasErrors([
            'name',
            'instagram_username',
            'mobile',
            'location',
            'influencer_type',
            'default_price',
        ]);
        $this->assertSame(0, Influencer::query()->count());
    }

    public function test_influencer_can_be_edited(): void
    {
        $this->actingAsAdmin();

        $influencer = Influencer::factory()->create([
            'name' => 'Riya Patel',
            'location' => 'Ahmedabad',
            'default_price' => 15000,
        ]);

        $response = $this->put(route('influencers.update', $influencer), [
            'name' => 'Riya P.',
            'instagram_username' => $influencer->instagram_username,
            'email' => $influencer->email,
            'mobile' => $influencer->mobile,
            'location' => 'Mumbai',
            'influencer_type' => InfluencerType::Premium->value,
            'default_price' => 18000,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('influencers.show', $influencer));

        $this->assertDatabaseHas('influencers', [
            'id' => $influencer->id,
            'name' => 'Riya P.',
            'location' => 'Mumbai',
            'default_price' => 18000,
        ]);
    }

    public function test_duplicate_instagram_cannot_be_created(): void
    {
        $this->actingAsAdmin();

        Influencer::factory()->create([
            'instagram_username' => 'riyapatel',
            'instagram_url' => 'https://instagram.com/riyapatel',
            'email' => 'existing@example.com',
            'mobile' => '9111111111',
        ]);

        $response = $this->from(route('influencers.create'))->post(route('influencers.store'), [
            'name' => 'Another Riya',
            'instagram_url' => 'https://www.instagram.com/riyapatel/',
            'instagram_username' => '@riyapatel',
            'email' => 'other@example.com',
            'mobile' => '9222222222',
            'location' => 'Surat',
            'influencer_type' => InfluencerType::Medium->value,
            'default_price' => 10000,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('duplicates');
        $this->assertSame(1, Influencer::query()->where('instagram_username', 'riyapatel')->count());
    }

    public function test_duplicate_email_detection_works(): void
    {
        $this->actingAsAdmin();

        Influencer::factory()->create([
            'email' => 'riya@example.com',
            'instagram_username' => 'unique_one',
            'mobile' => '9333333333',
        ]);

        $response = $this->getJson(route('influencers.check-duplicate', [
            'email' => 'Riya@Example.com',
        ]));

        $response->assertOk()
            ->assertJson([
                'has_duplicates' => true,
            ]);

        $this->assertNotEmpty($response->json('duplicates'));
    }

    public function test_duplicate_phone_detection_works(): void
    {
        $this->actingAsAdmin();

        Influencer::factory()->create([
            'mobile' => '9876543210',
            'email' => 'phone-owner@example.com',
            'instagram_username' => 'unique_two',
        ]);

        $response = $this->getJson(route('influencers.check-duplicate', [
            'mobile' => '+91 98765-43210',
        ]));

        $response->assertOk()
            ->assertJson([
                'has_duplicates' => true,
            ]);
    }
}
