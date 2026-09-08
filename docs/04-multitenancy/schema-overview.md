# Logical Schema Overview

This is a logical schema map, not a generated ERD. It records entities explicitly established during development and their major relationships.

## Central entities

```text
User
  └── Tenant memberships (TenantUser)

Company / Tenant
  ├── Subscription
  └── Payment

SubscriptionPlan
  └── SubscriptionPrice
       └── Stripe Price reference

Subscription
  └── Company / Tenant

Payment
  └── Stripe checkout/payment intent references
```

## Tenant entities

```text
TenantUser
  ├── departments
  ├── positions ↔ tenant_user_positions
  └── team membership

Project
  └── Tasks
       └── Team association

Team
  └── Tenant user membership

Project / Task
  ├── status
  ├── priority
  ├── dates
  └── media where configured
```

## Schema verification rule

This file should be replaced or supplemented by an automatically generated ERD once the migration set is considered stable.
