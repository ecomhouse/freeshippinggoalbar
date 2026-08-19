<?php

declare(strict_types=1);

namespace EcomHouse\FreeShippingGoalBar\CustomerData;

use EcomHouse\FreeShippingGoalBar\Model\GoalBarState;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\CustomerData\SectionSourceInterface;

/**
 * Publishes the bar state as its own customer-data section.
 *
 * A dedicated section rather than a plugin on Magento's `cart` section: no interceptor on a core
 * class, our own invalidation rules in `sections.xml`, and a payload a future Breeze or Hyva
 * companion can consume as plain JSON. Sections travel in one `/customer/section/load` request,
 * so this costs no extra round trip.
 */
class GoalBar implements SectionSourceInterface
{
    public function __construct(
        private readonly CheckoutSession $checkoutSession,
        private readonly GoalBarState $state,
    ) {
    }

    /**
     * Section payload, see GoalBarState::get().
     *
     * @return array<string, mixed>
     */
    public function getSectionData()
    {
        return $this->state->get($this->checkoutSession->getQuote());
    }
}
