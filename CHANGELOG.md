# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-08-19

First release. Successor to `ecomhouse/freeshippingprogressbar`, written from scratch rather than
renamed - the calculation, the configuration section and the frontend wiring all changed.

### Added

- Free-shipping goal bar on the cart page and in the minicart (Luma), with a "shipment is free"
  state once the cart reaches the goal. Both surfaces run the same Knockout component on the same
  customer-data section, so the bar follows AJAX cart updates (Breeze) as well as full reloads
  (Luma) - a server-rendered cart-page bar would freeze at its pre-update value. The component
  also refetches its section whenever the `cart` section runs ahead of it, so an extension that
  refreshes only `cart` cannot leave the bar showing a previous cart.
- Own configuration section under `Stores > Configuration > EcomHouse > Free Shipping Goal Bar`,
  store-view scoped, instead of extending Magento's core Checkout > Shopping Cart group.
- `Api\GoalProviderInterface` with carrier and manual implementations, selected by configuration
  and injectable from `di.xml` - the supported way to add a project-specific goal source.
- Own customer-data section `ecomhouse-freeshipping-goal-bar` with its own invalidation rules,
  replacing the predecessor's plugin on Magento's `cart` section.
- `Setup\Patch\Data\MigrateLegacyConfig`, which carries `ecomhouse/freeshippingprogressbar`
  settings over on `setup:upgrade`, preserving scope.
- English and Polish translations; starting styles with every colour exposed as a LESS variable.
- Unit tests covering the carrier mirror, goal resolution, clamping and the render-ready state.

### Changed

- **The amount compared against the goal now mirrors Magento's Free Shipping carrier** (base
  subtotal with discount, tax-inclusive where the carrier is), in base currency. The predecessor
  compared grand total minus shipping, which promised free shipping the carrier refused, moved
  backwards when a customer paid with a gift card, and was wrong on multi-currency stores. A store
  migrating from that module will see the bar sit at a different point for the same cart.
- The bar hides itself for empty and virtual carts, and whenever no goal resolves, rather than
  rendering an empty or zero-width bar.

### Security

- All storefront text and prices are built server-side and escaped there; the Knockout component
  only prints them. No inline scripts, no globals, no core template overrides - CSP-safe as
  shipped.
