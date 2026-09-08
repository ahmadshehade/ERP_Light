# Tenant Users

`TenantUser` represents a user's membership inside a tenant/company.

## Responsibilities

- Link central user to tenant.
- Store tenant membership state such as `is_active`.
- Participate in tenant authorization.
- Associate with departments and positions.
- Participate in team/project/task scope.

## Request behavior established

Tenant-user creation/update validation included concepts such as:

- `user_id` must exist in central `mysql.users`.
- `user_id` must be unique inside the tenant membership table.
- `is_active` must be boolean.
- department IDs are supplied as an array when required.
- position IDs are supplied as an array when required.

## Position relation

The project completed a `tenant_user_positions` pivot for the many-to-many relationship between tenant users and positions.
