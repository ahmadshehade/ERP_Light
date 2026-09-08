# SAASProgram

## Overview

SAASProgram is an API-first Laravel application designed around **central platform data plus isolated tenant/company data**. The current architecture combines stateless API authentication, tenant resolution, policy-based authorization, service-layer business logic, API Resources, transactional state changes, caching, asynchronous/external integrations, and automated testing.

The project is intentionally structured so that controllers remain thin and business rules are kept in dedicated services and policies.

## Core goals

1. Provide a REST-oriented JSON API.
2. Isolate one tenant's operational data from another tenant.
3. Separate platform concerns from tenant concerns.
4. Centralize business rules in Services.
5. Validate input through Form Requests.
6. Authorize operations through Policies and tenant permissions.
7. Represent output through API Resources.
8. Handle business-state failures consistently through application exceptions.
9. Protect state changes with transactions and race-condition-aware logic.
10. Support payments, scheduled workflows, notifications, media, caching, and activity logging.
11. Verify behavior with Unit and Feature tests.

## Technology stack

- Laravel 13.x (project was updated to Laravel 13.17.0 during development)
- PHP 8.3.x in the current Ubuntu development environment
- MySQL
- Redis
- Laravel Sanctum
- `stancl/tenancy` 3.10.x
- `nwidart/laravel-modules`
- Spatie Permission
- Spatie Activitylog
- Spatie Translatable
- Spatie Media Library
- Stripe integration
- Pion Laravel Chunk Upload
- PHPUnit / Laravel testing stack

## Architectural flow

```text
Client
  ↓
API Route
  ↓
Middleware
  ├── Authentication
  └── Tenant resolution
  ↓
Form Request
  ├── Validation
  └── Input preparation
  ↓
Policy / Permission check
  ↓
Thin Controller
  ↓
Service
  ├── Business rules
  ├── Transaction
  ├── Model persistence
  ├── Cache invalidation
  ├── Notifications
  └── Activity log
  ↓
API Resource
  ↓
JSON Response
```

## Central and tenant split

The platform-level database owns entities such as users, companies/tenants, subscription plans, subscriptions, and payment records. A tenant database owns company operational data such as tenant users, departments, positions, teams, projects, tasks, tenant roles/permissions, and tenant media.

The exact migration list is maintained in the codebase; this documentation intentionally describes the logical model rather than inventing tables that were not explicitly established.

## Current implementation stage

The project has moved beyond basic CRUD architecture. Important work already completed includes:

- Tenant-aware authorization using `TenantUser->hasPermissionTo()`.
- Tenant Position and Position/User pivot work.
- Team, Project, and Task authorization.
- Project lifecycle services and scheduled project starting.
- Task state/action services.
- Cache-tag based invalidation.
- Transactional project/task state transitions.
- Stripe webhook separation between checkout completion and payment success.
- Unit tests for major Tenant policies, controllers, and business services.

The next major verification layer is the Feature/API suite, especially tenant resolution, authentication, authorization, validation, persistence, resources, and complete HTTP behavior.

## Documentation principle

Every architectural rule should answer **what**, **why**, and **where**:

- What behavior exists?
- Why was it designed that way?
- Where is the behavior implemented?

See the [Architecture Decisions](01-architecture/architecture-decisions.md) document for the key decisions and trade-offs established during development.
