<?php

declare(strict_types=1);

namespace EcomHouse\FreeShippingGoalBar\Test\Unit\Model;

use EcomHouse\FreeShippingGoalBar\Model\CartValueResolver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\TestCase;

/**
 * Guards the mirror of Magento's Free Shipping carrier: every case here corresponds to a branch
 * of Carrier\Freeshipping::isFreeShippingRequired() plus the rate request Address builds for it.
 *
 * @see CartValueResolver
 */
class CartValueResolverTest extends TestCase
{
    private const PATH_PRICE_INCLUDES_TAX = 'tax/calculation/price_includes_tax';
    private const PATH_CARRIER_TAX_INCLUDING = 'carriers/freeshipping/tax_including';

    /**
     * Default store: catalog prices net, carrier compares net amounts. The discount is stored as
     * a negative number, so adding it subtracts the discount.
     */
    public function testUsesNetSubtotalPlusDiscountByDefault(): void
    {
        $resolver = $this->resolver(priceIncludesTax: false, carrierTaxIncluding: false);

        $value = $resolver->getBaseValue($this->quote(
            baseSubtotal: 200.0,
            baseSubtotalInclTax: 246.0,
            baseDiscount: -20.0,
            baseSubtotalWithDiscount: 180.0,
            baseTax: 41.4,
        ));

        $this->assertSame(180.0, $value);
    }

    /**
     * Catalog prices entered with tax: the rate request takes the tax-inclusive subtotal, so a
     * gross-priced store (the typical PL setup) must not be compared on a net amount.
     */
    public function testUsesGrossSubtotalWhenCatalogPricesIncludeTax(): void
    {
        $resolver = $this->resolver(priceIncludesTax: true, carrierTaxIncluding: false);

        $value = $resolver->getBaseValue($this->quote(
            baseSubtotal: 200.0,
            baseSubtotalInclTax: 246.0,
            baseDiscount: -20.0,
            baseSubtotalWithDiscount: 180.0,
            baseTax: 41.4,
        ));

        $this->assertSame(226.0, $value);
    }

    /**
     * The carrier's own "Tax Including" switch wins over both of the above.
     */
    public function testUsesSubtotalWithDiscountPlusTaxWhenTheCarrierIncludesTax(): void
    {
        $resolver = $this->resolver(priceIncludesTax: false, carrierTaxIncluding: true);

        $value = $resolver->getBaseValue($this->quote(
            baseSubtotal: 200.0,
            baseSubtotalInclTax: 246.0,
            baseDiscount: -20.0,
            baseSubtotalWithDiscount: 180.0,
            baseTax: 41.4,
        ));

        $this->assertSame(221.4, $value);
    }

    /**
     * The carrier only switches basis when the tax-inclusive amount is truthy - a totals set
     * without tax data must fall back rather than compare against zero.
     */
    public function testFallsBackWhenTheTaxInclusiveAmountIsZero(): void
    {
        $resolver = $this->resolver(priceIncludesTax: false, carrierTaxIncluding: true);

        $value = $resolver->getBaseValue($this->quote(
            baseSubtotal: 200.0,
            baseSubtotalInclTax: 200.0,
            baseDiscount: -20.0,
            baseSubtotalWithDiscount: 0.0,
            baseTax: 0.0,
        ));

        $this->assertSame(180.0, $value);
    }

    private function resolver(bool $priceIncludesTax, bool $carrierTaxIncluding): CartValueResolver
    {
        $flags = [
            self::PATH_PRICE_INCLUDES_TAX => $priceIncludesTax,
            self::PATH_CARRIER_TAX_INCLUDING => $carrierTaxIncluding,
        ];

        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturnCallback(
            static fn (string $path): bool => $flags[$path] ?? false,
        );

        return new CartValueResolver($scopeConfig);
    }

    private function quote(
        float $baseSubtotal,
        float $baseSubtotalInclTax,
        float $baseDiscount,
        float $baseSubtotalWithDiscount,
        float $baseTax,
    ): Quote {
        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBaseSubtotalWithDiscount'])
            ->addMethods(['getBaseSubtotal', 'getBaseSubtotalTotalInclTax', 'getBaseDiscountAmount', 'getBaseTaxAmount'])
            ->getMock();
        $address->method('getBaseSubtotal')->willReturn($baseSubtotal);
        $address->method('getBaseSubtotalTotalInclTax')->willReturn($baseSubtotalInclTax);
        $address->method('getBaseDiscountAmount')->willReturn($baseDiscount);
        $address->method('getBaseSubtotalWithDiscount')->willReturn($baseSubtotalWithDiscount);
        $address->method('getBaseTaxAmount')->willReturn($baseTax);

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStoreId', 'getShippingAddress'])
            ->getMock();
        $quote->method('getStoreId')->willReturn(1);
        $quote->method('getShippingAddress')->willReturn($address);

        return $quote;
    }
}
