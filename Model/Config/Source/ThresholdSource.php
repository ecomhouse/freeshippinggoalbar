<?php

declare(strict_types=1);

namespace EcomHouse\FreeShippingGoalBar\Model\Config\Source;

use EcomHouse\FreeShippingGoalBar\Model\Config;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Admin options for the threshold source field.
 *
 * A project that registers its own goal provider adds its option through the `options` argument in
 * `di.xml` (value = the provider's key, label = untranslated text), after the two built-in ones.
 */
class ThresholdSource implements OptionSourceInterface
{
    /**
     * @param array<string, string> $options provider key => label
     */
    public function __construct(
        private readonly array $options = [],
    ) {
    }

    /**
     * Options for the threshold source select.
     *
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        $options = [
            [
                'value' => Config::SOURCE_CARRIER,
                'label' => __('Free Shipping shipping method'),
            ],
            [
                'value' => Config::SOURCE_MANUAL,
                'label' => __('Manual amount'),
            ],
        ];
        foreach ($this->options as $value => $label) {
            $options[] = ['value' => (string) $value, 'label' => __($label)];
        }

        return $options;
    }
}
