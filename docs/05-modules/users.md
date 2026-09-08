# Users Module

The central `User` model represents the application's authenticated identity.

## Responsibilities

- Authentication identity
- Central profile association
- Relationship to tenant memberships
- Platform-level identity needed for subscriptions and tenant access

## Design rule

The `User` is not automatically the tenant authorization subject. Tenant operations typically resolve through the user's tenant membership (`TenantUser`).
