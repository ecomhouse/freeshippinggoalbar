# EcomHouse FreeShippingGoalBar

`ecomhouse/freeshippinggoalbar` shows customers how far their cart is from free shipping. It
renders a progress bar with the missing amount on the **shopping cart page** and in the
**minicart**, and switches to a "your shipment is free" message once the cart reaches the goal.

The goal is either mirrored from Magento's native Free Shipping shipping method — so the bar
promises exactly what checkout grants — or set manually per store view.

Polish documentation: [docs/jak-uzywac.md](docs/jak-uzywac.md).

## Requirements

| Requirement | Version |
|---|---|
| Magento Open Source | `2.4.x` |
| PHP | `>= 8.2` |
| `ecomhouse/base` | `^1.0` |

This module targets the **Luma** storefront. For the Breeze storefront install
`ecomhouse/breezefreeshippinggoalbar` alongside it — Breeze runs its own JS stack, so the
minicart component here does not render on it without that companion.

## Installation

### Composer

```bash
composer require ecomhouse/freeshippinggoalbar
bin/magento module:enable EcomHouse_FreeShippingGoalBar
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Manual

```bash
cd app/code
mkdir -p EcomHouse/FreeShippingGoalBar
# Extract module files into the directory created above
bin/magento module:enable EcomHouse_FreeShippingGoalBar
bin/magento setup:upgrade
bin/magento cache:flush
```

### Verify

```bash
bin/magento module:status EcomHouse_FreeShippingGoalBar
```

Expected: `Module is enabled`.

## Configuration

Path: `Stores > Configuration > EcomHouse > Free Shipping Goal Bar`

| Field | Type | Description |
|---|---|---|
| **Enable Goal Bar** | Yes/No | Master switch for both the cart page and the minicart. |
| **Goal Amount Taken From** | Select | `Free Shipping shipping method` mirrors `Delivery Methods > Free Shipping`. `Manual amount` reveals the field below. |
| **Goal Amount** | Number | The manual goal, in base currency, used when the field above is `Manual amount`. |

All settings are store-view scoped. The bar hides itself whenever no goal resolves — the Free
Shipping method disabled, or a manual amount of `0` — so an unconfigured store never shows a
broken bar. It also stays hidden for empty and virtual carts.

See [docs/how-to-use.md](docs/how-to-use.md) for the full walkthrough and troubleshooting.

## Migrating from `ecomhouse/freeshippingprogressbar`

This module supersedes `ecomhouse/freeshippingprogressbar`. On `setup:upgrade` it copies that
module's settings (`checkout/cart/freeshipping_progress_*`) onto its own configuration paths,
preserving scope, so a store keeps its goal and stays switched on:

```bash
composer remove ecomhouse/freeshippingprogressbar
composer require ecomhouse/freeshippinggoalbar
bin/magento setup:upgrade
```

The legacy rows are copied, not deleted. Note that the cart amount compared against the goal
changed deliberately — see the CHANGELOG and `docs/how-to-use.md`.

## Uninstalling

```bash
bin/magento module:disable EcomHouse_FreeShippingGoalBar
composer remove ecomhouse/freeshippinggoalbar
bin/magento setup:upgrade
bin/magento cache:flush
bin/magento setup:static-content:deploy -f
```

The module stores no data of its own beyond its `core_config_data` settings — uninstalling
leaves no other trace in the database.

## Support

- **Email:** opensource@ecom.house
- **Website:** https://ecom.house/
- **Changelog:** [CHANGELOG.md](CHANGELOG.md)
- **License:** OSL-3.0
