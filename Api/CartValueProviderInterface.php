<?php

declare(strict_types=1);

namespace EcomHouse\FreeShippingGoalBar\Api;

use Magento\Quote\Model\Quote;

/**
 * Supplies the cart value a goal provider's threshold is compared against.
 *
 * A goal provider that does not mirror Magento's Free Shipping carrier (a project carrier with its
 * own basis, net or gross, with or without discounts) registers one of these in `di.xml` under the
 * same threshold-source key, as an item of CartValueResolver's `valueProviders` argument. Sources
 * without one use the Free Shipping carrier's basis.
 *
 * @api
 */
interface CartValueProviderInterface
{
    /**
     * Cart value in the store's base currency.
     *
     * @param Quote $quote
     * @return float
     */
    public function getBaseValue(Quote $quote): float;
}
