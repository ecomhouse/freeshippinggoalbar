<?php

declare(strict_types=1);

namespace EcomHouse\FreeShippingGoalBar\Test\Unit\Model\Config\Source;

use EcomHouse\FreeShippingGoalBar\Model\Config;
use EcomHouse\FreeShippingGoalBar\Model\Config\Source\ThresholdSource;
use PHPUnit\Framework\TestCase;

/**
 * @see ThresholdSource
 */
class ThresholdSourceTest extends TestCase
{
    public function testOffersTheBuiltInSources(): void
    {
        $values = array_column((new ThresholdSource())->toOptionArray(), 'value');

        $this->assertSame([Config::SOURCE_CARRIER, Config::SOURCE_MANUAL], $values);
    }

    public function testAppendsProjectSourcesFromDi(): void
    {
        $options = (new ThresholdSource(['project' => 'Project threshold']))->toOptionArray();

        $this->assertSame([Config::SOURCE_CARRIER, Config::SOURCE_MANUAL, 'project'], array_column($options, 'value'));
        $this->assertSame('Project threshold', (string) $options[2]['label']);
    }
}
