<?php

declare(strict_types=1);

namespace EcomHouse\FreeShippingGoalBar\Model;

use EcomHouse\FreeShippingGoalBar\Model\Goal\GoalResolver;
use Magento\Quote\Model\Quote;

/**
 * Turns a quote into the numbers the bar renders - the only place progress is computed.
 *
 * Returns null whenever the bar has nothing meaningful to show, so both storefront surfaces
 * share one answer to "should this render at all" instead of each guessing.
 */
class ProgressCalculator
{
    public function __construct(
        private readonly Config $config,
        private readonly GoalResolver $goalResolver,
        private readonly CartValueResolver $cartValueResolver,
    ) {
    }

    /**
     * Progress for the given quote, or null when the bar must stay hidden: switched off, an empty
     * cart, a cart that is never shipped, or no configured goal.
     *
     * @param Quote $quote
     * @return Progress|null
     */
    public function calculate(Quote $quote): ?Progress
    {
        if (!$this->config->isEnabled((int) $quote->getStoreId())) {
            return null;
        }

        if ($quote->isVirtual() || (int) $quote->getItemsCount() < 1) {
            return null;
        }

        $threshold = $this->goalResolver->getThreshold($quote);
        if ($threshold === null || $threshold <= 0.0) {
            return null;
        }

        $value = $this->cartValueResolver->getBaseValue($quote);

        return new Progress(
            $threshold,
            max(0.0, $threshold - $value),
            max(0.0, min(100.0, $value / $threshold * 100)),
            $value >= $threshold,
        );
    }
}
