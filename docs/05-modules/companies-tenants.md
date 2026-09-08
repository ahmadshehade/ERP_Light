# Companies and Tenants

A Company represents the customer/business boundary that owns a tenant environment.

## Responsibilities

- Identify the customer/tenant.
- Connect central subscription/payment state to the tenant.
- Act as the parent business entity for tenant operations.

The project uses a custom Tenant model based on the `stancl/tenancy` tenant model.
