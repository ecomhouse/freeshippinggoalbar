<?php

declare(strict_types=1);

namespace EcomHouse\FreeShippingGoalBar\Test\Unit\Model\Goal;

use EcomHouse\FreeShippingGoalBar\Model\Config;
use EcomHouse\FreeShippingGoalBar\Model\Goal\ManualGoalProvider;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;

/**
 * @see ManualGoalProvider
 */
class ManualGoalProviderTest extends TestCase
{
    public function testReturnsTheConfiguredAmount(): void
    {
        $this->assertSame(150.0, $this->provider(150.0)->getThreshold($this->quote()));
    }

    public function testReturnsNullWhenTheAmountIsUnset(): void
    {
        $this->assertNull($this->provider(0.0)->getThreshold($this->quote()));
    }

    private function provider(float $threshold): ManualGoalProvider
    {
        $config = $this->createMock(Config::class);
        $config->method('getManualThreshold')->willReturn($threshold);

        return new ManualGoalProvider($config);
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
