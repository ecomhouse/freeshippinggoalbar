<?php

declare(strict_types=1);

namespace EcomHouse\FreeShippingGoalBar\Model\Goal;

use EcomHouse\FreeShippingGoalBar\Api\GoalProviderInterface;
use EcomHouse\FreeShippingGoalBar\Model\Config;
use Magento\Quote\Model\Quote;

/**
 * Uses the amount typed into the module's own configuration.
 *
 * For stores where free shipping comes from a cart price rule rather than the native carrier.
 * The module only advertises the goal - granting free shipping stays the merchant's job.
 */
class ManualGoalProvider implements GoalProviderInterface
{
    public function __construct(
        private readonly Config $config,
    ) {
    }

    /**
     * Configured amount, in base currency, or null when it is unset.
     *
     * @param Quote $quote
     * @return float|null
     */
    public function getThreshold(Quote $quote): ?float
    {
        $threshold = $this->config->getManualThreshold((int) $quote->getStoreId());

        return $threshold > 0.0 ? $threshold : null;
    }
}
