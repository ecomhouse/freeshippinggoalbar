<?php

declare(strict_types=1);

namespace EcomHouse\FreeShippingGoalBar\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Store\Model\ScopeInterface;

/**
 * The cart amount compared against the goal, in base currency.
 *
 * Deliberately a mirror of what Magento's Free Shipping carrier sees. `Quote\Address::
 * requestShippingRates()` builds the rate request, and `OfflineShipping\Model\Carrier\
 * Freeshipping::isFreeShippingRequired()` compares one of two values from it against the
 * configured subtotal:
 *
 * - `package_value_with_discount` = base subtotal + base discount, where the subtotal is taken
 *   incl. tax when catalog prices include tax;
 * - `base_subtotal_with_discount_incl_tax` = base subtotal with discount + base tax, used when
 *   the carrier's own "Tax Including" setting is on.
 *
 * Reproducing this exactly is the whole point: any other basis (grand total, displayed subtotal)
 * makes the bar promise free shipping the carrier will not grant, or hide one it would.
 */
class CartValueResolver
{
    private const XML_PATH_PRICE_INCLUDES_TAX = 'tax/calculation/price_includes_tax';
    private const XML_PATH_CARRIER_TAX_INCLUDING = 'carriers/freeshipping/tax_including';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
    ) {
    }

    /**
     * Base-currency cart value to compare against the goal.
     *
     * @param Quote $quote
     * @return float
     */
    public function getBaseValue(Quote $quote): float
    {
        $address = $quote->getShippingAddress();
        $storeId = (int) $quote->getStoreId();

        $value = $this->getPackageValueWithDiscount($address, $storeId);

        if ($this->isCarrierTaxIncluding($storeId)) {
            $inclTax = (float) $address->getBaseSubtotalWithDiscount() + (float) $address->getBaseTaxAmount();
            if ($inclTax !== 0.0) {
                $value = $inclTax;
            }
        }

        return $value;
    }

    /**
     * Base subtotal plus discount - the carrier's default comparison basis. The discount amount
     * is stored as a negative number, so adding it subtracts the discount.
     *
     * @param Address $address
     * @param int $storeId
     * @return float
     */
    private function getPackageValueWithDiscount(Address $address, int $storeId): float
    {
        $subtotal = $this->includesTax($storeId)
            ? (float) $address->getBaseSubtotalTotalInclTax()
            : (float) $address->getBaseSubtotal();

        return $subtotal + (float) $address->getBaseDiscountAmount();
    }

    /**
     * Whether catalog prices are entered including tax.
     *
     * @param int $storeId
     * @return bool
     */
    private function includesTax(int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PRICE_INCLUDES_TAX,
            ScopeInterface::SCOPE_STORE,
            $storeId,
        );
    }

    /**
     * Whether the Free Shipping carrier compares tax-inclusive amounts.
     *
     * @param int $storeId
     * @return bool
     */
    private function isCarrierTaxIncluding(int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CARRIER_TAX_INCLUDING,
            ScopeInterface::SCOPE_STORE,
            $storeId,
        );
    }
}
