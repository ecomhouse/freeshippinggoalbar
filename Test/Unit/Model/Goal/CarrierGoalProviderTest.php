<?php

declare(strict_types=1);

namespace EcomHouse\FreeShippingGoalBar\Test\Unit\Model\Goal;

use EcomHouse\FreeShippingGoalBar\Model\Goal\CarrierGoalProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;

/**
 * @see CarrierGoalProvider
 */
class CarrierGoalProviderTest extends TestCase
{
    private const PATH_ACTIVE = 'carriers/freeshipping/active';
    private const PATH_SUBTOTAL = 'carriers/freeshipping/free_shipping_subtotal';

    public function testReturnsTheCarrierMinimumOrderAmount(): void
    {
        $provider = $this->provider(active: true, subtotal: '299.99');

        $this->assertSame(299.99, $provider->getThreshold($this->quote()));
    }

    /**
     * The bug the predecessor module shipped with: with the carrier off there is no goal at all,
     * and falling back to any other amount produced a bar counting towards nothing.
     */
    public function testReturnsNullWhenTheCarrierIsDisabled(): void
    {
        $provider = $this->provider(active: false, subtotal: '299.99');

        $this->assertNull($provider->getThreshold($this->quote()));
    }

    public function testReturnsNullWhenTheCarrierHasNoMinimum(): void
    {
        $provider = $this->provider(active: true, subtotal: '0');

        $this->assertNull($provider->getThreshold($this->quote()));
    }

    private function provider(bool $active, string $subtotal): CarrierGoalProvider
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturnCallback(
            static fn (string $path): bool => $path === self::PATH_ACTIVE && $active,
        );
        $scopeConfig->method('getValue')->willReturnCallback(
            static fn (string $path): ?string => $path === self::PATH_SUBTOTAL ? $subtotal : null,
        );

        return new CarrierGoalProvider($scopeConfig);
    }

    private function quote(): Quote
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStoreId'])
            ->getMock();
        $quote->method('getStoreId')->willReturn(1);

        return $quote;
    }
}
