# Architecture

## 1. Style

SAASProgram uses a **modular, layered, API-first architecture**. It is not a strict textbook DDD implementation, but it adopts several DDD-like ideas: explicit domain states, domain services, business exceptions, role/permission boundaries, and isolated tenant contexts.

## 2. Main layers

### HTTP/API layer
Responsible for routes, middleware, Form Requests, authorization entry points, controllers, Resources, and JSON responses.

### Policy layer
Responsible for deciding whether the current authenticated tenant context can perform an operation.

### Service layer
Responsible for business workflows, transactions, state transitions, persistence orchestration, cache invalidation, notifications, and activity logging.

### Model/data layer
Responsible for persistence, relationships, casts, tenant connection binding, and model-level behavior.

### Infrastructure layer
Redis caching, queues, scheduler, Stripe integration, media storage, notifications, and activity logging.

## 3. Central/tenant boundary

```text
              ┌───────────────────────┐
              │   Central Database    │
              │ Users                 │
              │ Companies / Tenants   │
              │ Subscription Plans    │
              │ Subscriptions         │
              │ Payments              │
              └───────────┬───────────┘
                          │
                   Tenant resolution
                          │
        ┌─────────────────┴─────────────────┐
        ▼                                   ▼
┌────────────────────┐             ┌────────────────────┐
│ Tenant DB A        │             │ Tenant DB B        │
│ Users/Teams/etc.   │             │ Users/Teams/etc.   │
└────────────────────┘             └────────────────────┘
```

The important invariant is that a tenant request must execute against the correct tenant database before tenant models are read or written.

## 4. Request pipeline

```text
Route
  → Middleware
  → Authentication
  → Tenant resolution
  → Form Request
  → Policy
  → Controller
  → Service
  → Transaction / DB
  → Cache / notifications / activity after commit
  → Resource
  → JSON
```

## 5. Thin controllers

A controller should primarily:

1. Receive the request.
2. Authorize the action.
3. Delegate to a service.
4. Return the appropriate Resource/response.

Business rules such as `ProjectStatus` transitions do not belong in controller methods.

## 6. Transactions

State-changing service methods use transactions when multiple writes must succeed together. `ProjectStatusService::cancel()` is a concrete example because it changes the project and also updates incomplete tasks.

## 7. After-commit side effects

Notifications, activity logging, and cache invalidation for transactional state changes are registered after commit. This prevents a notification or cache flush from claiming a successful transition when the database transaction was rolled back.

## 8. Enum/cast boundary

Models cast state columns to enums for application-level safety. Persistence writes use enum values where query/update APIs require scalar database values.

A recurring bug during development was comparing a database-facing string to an enum instance incorrectly. The established rule is:

- application state: enum instance after model casting
- query/update arrays: `Enum::value`

## 9. Business exception boundary

`BusinessRuleException` is used when the request is syntactically valid but violates the current business state. Examples include trying to complete a non-in-progress task or complete a project with incomplete tasks. These are generally represented as `409 Conflict` at the HTTP level.
