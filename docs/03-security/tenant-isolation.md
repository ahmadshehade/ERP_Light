# Tenant Isolation

Tenant isolation is a core security property.

## Required invariant

A request operating in tenant A must not read/write operational records belonging to tenant B.

## Enforcement layers

1. Tenant resolution establishes the current tenancy context.
2. Tenant models use the tenant connection.
3. Policies verify tenant/user/team scope.
4. Cache keys/tags are designed to avoid cross-tenant leakage.
5. Feature tests should explicitly verify cross-tenant denial and isolation.

## Testing requirement

Tenant isolation should be tested with at least:

- same user, different tenant memberships where applicable;
- two tenant databases;
- resource IDs that are valid in one tenant but not another;
- cache behavior under both tenant contexts.
