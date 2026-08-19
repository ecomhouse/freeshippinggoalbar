# CLAUDE.md

Guidance for Claude Code (or any AI assistant) working in this repository.

## Overview

`EcomHouse_FreeShippingGoalBar` renders a "you are X away from free shipping" progress bar on the
cart page and in the minicart. It plugs into nothing: no interceptor on any core class, no
override of any core template. It reads the quote and the configuration, and renders.

The module supersedes `ecomhouse/freeshippingprogressbar`, which it does not depend on or
reference at runtime beyond a one-off config migration patch.

## Architecture

Data flows one way: configuration -> goal -> progress -> render-ready state -> two surfaces.

- **`Model\Config`** — reads only `ecomhouse_freeshipping_goal_bar/*`. Carrier and tax settings
  are deliberately *not* read here; the classes that mirror them read them directly.
- **`Api\GoalProviderInterface`** — the module's single extension seam. `Model\Goal\
  CarrierGoalProvider` mirrors Magento's Free Shipping method, `Model\Goal\ManualGoalProvider`
  returns the configured amount. `Model\Goal\GoalResolver` picks one by the configured source;
  providers are injected from `etc/di.xml` keyed by that source, so a project can add its own
  (per customer group, per website) without touching module code.
- **`Model\CartValueResolver`** — the amount compared against the goal. See the constraint below.
- **`Model\ProgressCalculator`** — returns a `Model\Progress` value object, or null when the bar
  must stay hidden. One answer to "should this render", shared by both surfaces.
- **`Model\GoalBarState`** — turns `Progress` into render-ready strings: converted, formatted,
  translated. Both surfaces consume this and nothing else.
- **`CustomerData\GoalBar`** — publishes that state as the `ecomhouse-freeshipping-goal-bar`
  customer-data section.

## Where it renders

Both surfaces run **the same Knockout component on the same customer-data section** - there is no
server-side rendering of the bar and no view model.

- **Cart page** (`checkout_cart_index.xml`) — a template-only block at the top of
  `checkout.cart.container`, gated by `ifconfig` on the enable flag. The template is a bound
  container plus an `x-magento-init` that instantiates the component.
- **Minicart** (`default.xml`) — the same component registered into Magento's own
  `minicart_content.extra_info` region, which renders under the minicart subtotal.

## Non-obvious constraints

- **The compared amount mirrors the carrier, exactly.** `CartValueResolver` reproduces what
  `Quote\Address::requestShippingRates()` puts in the rate request and what
  `OfflineShipping\Model\Carrier\Freeshipping::isFreeShippingRequired()` compares: base subtotal
  plus discount, taken incl. tax when catalog prices include tax, or
  `base_subtotal_with_discount + base_tax_amount` when the carrier's own "Tax Including" setting
  is on. Everything is in **base currency**, converted only for display. Do not "simplify" this
  to grand total or displayed subtotal — the predecessor module used grand total minus shipping
  and consequently promised free shipping the carrier refused, moved backwards when a customer
  paid with a gift card, and lied outright on multi-currency stores.
- **A missing goal is null, never zero.** `GoalProviderInterface` returns null when the carrier
  is disabled or the manual amount is unset. The predecessor silently fell back to the other
  mode's value and rendered a `0 / 0` bar reading "add -159,95 € more". Keep null propagating.
- **All text and prices are formatted server-side** in `Model\GoalBarState`. The Knockout
  component is a pure renderer: no maths, no currency formatting, no translation. JS-side
  formatting hardcodes decimal separators, and JS translation dictionaries handle placeholders
  poorly.
- **Own customer-data section, not a plugin on `Checkout\CustomerData\Cart`.** Own invalidation
  rules in `etc/frontend/sections.xml`, no interceptor on a core class, and a payload a Breeze or
  Hyva companion can consume as plain JSON. Sections travel in one `/customer/section/load`
  request, so there is no extra round trip.
- **The cart page must not render the bar server-side.** It did until this was caught live: a
  view model rendered the numbers into HTML, and because Breeze updates the cart over AJAX
  without reloading the page, the bar stayed frozen at its pre-change value while the
  customer-data section was already correct. Luma hid the defect, since "Update Shopping Cart"
  does a full POST and reload. Reading the section on both surfaces is what keeps them honest -
  do not reintroduce a view model for the cart page.
- **The component self-heals a stale section.** `followCartUpdates()` pulls this section whenever
  the `cart` section carries a newer `data_id`. Extensions that update the cart over AJAX
  sometimes refresh a hardcoded list of sections (`customerData.reload(['cart'], true)` is the
  common shape), which marks their own fresh and leaves every other section stale in
  localStorage. Costs nothing when the storefront behaves; saves the bar from showing a previous
  cart's numbers when it does not.
- **The minicart follows the cart, not the config.** The section is invalidated by cart actions,
  so quantity changes update the bar live; a *configuration* change only reaches a browser that
  already holds a copy after any cart action or a cleared site storage. Say so in support answers
  rather than treating it as a bug.
- **Empty and virtual carts render nothing.** A virtual cart is never shipped, so there is no
  shipping to earn.
- **Translation keys are shared with the predecessor module.** Both use
  `"Add %1 more to your cart and get free shipping!"` and
  `"Congratulations! Your shipment is free!"`. With both modules installed side by side during a
  migration, whichever loads last wins the phrase. Harmless (the wording matches), but it
  explains a surprising string in a mixed install.

## Module dependencies

- `EcomHouse_Base` — required (admin config tab scaffolding).
- `Magento_Quote`, `Magento_Checkout`, `Magento_OfflineShipping` — core, sequenced.

The Breeze storefront is served by the separate `EcomHouse_BreezeFreeShippingGoalBar` companion,
which registers this module's minicart component in Breeze's own bundle and ships a plain-CSS
port of `_module.less` (Breeze compiles no Luma LESS). It reuses this module's Knockout template
and customer-data payload verbatim. Never couple this module to Breeze — and remember that any
visual change here has to be mirrored in the companion's CSS.

## Config migration

`Setup\Patch\Data\MigrateLegacyConfig` copies `checkout/cart/freeshipping_progress_enable`,
`use_freeshipping_method_config` (1 -> `carrier`, 0 -> `manual`) and
`freeshipping_progress_min_total` onto this module's paths, preserving scope. Legacy rows are
read, not deleted, so a staged migration with both modules installed keeps working.

## Tests

`Test/Unit/` covers the configuration reader, both goal providers, the resolver, the carrier
mirror (all four tax branches), the calculator (clamping, virtual/empty carts, missing goal) and
the state builder. Run with `bin/test ecomhouse_freeshippinggoalbar` from the sandbox root.
