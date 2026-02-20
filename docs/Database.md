# Database Reference - Capell Address

This package provides basic tables for addresses and countries and integrates them with the core `Site` model.

## Migrations

- `database/migrations/create_countries_table.php`
- `database/migrations/create_addresses_table.php`

Run via the installer:

```
php artisan capell:address-install
```

This publishes the migrations and runs `php artisan migrate`.

## Factories

- `database/factories/CountryFactory.php`
- `database/factories/AddressFactory.php`

Use them in tests or seeders to quickly generate data.

## Relations registered at runtime

Within the service provider, the following are added:

- `Site::address()` — belongs to `Capell\\Address\\Models\\Address`
- `Site::country()` — has one through `Capell\\Address\\Models\\Country` via address

See: `src/Providers/AddressServiceProvider.php`.
