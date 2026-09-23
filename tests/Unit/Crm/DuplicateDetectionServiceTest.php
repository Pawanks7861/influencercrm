<?php

namespace Tests\Unit\Crm;

use App\Services\Campaign\PricingService;
use App\Services\Influencer\DuplicateDetectionService;
use PHPUnit\Framework\TestCase;

class DuplicateDetectionServiceTest extends TestCase
{
    public function test_normalize_instagram_from_handle_and_url(): void
    {
        $service = new DuplicateDetectionService;

        $this->assertSame('riyapatel', $service->normalizeInstagram('@riyapatel'));
        $this->assertSame('riyapatel', $service->normalizeInstagram('https://instagram.com/riyapatel'));
        $this->assertSame('riyapatel', $service->normalizeInstagram('https://www.instagram.com/riyapatel/?hl=en'));
        $this->assertSame('riyapatel', $service->normalizeInstagram('instagram.com/riyapatel/'));
        $this->assertNull($service->normalizeInstagram(''));
        $this->assertNull($service->normalizeInstagram(null));
    }

    public function test_normalize_email_and_mobile(): void
    {
        $service = new DuplicateDetectionService;

        $this->assertSame('riya@example.com', $service->normalizeEmail('  Riya@Example.com '));
        $this->assertSame('9876543210', $service->normalizeMobile('+91 98765-43210'));
        $this->assertSame('9876543210', $service->normalizeMobile('09876543210'));
        $this->assertSame('9876543210', $service->normalizeMobile('(987) 654-3210'));
        $this->assertNull($service->normalizeEmail(''));
        $this->assertNull($service->normalizeMobile('abc'));
    }
}
