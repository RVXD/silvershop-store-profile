# SilverShop Store Profile

Central **shop identity + address** for [SilverShop](https://github.com/silvershop/silvershop-core), on
SiteConfig — one place for the shop name, legal name, VAT number, contact details, logo and a structured store
address, shared by other modules (invoicing seller, shipping-labels sender, …).

Core silvershop has no shop address (only allowed countries / base currency / from-email), so each module
rolled its own seller/sender identity and they diverged. This module makes it a single source of truth.

## What it adds

- **`StoreProfileExtension`** on `SilverStripe\SiteConfig\SiteConfig`, in a **Store profile** area under
  **Settings → Shop**:
  - Identity: `ShopName`, `LegalName`, `VatNumber`, `ContactEmail`, `ContactPhone`, and a `Logo` (Image).
  - Structured store address: street, line 2, postcode, city, region, ISO country — with **labels and
    formatting from [silvershop/address-formats](https://github.com/RVXD/silvershop-address-formats)** (the
    region field auto-hides for countries that don't use one).
- Accessors for consumers: `StoreProfileName()` (ShopName → LegalName → site title), `hasStoreAddress()`,
  `StoreAddressData()` (generic array), `StoreFormattedAddress($html)`.

## Consumers (soft dependency)

[invoicing](https://github.com/RVXD/silvershop-invoicing) (seller) and shipping-labels (sender) use the store
profile when it's present and filled, and fall back to their own fields otherwise — so each still works
standalone. Nothing hard-requires store-profile.

## Installation

```bash
composer require silvershop/store-profile
```

Then run `dev/build` and fill in **Settings → Shop → Store profile**. Requires
[silvershop/address-formats](https://github.com/RVXD/silvershop-address-formats) (installed automatically).

## Status

v1 — identity + structured address + logo, consumed by invoicing and shipping-labels. i18n: en, nl, de, fr, es,
it. Not yet included (future): registration / Chamber-of-Commerce number and bank/IBAN details (invoicing still
owns its registration-number field for now); multi-store scoping. See
[`PLAN-store-profile-address.md`](journal/PLAN-store-profile-address.md) in the consuming project.

## Licence

BSD-3-Clause.
