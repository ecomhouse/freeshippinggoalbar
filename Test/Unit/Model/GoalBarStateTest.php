<?php

declare(strict_types=1);

namespace EcomHouse\FreeShippingGoalBar\Test\Unit\Model;

use EcomHouse\FreeShippingGoalBar\Model\GoalBarState;
use EcomHouse\FreeShippingGoalBar\Model\Progress;
use EcomHouse\FreeShippingGoalBar\Model\ProgressCalculator;
use Magento\Framework\Escaper;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;

/**
 * @see GoalBarState
 */
class GoalBarStateTest extends TestCase
{
    public function testHiddenStateCarriesNothingElse(): void
    {
        $state = $this->state(null)->get($this->quote());

        $this->assertSame(['available' => false], $state);
    }

    public function testFormatsEveryAmountServerSide(): void
    {
        $progress = new Progress(threshold: 200.0, remaining: 50.0, percent: 75.0, eligible: false);

        $state = $this->state($progress)->get($this->quote());

        $this->assertTrue($state['available']);
        $this->assertFalse($state['eligible']);
        $this->assertSame(75.0, $state['percent']);
        $this->assertSame('formatted(0)', $state['min']);
        $this->assertSame('formatted(200)', $state['max']);
        $this->assertStringContainsString('formatted(50)', $state['message']);
        $this->assertStringContainsString('freeshipping-goal-bar-amount', $state['message']);
    }

    public function testReachedGoalStillShipsTheSuccessMessage(): void
    {
        $progress = new Progress(threshold: 200.0, remaining: 0.0, percent: 100.0, eligible: true);

        $state = $this->state($progress)->get($this->quote());

        $this->assertTrue($state['eligible']);
        $this->assertNotSame('', $state['successMessage']);
    }

    private function state(?Progress $progress): GoalBarState
    {
        $calculator = $this->createMock(ProgressCalculator::class);
        $calculator->method('calculate')->willReturn($progress);

        $priceCurrency = $this->createMock(PriceCurrencyInterface::class);
        $priceCurrency->method('convertAndFormat')->willReturnCallback(
            static fn (float $amount): string => 'formatted(' . (int) $amount . ')',
        );

        $escaper = $this->createMock(Escaper::class);
        $escaper->method('escapeHtml')->willReturnArgument(0);

        return new GoalBarState($calculator, $priceCurrency, $escaper);
    }

    private function quote(): Quote
    {
        return $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
    }
}
