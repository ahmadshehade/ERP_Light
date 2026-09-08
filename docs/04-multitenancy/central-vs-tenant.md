# Central vs Tenant Data

## Central data

Central data describes the platform and identity of the customer/company:

- Users
- Companies / Tenants
- Subscription plans
- Subscriptions
- Payments
- Platform-level Stripe/customer references

## Tenant data

Tenant data describes work performed inside a company:

- Tenant users
- Departments
- Positions
- Teams
- Projects
- Tasks
- Tenant roles and permissions
- Tenant media
- Tenant activity/audit context

## Why split the databases?

The split provides a strong company boundary and avoids relying on an application-level `tenant_id` filter for every operational query.

The cost is increased database lifecycle and testing complexity, which is why tenant-aware testing is a major part of the Feature test roadmap.
