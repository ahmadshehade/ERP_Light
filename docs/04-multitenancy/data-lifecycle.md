# Data Lifecycle

## New tenant

1. Create central tenant/company record.
2. Provision tenant database.
3. Run tenant migrations.
4. Seed tenant roles/permissions.
5. Create tenant membership records.
6. Begin serving tenant requests.

## Tenant mutation

Tenant request → tenant context → policy → service → transaction → tenant DB → after-commit side effects.

## Tenant removal/deactivation

The exact deletion policy must be verified against the current code. Never assume central deletion automatically removes a tenant database safely; database lifecycle needs an explicit operational process.
