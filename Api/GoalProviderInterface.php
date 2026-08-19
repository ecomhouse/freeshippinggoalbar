<?php

declare(strict_types=1);

namespace EcomHouse\FreeShippingGoalBar\Api;

use Magento\Quote\Model\Quote;

/**
 * Supplies the free-shipping goal a cart is progressing towards.
 *
 * The quote is passed in so an implementation can vary the goal per store, customer group or
 * cart content. Implementations are registered in `di.xml` under the key the merchant picks in
 * `ecomhouse_freeshipping_goal_bar/general/threshold_source`.
 *
 * @api
 */
interface GoalProviderInterface
{
    /**
     * Threshold the cart has to reach, in the store's base currency.
     *
     * Returns null when this provider has no goal to offer - a disabled carrier, an unset amount.
     * Callers must treat null as "render nothing" rather than as a zero threshold.
     *
     * @param Quote $quote
     * @return float|null
     */
    public function getThreshold(Quote $quote): ?float;
}
