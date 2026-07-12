---
name: capell-address-development
description: Use when editing Capell Address countries, addresses, selectors, or flag rendering.
---

# Capell Address

Reusable countries, addresses, address selectors, country selectors, and flags for Capell admin.

## Look

- `packages/address/src`
- `packages/address/docs`
- `packages/address/README.md`

## Rules

- Treat countries and addresses as shared admin primitives.
- Keep selectors reusable; avoid package-specific address assumptions.
- Site schema extension belongs in public extenders, not consuming packages.
- Run `vendor/bin/pest packages/address/tests`.
