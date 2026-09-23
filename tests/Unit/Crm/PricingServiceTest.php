<?php

namespace Tests\Unit\Crm;

use App\Models\CampaignInfluencer;
use App\Services\Campaign\PricingService;
use PHPUnit\Framework\TestCase;

class PricingServiceTest extends TestCase
{
    public function test_calculate_final_amount_sums_cost_additional_and_fee(): void
    {
        $service = new PricingService;

        $this->assertSame('17000.00', $service->calculateFinalAmount(12000, 2000, 3000));
        $this->assertSame('15000.00', $service->calculateFinalAmount(12000, 0, 3000));
        $this->assertSame('15000.50', $service->calculateFinalAmount(12000.25, 0, 3000.25));
    }

    public function test_recalculate_skips_when_final_amount_overridden(): void
    {
        $row = new CampaignInfluencer([
            'influencer_cost' => 12000,
            'additional_cost' => 2000,
            'grovera_fee' => 3000,
            'final_amount' => 14000,
            'final_amount_overridden' => true,
        ]);

        $row->recalculateFinalAmountIfNotOverridden();

        $this->assertEquals(14000, (float) $row->final_amount);

        $row->final_amount_overridden = false;
        $row->recalculateFinalAmountIfNotOverridden();

        $this->assertEquals(17000, (float) $row->final_amount);
    }
}
