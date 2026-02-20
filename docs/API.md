# API Reference - Capell Address

This document points you to the key namespaces and classes. Browse the `src` directory for full code.

- Service Provider
    - `src/Providers/AddressServiceProvider.php`
- Models
    - `src/Models/Country.php`
    - `src/Models/Address.php`
- Filament components (forms)
    - `src/Filament/Components/Forms/CountrySelect.php`
    - `src/Filament/Components/Forms/AddressSelect.php`
- Filament resources
    - Countries: `src/Filament/Resources/Countries`
    - Addresses: `src/Filament/Resources/Addresses`
- Schema extenders
    - `src/Filament/Resources/Sites/Schemas/Extenders/SiteSchemaExtender.php`
- Enums
    - `src/Enums/*`
- Commands
    - `src/Commands/InstallCommand.php`
    - `src/Commands/DemoCommand.php`
- Model registration
    - `src/AddressModelRegistrar.php`

Quick links:

- Source directory: `./src`
- Database reference: `./docs/Database.md`
