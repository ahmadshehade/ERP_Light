# SAASProgram — Documentation Index

This folder is the project documentation baseline for **SAASProgram**, consolidated from the architecture, code decisions, business rules, integrations, bugs/fixes, and testing work completed during the project.

> **Scope note:** This documentation reflects what was established during development conversations and known code excerpts. Items marked **Planned**, **Partially implemented**, or **Needs repository verification** are deliberately not presented as confirmed implementation details.

## Documentation structure

### 01 — Overview and architecture
- [README](README.md)
- [Architecture](01-architecture/architecture.md)
- [Architecture Decisions](01-architecture/architecture-decisions.md)
- [Technology Stack](01-architecture/technology-stack.md)
- [Application Layers](01-architecture/application-layers.md)

### 02 — API
- [API Overview](02-api/overview.md)
- [API Conventions](02-api/conventions.md)
- [HTTP Status Codes](02-api/http-status-codes.md)
- [Errors and Exceptions](02-api/errors.md)
- [Versioning](02-api/versioning.md)
- [Endpoint Map](02-api/endpoint-map.md)

### 03 — Security and authorization
- [Authentication](03-security/authentication.md)
- [Authorization](03-security/authorization.md)
- [Roles and Permissions](03-security/roles-permissions.md)
- [Tenant Isolation](03-security/tenant-isolation.md)
- [Security Checklist](03-security/security-checklist.md)

### 04 — Multi-tenancy and data
- [Multi-tenancy](04-multitenancy/multi-tenancy.md)
- [Central vs Tenant](04-multitenancy/central-vs-tenant.md)
- [Database Architecture](04-multitenancy/database-architecture.md)
- [Logical Schema Overview](04-multitenancy/schema-overview.md)
- [Data Lifecycle](04-multitenancy/data-lifecycle.md)

### 05 — Modules
- [Users](05-modules/users.md)
- [Profiles](05-modules/profiles.md)
- [Companies and Tenants](05-modules/companies-tenants.md)
- [Subscription Plans](05-modules/subscription-plans.md)
- [Subscriptions](05-modules/subscriptions.md)
- [Payments](05-modules/payments.md)
- [Tenant Users](05-modules/tenant-users.md)
- [Departments](05-modules/departments.md)
- [Positions](05-modules/positions.md)
- [Teams](05-modules/teams.md)
- [Projects](05-modules/projects.md)
- [Tasks](05-modules/tasks.md)
- [Courses](05-modules/courses.md)
- [Sections](05-modules/sections.md)
- [Lessons](05-modules/lessons.md)
- [Enrollments](05-modules/enrollments.md)
- [Reviews](05-modules/reviews.md)
- [Quizzes](05-modules/quizzes.md)

### 06 — Business rules
- [Project Lifecycle](06-business/project-lifecycle.md)
- [Task Lifecycle](06-business/task-lifecycle.md)
- [Tenant User Rules](06-business/tenant-user-rules.md)
- [Concurrency and Race Conditions](06-business/concurrency.md)

### 07 — Integrations and infrastructure
- [Stripe](07-integrations/stripe.md)
- [Notifications](07-integrations/notifications.md)
- [Activity Logging](07-integrations/activity-log.md)
- [Media and File Uploads](07-integrations/media.md)
- [Caching](07-integrations/caching.md)
- [Queues](07-integrations/queues.md)
- [Scheduled Commands](07-integrations/scheduled-commands.md)

### 08 — Testing
- [Testing Strategy](08-testing/strategy.md)
- [Unit Tests](08-testing/unit-tests.md)
- [Feature Tests](08-testing/feature-tests.md)
- [Test Infrastructure Lessons](08-testing/test-infrastructure.md)

### 09 — Operations
- [Local Development](09-operations/local-development.md)
- [Environment Configuration](09-operations/environment.md)
- [Troubleshooting Log](09-operations/troubleshooting.md)
- [Development Status](09-operations/status.md)

## Project map

```text
SAASProgram
├── Central / Platform
│   ├── Users
│   ├── Companies / Tenants
│   ├── Subscription Plans
│   ├── Subscriptions
│   ├── Payments
│   └── Stripe lifecycle
│
├── Tenant
│   ├── Tenant Users
│   ├── Departments
│   ├── Positions
│   ├── Teams
│   ├── Projects
│   ├── Tasks
│   ├── Tenant roles / permissions
│   ├── Media
│   ├── Notifications
│   └── Activity log
│
└── Cross-cutting
    ├── Authentication
    ├── Policies
    ├── Form Requests
    ├── Services
    ├── Resources
    ├── Exceptions
    ├── Redis Cache
    ├── Queues
    ├── Scheduler
    └── Automated Tests
```
