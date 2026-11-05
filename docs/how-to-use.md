# How to Use and Configure

The goal bar has three settings, all in one place. Which of them matter depends on where your
store keeps its free-shipping threshold.

## Configuration

Go to `Stores > Configuration > EcomHouse > Free Shipping Goal Bar`:

| Field | Value |
|---|---|
| **Enable Goal Bar** | `Yes` |
| **Goal Amount Taken From** | `Free Shipping shipping method` or `Manual amount` — see below |
| **Goal Amount** | your goal, in base currency (only when the field above is `Manual amount`) |

Click **Save Config**, then clear cache:

```bash
bin/magento cache:flush
```

All settings are store-view scoped, so different store views can run different goals.

### Mode 1 — mirror the Free Shipping shipping method (recommended)

Set **Goal Amount Taken From** to `Free Shipping shipping method`. The bar then reads
`Stores > Configuration > Sales > Delivery Methods > Free Shipping > Minimum Order Amount`, and
compares the cart the same way that method does — so what the bar promises is what checkout
grants.

The bar stays hidden while the Free Shipping method is **disabled**: there is no goal to progress
towards in that case, and the module will not invent one.

### Mode 2 — a manual amount

Set **Goal Amount Taken From** to `Manual amount` and fill in **Goal Amount** (for example `150`).

Use this when free shipping comes from a cart price rule rather than the native shipping method.
**The bar does not create free shipping** — it only advertises it. Make sure the rule that grants
free shipping triggers at the same amount, otherwise customers are told shipping is free while
checkout still charges them.

The bar stays hidden while this value is `0`.

## What customers see

- **Below the goal** — "Add *X* more to your cart and get free shipping!" with a bar running from
  `0` to the goal.
- **At or above it** — "Congratulations! Your shipment is free!"

Both appear on the shopping cart page (above the cart contents) and in the minicart, under the
subtotal. Empty carts and virtual carts (downloads only — nothing to ship) show nothing.

## Which amount is compared against the goal

The cart's **subtotal after discounts**, in the store's base currency — the same amount Magento's
Free Shipping method itself compares. Specifically:

- catalog prices entered **excluding** tax → net subtotal + discounts;
- catalog prices entered **including** tax → gross subtotal + discounts;
- the Free Shipping method's own **Tax Including** setting on → subtotal with discounts, plus tax.

Shipping costs, gift wrapping and store credit are **not** part of it, on purpose: they are not
part of what the carrier looks at either.

On a multi-currency store the comparison happens in base currency and only the displayed amounts
are converted, so the bar reads the same regardless of the currency a customer browses in.

## Translations

The module ships `en_US` and `pl_PL`. To change the wording, add your own translation for these
source strings in a theme or a language package:

```
"Add %1 more to your cart and get free shipping!","<your text with %1>"
"Congratulations! Your shipment is free!","<your text>"
```

`%1` is replaced with the formatted missing amount, wrapped in
`<span class="freeshipping-goal-bar-amount">` so it can be styled separately.

## Styling

The module ships starting styles in `view/frontend/web/css/source/_module.less`. Every colour is
a LESS variable, so a theme can restyle the bar without copying the file:

```less
// in your theme's web/css/source/_extend.less
@freeshipping-goal-bar-background: #fff8e1;
@freeshipping-goal-bar-accent-color: #d35400;
@freeshipping-goal-bar-reached-background: #e5efe5;
```

| Class | Element |
|---|---|
| `.freeshipping-goal-bar` | outer wrapper (both surfaces) |
| `.freeshipping-goal-bar-reached` | added to the wrapper once the goal is reached |
| `.freeshipping-goal-bar-message` | the sentence above the bar |
| `.freeshipping-goal-bar-amount` | the highlighted missing amount |
| `.freeshipping-goal-bar-track` | the bar's background track |
| `.freeshipping-goal-bar-fill` | the filled part |
| `.freeshipping-goal-bar-limit` | the `0` and goal labels either side of the track |

The module uses no `!important`, so plain theme rules win.

## Storefront support

