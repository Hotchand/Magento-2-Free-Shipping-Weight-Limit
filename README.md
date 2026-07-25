# Hotchand - Free Shipping Weight Limit

**Magento 2 - Free Shipping with Weight-Based Flat Rate Fallback**

A lightweight Magento 2 shipping module that offers **free shipping up to a configurable weight limit**, and automatically applies a **flat rate fee** for orders that exceed that limit, all configurable from the Magento admin panel with no code changes required.

---

## The Problem

Magento's built-in **Free Shipping** method has no weight limit. Once enabled, it applies to every order regardless of size or weight, which can make heavy shipments unprofitable.

Magento's built-in **Flat Rate** method applies the same fee to every order, with no way to waive it for lighter orders.

There is no native Magento way to say:

> *"Free shipping for orders under 8 LBS - $9.99 flat rate for anything heavier."*

This module solves exactly that.

---

## The Solution

**Hotchand_FreeShippingWeightLimit** adds a single, unified shipping method that:

- Shows **Free Shipping** at checkout when the cart's total weight is at or below your threshold
- Automatically switches to a **configurable flat rate** when the cart weight exceeds the threshold
- Displays a clear, customisable label at checkout for both scenarios
- Requires zero theme changes or template overrides

---

## Features

- ✅ **Weight threshold** - configure the maximum weight for free shipping (e.g. 8 LBS)
- ✅ **Flat rate fallback** - configure the fee applied when the threshold is exceeded (e.g. $9.99)
- ✅ **Custom labels** - set the checkout display name for both the free and paid tiers
- ✅ **Enable/disable per store view** - full store-view scope support
- ✅ **Allowed countries** - restrict the method to specific countries
- ✅ **Sort order** - control where this method appears in the shipping step
- ✅ **Works with any weight unit** - LBS or KGS, reads from your Magento store config
- ✅ **Compatible with Magento's tax and discount system** - uses standard shipping carrier API
- ✅ **No third-party dependencies** - pure Magento 2 carrier implementation

---

## How It Works

At checkout, Magento collects the total weight of all items in the cart and passes it to every shipping carrier. This module's carrier:

1. Reads your configured weight limit from admin config
2. Compares it against the cart's total weight
3. Returns **one shipping rate**:
   - `$0.00` (free) if weight ≤ limit
   - Your configured flat rate if weight > limit

The customer always sees a single, clean option, the correct price is applied automatically.

```
Cart weight ≤ 8 LBS  →  "Free Shipping"         $0.00
Cart weight  > 8 LBS  →  "Standard Shipping"     $9.99
```

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | 8.1 or higher |
| Magento Open Source / Adobe Commerce | 2.4.4 – 2.4.8 |

---

## Installation

### Composer (Recommended)

```bash
composer require hotchand/module-free-shipping-weight-limit
php bin/magento module:enable Hotchand_FreeShippingWeightLimit
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
php bin/magento cache:flush
```

### Manual Installation

1. Create the directory `app/code/Hotchand/FreeShippingWeightLimit/`
2. Copy all module files into that directory
3. Run:

```bash
php bin/magento module:enable Hotchand_FreeShippingWeightLimit
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
php bin/magento cache:flush
```

---

## Configuration

Navigate to:

```
Stores → Configuration → Sales → Shipping Methods → Free Shipping with Weight Limit
```

| Field | Description | Default |
|---|---|---|
| **Enabled** | Enable or disable this shipping method | No |
| **Method Title** | Label shown at the top of the shipping option | `Free Shipping` |
| **Free Shipping Label** | Checkout label when weight is within the limit | `Free Shipping` |
| **Paid Shipping Label** | Checkout label when weight exceeds the limit | `Standard Shipping` |
| **Weight Limit** | Maximum cart weight for free shipping | `8` |
| **Flat Rate Fee** | Fee charged when cart weight exceeds the limit | `9.99` |
| **Error Message** | Message shown if the method cannot calculate a rate | `This shipping method is not available.` |
| **Ship to Applicable Countries** | All countries or specific countries only | All |
| **Ship to Specific Countries** | Multi-select country list | - |
| **Sort Order** | Position in the checkout shipping list | `10` |

