<?php

declare(strict_types=1);

namespace SilverShop\StoreProfile\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\SiteConfig\SiteConfig;

class StoreProfileTest extends SapphireTest
{
    protected $usesDatabase = true;

    public function testNameFallsBackToTitle(): void
    {
        $config = SiteConfig::create(['Title' => 'My Shop']);
        $config->write();
        $this->assertSame('My Shop', $config->StoreProfileName());

        $config->ShopName = 'Silvershop';
        $this->assertSame('Silvershop', $config->StoreProfileName());
    }

    public function testFormattedAddressUsesCountryRules(): void
    {
        $config = SiteConfig::create([
            'Title' => 'My Shop',
            'ShopName' => 'Silvershop',
            'StoreStreet' => 'Voorbeeldstraat 1',
            'StorePostcode' => '1011 AB',
            'StoreCity' => 'Amsterdam',
            'StoreCountryCode' => 'NL',
        ]);
        $config->write();

        $this->assertTrue($config->hasStoreAddress());
        $formatted = $config->StoreFormattedAddress();
        $this->assertStringContainsString('Voorbeeldstraat 1', $formatted);
        $this->assertStringContainsString('1011 AB Amsterdam', $formatted);
        $this->assertStringContainsString('Netherlands', $formatted);
    }

    public function testNoAddressWhenEmpty(): void
    {
        $config = SiteConfig::create(['Title' => 'My Shop']);
        $config->write();
        $this->assertFalse($config->hasStoreAddress());
    }
}
