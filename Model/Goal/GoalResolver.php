<?php

declare(strict_types=1);

namespace EcomHouse\FreeShippingGoalBar\Model\Goal;

use EcomHouse\FreeShippingGoalBar\Api\GoalProviderInterface;
use EcomHouse\FreeShippingGoalBar\Model\Config;
use Magento\Quote\Model\Quote;

/**
 * Picks the goal provider the merchant configured.
 *
 * Providers are injected from `di.xml` keyed by the value of the threshold-source setting, so a
 * project can add its own source (per customer group, per website) without touching this class.
 */
class GoalResolver
{
    /**
     * @param Config $config
     * @param GoalProviderInterface[] $providers
     */
    public function __construct(
        private readonly Config $config,
        private readonly array $providers = [],
    ) {
    }

    /**
     * Threshold from the configured provider, in base currency, or null when there is no goal.
     *
     * @param Quote $quote
     * @return float|null
     */
    public function getThreshold(Quote $quote): ?float
    {
        $source = $this->config->getThresholdSource((int) $quote->getStoreId());
        $provider = $this->providers[$source] ?? null;

        return $provider?->getThreshold($quote);
    }
}