---

## Checkout Experience

**Order under the weight limit:**
```
○  Free Shipping                              $0.00
```

**Order over the weight limit:**
```
○  Standard Shipping                          $9.99
```

Both labels are fully customisable from the admin panel.

---

## Setting the Weight Unit

This module uses Magento's store weight unit. To confirm or change your store's unit:

```
Stores → Configuration → General → Locale Options → Weight Unit
```

Set it to `lbs` or `kgs` and configure your **Weight Limit** in the same unit. For example, if your store uses LBS and you want free shipping up to 8 pounds, enter `8` in the Weight Limit field.

---

## Combining With Other Shipping Methods

This module works alongside other shipping methods. You can, for example:

- Enable this module for domestic flat rate + free shipping
- Keep UPS or FedEx live rates active for customers who want expedited options
- Disable Magento's default **Free Shipping** and **Flat Rate** methods to avoid duplication

---

## Module Structure

```
app/code/Hotchand/FreeShippingWeightLimit/
├── registration.php
├── composer.json
├── etc/
│   ├── module.xml              # Module declaration
│   ├── config.xml              # Default configuration values
│   └── adminhtml/
│       └── system.xml          # Admin configuration form
└── Model/
    └── Carrier/
        └── WeightBasedShipping.php   # Core shipping carrier
```

---

## Troubleshooting

### The shipping method does not appear at checkout

1. Confirm the method is **Enabled** in admin config
2. Flush the cache: `php bin/magento cache:flush`
3. Check that **all products in the cart have a weight set** - if any product has no weight, Magento may treat the cart weight as zero or skip weight-based carriers
4. If you have restricted to specific countries, confirm the shipping address country is included

### Free shipping is showing for heavy orders

Verify the **Weight Limit** field is saved correctly. Check your store's weight unit under `General → Locale Options → Weight Unit` and confirm the limit is in the same unit.

### The flat rate is showing for light orders

Same as above - check the weight unit. If your store is in KGS and you entered `8` expecting pounds, the comparison will be wrong.

### The method shows but the price is wrong

Clear config cache and full page cache:

```bash
php bin/magento cache:clean config full_page
```

---

## Frequently Asked Questions

**Can I set different thresholds per store view?**
Yes. All configuration fields support Default / Website / Store View scope.

**Does this work with virtual or downloadable products?**
Virtual and downloadable products have zero weight, so they will always qualify for free shipping under this method, which is the expected behaviour.

**Does this work with guest checkout?**
Yes. The carrier works at the quote level and applies equally to guest and registered customer checkouts.

**Can I use this alongside table rates or live carrier rates?**
Yes. Magento shows all available shipping methods at checkout. Enable or disable whichever combination you need.

**What happens if a product has no weight set?**
If a product has no weight (or weight = 0), it does not contribute to the cart total weight. The cart may qualify for free shipping even if the physical weight would exceed the limit. Always set accurate weights on your products.

**Can I have a different flat rate for different weight ranges?**
This module supports one threshold and one flat rate. For more complex tiered pricing, consider Magento's Table Rates feature (`Sales → Shipping Methods → Table Rates`).

---

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

1. Fork the repository
2. Create your feature branch: `git checkout -b feature/my-feature`
3. Commit your changes: `git commit -m 'Add my feature'`
4. Push to the branch: `git push origin feature/my-feature`
5. Open a Pull Request

---

## License

[MIT License](LICENSE)

---

## Author

**Hotchand Sajnani**
Senior PHP / Magento Engineer
[ConnectResale LLC](https://connectresale.com)

---

## Related Modules

- [Hotchand_CspManager](https://github.com/Hotchand/Magento-2-Content-Security-Policy-CSP-Whitelist-Manager) - Manage CSP whitelists from the Magento admin panel

---

## Changelog

### 1.0.0 - 2026-01-01
- Initial release
- Weight-based free shipping threshold
- Configurable flat rate fallback
- Custom checkout labels for free and paid tiers
- Per-store-view configuration
- Allowed countries support
