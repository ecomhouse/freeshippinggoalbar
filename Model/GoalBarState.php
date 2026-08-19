<?php

declare(strict_types=1);

namespace EcomHouse\FreeShippingGoalBar\Model;

use Magento\Framework\Escaper;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote;

/**
 * Everything the storefront needs to render the bar, ready to print.
 *
 * Both surfaces - the cart page template and the minicart Knockout component - read this one
 * structure, so they can never drift apart in wording, rounding or currency formatting. Amounts
 * arrive in base currency and leave converted and formatted for the current presentation
 * currency, because the JavaScript side deliberately does no maths and no formatting.
 */
class GoalBarState
{
    public function __construct(
        private readonly ProgressCalculator $calculator,
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly Escaper $escaper,
    ) {
    }

    /**
     * Render-ready state for the quote. `available` is false when the bar must stay hidden, and
     * is then the only key present.
     *
     * @param Quote $quote
     * @return array{
     *     available: bool,
     *     eligible?: bool,
     *     percent?: float,
     *     message?: string,
     *     successMessage?: string,
     *     min?: string,
     *     max?: string
     * }
     */
    public function get(Quote $quote): array
    {
        $progress = $this->calculator->calculate($quote);

        if ($progress === null) {
            return ['available' => false];
        }

        return [
            'available' => true,
            'eligible' => $progress->eligible,
            'percent' => round($progress->percent, 2),
            'message' => $this->buildMessage($progress->remaining),
            'successMessage' => (string) __('Congratulations! Your shipment is free!'),
            'min' => $this->priceCurrency->convertAndFormat(0.0, false, 0),
            'max' => $this->priceCurrency->convertAndFormat($progress->threshold, false, 0),
        ];
    }

    /**
     * Upsell sentence with the missing amount wrapped for styling. Built server-side so the
     * placeholder survives translation - JS dictionaries handle placeholders poorly.
     *
     * @param float $remaining
     * @return string
     */
    private function buildMessage(float $remaining): string
    {
        $amount = '<span class="freeshipping-goal-bar-amount">'
            . $this->escaper->escapeHtml($this->priceCurrency->convertAndFormat($remaining, false))
            . '</span>';

        return (string) __('Add %1 more to your cart and get free shipping!', $amount);
    }
}
