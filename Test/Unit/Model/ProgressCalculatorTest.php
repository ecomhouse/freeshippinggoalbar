<?php

declare(strict_types=1);

namespace EcomHouse\FreeShippingGoalBar\Test\Unit\Model;

use EcomHouse\FreeShippingGoalBar\Model\CartValueResolver;
use EcomHouse\FreeShippingGoalBar\Model\Config;
use EcomHouse\FreeShippingGoalBar\Model\Goal\GoalResolver;
use EcomHouse\FreeShippingGoalBar\Model\ProgressCalculator;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;

/**
 * @see ProgressCalculator
 */
class ProgressCalculatorTest extends TestCase
{
    public function testComputesRemainingAmountAndPercentage(): void
    {
        $progress = $this->calculator(threshold: 200.0, value: 150.0)->calculate($this->quote());

        $this->assertNotNull($progress);
        $this->assertSame(200.0, $progress->threshold);
        $this->assertSame(50.0, $progress->remaining);
        $this->assertSame(75.0, $progress->percent);
        $this->assertFalse($progress->eligible);
    }

    public function testCartAtTheGoalIsEligible(): void
    {
        $progress = $this->calculator(threshold: 200.0, value: 200.0)->calculate($this->quote());

        $this->assertNotNull($progress);
        $this->assertTrue($progress->eligible);
        $this->assertSame(0.0, $progress->remaining);
        $this->assertSame(100.0, $progress->percent);
    }

    /**
     * A cart over the goal must not report a negative amount or a bar wider than the track -
     * the "add -159.95 more" defect the predecessor module shipped.
     */
    public function testClampsRemainingAndPercentageAboveTheGoal(): void
    {
        $progress = $this->calculator(threshold: 200.0, value: 350.0)->calculate($this->quote());

        $this->assertNotNull($progress);
        $this->assertSame(0.0, $progress->remaining);
        $this->assertSame(100.0, $progress->percent);
    }

    /**
     * Store credit or a gift card can push the compared value below zero; the bar shows no
     * progress rather than a negative fill.
     */
    public function testClampsPercentageBelowZero(): void
    {
        $progress = $this->calculator(threshold: 200.0, value: -50.0)->calculate($this->quote());

        $this->assertNotNull($progress);
        $this->assertSame(0.0, $progress->percent);
        $this->assertSame(250.0, $progress->remaining);
    }

    public function testReturnsNothingWhenDisabled(): void
    {
        $calculator = $this->calculator(threshold: 200.0, value: 150.0, enabled: false);

        $this->assertNull($calculator->calculate($this->quote()));
    }

    public function testReturnsNothingWhenNoGoalIsConfigured(): void
    {
        $calculator = $this->calculator(threshold: null, value: 150.0);

        $this->assertNull($calculator->calculate($this->quote()));
    }

    /**
     * A virtual cart is never shipped, so there is no shipping to earn.
     */
    public function testReturnsNothingForAVirtualCart(): void
    {
        $calculator = $this->calculator(threshold: 200.0, value: 150.0);

        $this->assertNull($calculator->calculate($this->quote(virtual: true)));
    }

    public function testReturnsNothingForAnEmptyCart(): void
    {
        $calculator = $this->calculator(threshold: 200.0, value: 0.0);

        $this->assertNull($calculator->calculate($this->quote(itemsCount: 0)));
    }

    private function calculator(?float $threshold, float $value, bool $enabled = true): ProgressCalculator
    {
        $config = $this->createMock(Config::class);
        $config->method('isEnabled')->willReturn($enabled);

        $goalResolver = $this->createMock(GoalResolver::class);
        $goalResolver->method('getThreshold')->willReturn($threshold);

        $cartValueResolver = $this->createMock(CartValueResolver::class);
        $cartValueResolver->method('getBaseValue')->willReturn($value);

        return new ProgressCalculator($config, $goalResolver, $cartValueResolver);
    }

    private function quote(bool $virtual = false, int $itemsCount = 2): Quote
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStoreId', 'isVirtual', 'getItemsCount'])
            ->getMock();
        $quote->method('getStoreId')->willReturn(1);
        $quote->method('isVirtual')->willReturn($virtual);
        $quote->method('getItemsCount')->willReturn($itemsCount);

        return $quote;
    }
}
