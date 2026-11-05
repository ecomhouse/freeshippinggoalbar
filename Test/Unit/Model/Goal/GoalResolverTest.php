<?php

declare(strict_types=1);

namespace EcomHouse\FreeShippingGoalBar\Test\Unit\Model\Goal;

use EcomHouse\FreeShippingGoalBar\Api\GoalProviderInterface;
use EcomHouse\FreeShippingGoalBar\Model\Config;
use EcomHouse\FreeShippingGoalBar\Model\Goal\GoalResolver;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;

/**
 * @see GoalResolver
 */
class GoalResolverTest extends TestCase
{
    public function testAsksTheProviderMatchingTheConfiguredSource(): void
    {
        $resolver = $this->resolver(Config::SOURCE_MANUAL, [
            Config::SOURCE_CARRIER => $this->provider(300.0),
            Config::SOURCE_MANUAL => $this->provider(150.0),
        ]);

        $this->assertSame(150.0, $resolver->getThreshold($this->quote()));
    }

    public function testAsksAProjectProviderRegisteredUnderItsOwnKey(): void
    {
        $resolver = $this->resolver('project', [
            Config::SOURCE_CARRIER => $this->provider(300.0),
            'project' => $this->provider(42.0),
        ]);

        $this->assertSame(42.0, $resolver->getThreshold($this->quote()));
    }

    /**
     * A source whose provider is gone (a removed project module) falls back to the carrier.
     */
    public function testFallsBackToTheCarrierForAnUnregisteredSource(): void
    {
        $resolver = $this->resolver('removed', [Config::SOURCE_CARRIER => $this->provider(300.0)]);

        $this->assertSame(300.0, $resolver->getThreshold($this->quote()));
    }

    public function testReturnsNullWhenNoProviderIsRegisteredForTheSource(): void
    {
        $resolver = $this->resolver(Config::SOURCE_MANUAL, []);

        $this->assertNull($resolver->getThreshold($this->quote()));
    }

    /**
     * @param string $source
     * @param array<string, GoalProviderInterface> $providers
     * @return GoalResolver
     */
    private function resolver(string $source, array $providers): GoalResolver
    {
        $config = $this->createMock(Config::class);
        $config->method('getThresholdSource')->willReturn($source);

        return new GoalResolver($config, $providers);
    }

    private function provider(?float $threshold): GoalProviderInterface
    {
        $provider = $this->createMock(GoalProviderInterface::class);
        $provider->method('getThreshold')->willReturn($threshold);

        return $provider;
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
