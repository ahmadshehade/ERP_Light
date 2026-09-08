# Roles and Permissions

## Established tenant roles

- Owner
- Manager
- Employee
- Guest

## Permission source

Tenant permissions are seeded and managed in the Tenant context. The project uses Spatie Permission for role/permission persistence.

## General role intent

| Role | General intent |
|---|---|
| Owner | Full tenant administration and management |
| Manager | Operational management of assigned tenant resources, with broader project/team access |
| Employee | Work within assigned/team-scoped resources |
| Guest | Limited/read-oriented access |

## Policy pattern

Role checks may be used for broad management rules, while permission checks provide granular control.

A policy may therefore combine:

```text
Role/permission
       +
resource state
       +
team/tenant membership
       ↓
allow / deny
```

## Seeders

Tenant authorization data is seeded through dedicated Tenant seeders, including role, permission, and role-permission seeders.
