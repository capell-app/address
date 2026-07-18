# Address

<!-- prettier-ignore-start -->

## What it does

Address provides Capell's shared Country reference list and structured postal Address records. Sites and other packages can point to those records instead of keeping separate copies of the same location fields.

## Setup and country data

Install Address and run its migrations before a package that depends on Address. The package installer also publishes the country-flag assets used by the admin. It does not populate a country dataset automatically.

Open **Web Pages > Countries** to add countries manually, or import a controlled ISO dataset with `capell:address-countries-import path/to/countries.json`. JSON and CSV are supported. Run with `--dry-run` first. `--restore` includes soft-deleted matches; `--disable-missing` disables every currently enabled country absent from the supplied dataset, so use that option only with a complete authoritative list.

Country records are installation-wide reference data rather than site-owned records. Disabling or deleting a country can make existing addresses fail the Address data-quality diagnostic, so review dependent records before changing the reference list.

## Manage addresses

Open **Web Pages > Addresses** to create, edit, replicate, soft-delete, restore, or force-delete records according to your Address permissions. An address includes a display name, postal fields, country, enabled state, and default flag. The package does not call an external validation or geocoding service when you save through this screen.

Address queries are restricted for non-global administrators to records used by their assigned sites. An administrator with no assigned sites sees none. Global administrators can see all records. Country access uses separate Country permissions and is not site-scoped.

The Site editor also provides an address picker. A saved site relationship is treated as ownership: an unowned address is claimed by the first site that uses it, and attaching an address already owned by another site creates a copy and updates the new site's relationship. Editing a shared address through a picker can also clone it before applying changes. This prevents one site's edit from silently changing another site's postal details, but it can leave similar records that Diagnostics reports as likely duplicates.

Before force-deleting an address, check the Sites count and any consuming package relationships. The package protects site edits through copy-on-write, but it cannot infer every relationship created by another package.

## Quality checks and optional geocoding

Diagnostics checks that the tables exist, every address uses an enabled country, stored coordinates are valid, and likely duplicate groups are visible. Missing coordinates are counted but do not by themselves fail the data-quality check. Validation and geocoding providers are optional extension points; having none configured is not an error.

If an integration has registered an available geocoding provider, run `capell:address-geocode-normalize --dry-run` to preview a batch. Use `--limit=100` to bound it or `--provider=provider-key` to choose one provider. Without an available provider the command scans safely and writes nothing. A successful run stores latitude, longitude, provider key, confidence, and geocoded time in encrypted address metadata. The command is synchronous, is not scheduled, and has no automatic retry; use small batches and review provider failures before rerunning.

The core Address package exposes validation-provider contracts to integrations, but it does not provide a batch validation command or automatically normalise postal fields from those providers.

## Privacy and data lifecycle

Address name, street, city, region, postal code, and metadata are encrypted at rest. Deterministic HMAC indexes of the first address line and postal code support exact duplicate lookup; keep `APP_KEY` stable or encrypted values and those indexes will no longer be usable. Country names and codes, ownership IDs, enabled/default flags, timestamps, and user stamps are not encrypted. Treat coordinates as precise location data and protect database, backup, and admin access.

When Privacy Center is present, Address contributes export and erasure only for a **Site** subject: it exports addresses whose `site_id` matches that site and soft-deletes those owned records on erasure. It does not guess that an address belongs to a person because an order, booking, attendee, or customer references it. The consuming package must export or erase that relationship, and must detach rather than delete a record still needed by another subject or legal record. Country reference data is never part of subject erasure.

There is no age-based address retention or scheduled cleanup. Soft-deleted records remain until an authorised force-delete, and external geocoding providers may have their own request-data retention terms.

---

For how to use Address, see the [admin guide](admin-guide.md).
For developers: see the [README](../README.md).

<!-- prettier-ignore-end -->
