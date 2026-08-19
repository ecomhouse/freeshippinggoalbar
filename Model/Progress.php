<?php

declare(strict_types=1);

namespace EcomHouse\FreeShippingGoalBar\Model;

/**
 * Immutable result of one progress calculation. All amounts are in base currency; presentation
 * currency conversion and formatting happen in the customer-data source and the view model.
 */
class Progress
{
    /**
     * @param float $threshold Goal the cart is progressing towards
     * @param float $remaining Amount still missing, never negative
     * @param float $percent Completion, clamped to 0-100
     * @param bool $eligible Whether the cart already reached the goal
     */
    public function __construct(
        public readonly float $threshold,
        public readonly float $remaining,
        public readonly float $percent,
        public readonly bool $eligible,
    ) {
    }
}
