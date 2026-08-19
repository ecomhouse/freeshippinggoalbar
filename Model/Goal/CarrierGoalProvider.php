<?php

declare(strict_types=1);

namespace EcomHouse\FreeShippingGoalBar\Model\Goal;

use EcomHouse\FreeShippingGoalBar\Api\GoalProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\ScopeInterface;

/**
 * Mirrors Magento's native Free Shipping shipping method.
 *
 * The goal is the carrier's own minimum order amount, so what the bar promises is exactly what
 * the carrier grants at checkout. When the carrier is switched off there is nothing to progress
 * towards and the provider says so with null - it must never fall back to another amount.
 */
class CarrierGoalProvider implements GoalProviderInterface
{
    private const XML_PATH_CARRIER_ACTIVE = 'carriers/freeshipping/active';
    private const XML_PATH_CARRIER_SUBTOTAL = 'carriers/freeshipping/free_shipping_subtotal';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
    ) {
    }

    /**
     * Minimum order amount configured on the Free Shipping carrier, in base currency.
     *
     * @param Quote $quote
     * @return float|null
     */
    public function getThreshold(Quote $quote): ?float
    {
        $storeId = (int) $quote->getStoreId();

        if (!$this->scopeConfig->isSetFlag(self::XML_PATH_CARRIER_ACTIVE, ScopeInterface::SCOPE_STORE, $storeId)) {
            return null;
        }

        $threshold = (float) $this->scopeConfig->getValue(
            self::XML_PATH_CARRIER_SUBTOTAL,
            ScopeInterface::SCOPE_STORE,
            $storeId,
        );

        return $threshold > 0.0 ? $threshold : null;
    }
}
