<?php

declare(strict_types=1);

namespace EcomHouse\FreeShippingGoalBar\Test\Unit\Model;

use EcomHouse\FreeShippingGoalBar\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;

/**
 * @see Config
 */
class ConfigTest extends TestCase
{
    private const PATH_ENABLED = 'ecomhouse_freeshipping_goal_bar/general/enabled';
    private const PATH_SOURCE = 'ecomhouse_freeshipping_goal_bar/general/threshold_source';
    private const PATH_MANUAL = 'ecomhouse_freeshipping_goal_bar/general/manual_threshold';

    public function testReadsTheEnableFlag(): void
    {
        $this->assertTrue($this->config(flags: [self::PATH_ENABLED => true])->isEnabled());
        $this->assertFalse($this->config(flags: [self::PATH_ENABLED => false])->isEnabled());
    }

    public function testReadsTheConfiguredSource(): void
    {
        $config = $this->config(values: [self::PATH_SOURCE => Config::SOURCE_MANUAL]);

        $this->assertSame(Config::SOURCE_MANUAL, $config->getThresholdSource());
    }

    /**
     * An unset value must not disable the bar - the carrier is the safe default, because it can
     * only ever promise what checkout actually grants.
     */
    public function testAnUnsetSourceIsTheCarrier(): void
    {
        $this->assertSame(Config::SOURCE_CARRIER, $this->config(values: [self::PATH_SOURCE => ''])->getThresholdSource());
    }

    /**
     * A project source registered on GoalResolver is passed through; an unknown one is resolved
     * to the carrier by GoalResolver, not here.
     */
    public function testPassesAProjectSourceThrough(): void
    {
        $this->assertSame('project', $this->config(values: [self::PATH_SOURCE => 'project'])->getThresholdSource());
    }

    public function testReadsTheManualThresholdAsFloat(): void
    {
        $this->assertSame(150.0, $this->config(values: [self::PATH_MANUAL => '150.00'])->getManualThreshold());
        $this->assertSame(0.0, $this->config()->getManualThreshold());
    }

    /**
     * @param array<string, bool> $flags
     * @param array<string, string> $values
     * @return Config
     */
    private function config(array $flags = [], array $values = []): Config
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturnCallback(
            static fn (string $path): bool => $flags[$path] ?? false,
        );
        $scopeConfig->method('getValue')->willReturnCallback(
            static fn (string $path): ?string => $values[$path] ?? null,
        );

        return new Config($scopeConfig);
    }
}
