# Tenant User Business Rules

## Membership

A central user can be represented inside a tenant through `TenantUser`.

## Uniqueness

The same central `user_id` should not be duplicated inside the same tenant membership collection.

## Activation

`is_active` controls whether the tenant membership is active and should participate in policy decisions where applicable.

## Organizational relationships

Tenant users can be associated with departments and positions. Position membership is many-to-many through `tenant_user_positions`.

## Authorization relationship

Tenant user membership is also the gateway for role/permission evaluation, so a central user can have different effective permissions across tenants.
