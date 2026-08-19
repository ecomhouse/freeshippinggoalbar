<?php

declare(strict_types=1);

namespace EcomHouse\FreeShippingGoalBar\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Reader for the module's own configuration section.
 *
 * Only `ecomhouse_freeshipping_goal_bar/*` paths live here. Carrier and tax settings are read by
 * the classes that mirror them (CarrierGoalProvider, CartValueResolver), so this class stays the
 * single source of truth for what the merchant configured about the bar itself.
 */
class Config
{
    public const SOURCE_CARRIER = 'carrier';
    public const SOURCE_MANUAL = 'manual';

    private const XML_PATH_ENABLED = 'ecomhouse_freeshipping_goal_bar/general/enabled';
    private const XML_PATH_THRESHOLD_SOURCE = 'ecomhouse_freeshipping_goal_bar/general/threshold_source';
    private const XML_PATH_MANUAL_THRESHOLD = 'ecomhouse_freeshipping_goal_bar/general/manual_threshold';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
    ) {
    }

    /**
     * Whether the merchant switched the bar on for the given store.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Which goal provider supplies the threshold: SOURCE_CARRIER or SOURCE_MANUAL.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getThresholdSource(?int $storeId = null): string
    {
        $source = (string) $this->scopeConfig->getValue(
            self::XML_PATH_THRESHOLD_SOURCE,
            ScopeInterface::SCOPE_STORE,
            $storeId,
        );

        return $source === self::SOURCE_MANUAL ? self::SOURCE_MANUAL : self::SOURCE_CARRIER;
    }

    /**
     * Threshold typed in by the merchant, in base currency. Only used in manual mode.
     *
     * @param int|null $storeId
     * @return float
     */
    public function getManualThreshold(?int $storeId = null): float
    {
        return (float) $this->scopeConfig->getValue(
            self::XML_PATH_MANUAL_THRESHOLD,
            ScopeInterface::SCOPE_STORE,
            $storeId,
        );
    }
}
