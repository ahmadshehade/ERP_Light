# Multi-tenancy

The application uses `stancl/tenancy` to resolve and work with tenant databases.

## Logical model

```text
Central
  ├── User
  ├── Company / Tenant
  ├── SubscriptionPlan
  ├── Subscription
  └── Payment

Tenant
  ├── TenantUser
  ├── Department
  ├── Position
  ├── Team
  ├── Project
  ├── Task
  ├── Role / Permission
  └── Media / tenant operational data
```

## Tenant connection

Tenant models established during development explicitly use:

```php
protected $connection = 'tenant';
```

## Tenant lifecycle

Conceptually:

```text
Create company
→ create/resolve tenant
→ provision tenant database/schema
→ run tenant migrations
→ seed tenant roles/permissions
→ associate users
→ serve tenant API requests
```

The exact provisioning hooks and route middleware must remain aligned with the current `stancl/tenancy` configuration.

## Central domains

The development configuration used central domains including `127.0.0.1` and `localhost` for local tenancy resolution.