This module targets **Luma**. The minicart component is a Knockout UI component, which Breeze
does not run — install `ecomhouse/breezefreeshippinggoalbar` for that storefront. The
customer-data section is plain JSON, so the companion consumes it directly with no duplicated
calculation.

## Extending: your own goal source

The goal comes from an implementation of `EcomHouse\FreeShippingGoalBar\Api\
GoalProviderInterface`, chosen by the configured source. Register your own in a project module:

```xml
<type name="EcomHouse\FreeShippingGoalBar\Model\Goal\GoalResolver">
    <arguments>
        <argument name="providers" xsi:type="array">
            <item name="customer_group" xsi:type="object">Vendor\Module\Model\GroupGoalProvider</item>
        </argument>
    </arguments>
</type>
```

Return `null` from `getThreshold()` when your source has no goal for that cart — the bar then
hides itself instead of showing a meaningless zero.

Give the source an option in the **Threshold Source** select, and, when your goal is not measured
against the Free Shipping carrier's basis, a cart value of its own
(`EcomHouse\FreeShippingGoalBar\Api\CartValueProviderInterface`), both under the same key:

```xml
<type name="EcomHouse\FreeShippingGoalBar\Model\Config\Source\ThresholdSource">
    <arguments>
        <argument name="options" xsi:type="array">
            <item name="customer_group" xsi:type="string">Customer group threshold</item>
        </argument>
    </arguments>
</type>
<type name="EcomHouse\FreeShippingGoalBar\Model\CartValueResolver">
    <arguments>
        <argument name="valueProviders" xsi:type="array">
            <item name="customer_group" xsi:type="object">Vendor\Module\Model\GroupCartValue</item>
        </argument>
    </arguments>
</type>
```

A stored source whose provider is no longer registered (the project module was removed) falls
back to the carrier goal.

## Troubleshooting

### The bar does not appear anywhere

1. **Enable Goal Bar** must be `Yes` for the store view you are looking at.
2. Check that a goal actually resolves:
   - carrier mode → `Delivery Methods > Free Shipping` must be **Enabled** with a non-zero
     minimum order amount;
   - manual mode → **Goal Amount** must be greater than `0`.
3. The cart must contain something shippable — empty and virtual carts show nothing.
4. `bin/magento module:status EcomHouse_FreeShippingGoalBar` should report `Module is enabled`.
5. Clear cache and redeploy static content:

   ```bash
   bin/magento cache:flush
   bin/magento setup:static-content:deploy -f
   ```

### It appears on the cart page but not in the minicart

The minicart bar is driven by the `ecomhouse-freeshipping-goal-bar` customer-data section, which
browsers keep in `localStorage`. After a configuration change the stored copy can still be the
old one.

- Add or remove any cart item — that invalidates the section and the bar catches up.
- Or clear the browser's site data for the storefront.

This only affects the person whose browser holds the stale copy; new visitors get current values
immediately.

### The bar says shipping is free, but checkout still charges for it

You are in manual mode and nothing in Magento actually grants free shipping at that amount.
Either switch to carrier mode, or create a cart price rule with a **Free Shipping** action and a
matching subtotal condition.

### The bar sits at a different point than in the old module

Expected. `ecomhouse/freeshippingprogressbar` compared the grand total minus shipping; this module
compares the subtotal after discounts, the way the carrier does. On a store with tax-inclusive
prices the bar typically moves.

## FAQ

**Does it work for guests?**
Yes. Everything is derived from the quote, which exists for guests too.

**Does it slow down the cart?**
No extra queries: the calculation reads the already-loaded quote and cached configuration, and the
minicart values ride along with the customer-data request Magento makes anyway.

**Can I show it somewhere else, like the product page?**
Yes. The bar is a Knockout component reading the `ecomhouse-freeshipping-goal-bar` customer-data
section — instantiate `EcomHouse_FreeShippingGoalBar/js/view/goal-bar` from your own template, or
read that section from your own JS.

**Does it change how shipping is calculated?**
No. The module never plugs into shipping, totals or carriers. It reads and displays.

**Does it store any customer data?**
No. It writes nothing to the database and keeps nothing in the session; the customer-data section
is derived from the quote on each request.
