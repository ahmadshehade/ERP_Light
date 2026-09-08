# Database Architecture

## Connections

The project distinguishes at least:

- `mysql` / central connection for platform data
- `tenant` connection for the active tenant database

## Model examples

Tenant models such as `Project` and `Task` bind to the `tenant` connection and define their tenant-specific tables.

## Migrations

The application has central and tenant migrations. Tenant migrations must exist before a tenant database can successfully query its tables.

A concrete development issue occurred when a tenant database existed but a tenant table such as `tasks` had not yet been created. The lesson is important:

> Tenant provisioning is not complete until required tenant migrations have been applied.

## Schema ownership

Central migrations own platform tables. Tenant migrations own operational tenant tables. Do not mix these responsibilities casually, because the wrong connection produces runtime failures and potential security risks.
