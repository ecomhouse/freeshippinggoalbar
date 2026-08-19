<?php

declare(strict_types=1);

namespace EcomHouse\FreeShippingGoalBar\Model\Config\Source;

use EcomHouse\FreeShippingGoalBar\Model\Config;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Admin options for the threshold source field.
 */
class ThresholdSource implements OptionSourceInterface
{
    /**
     * Options for the threshold source select.
     *
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => Config::SOURCE_CARRIER,
                'label' => __('Free Shipping shipping method'),
            ],
            [
                'value' => Config::SOURCE_MANUAL,
                'label' => __('Manual amount'),
            ],
        ];
    }
}
