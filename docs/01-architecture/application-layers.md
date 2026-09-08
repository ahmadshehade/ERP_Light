# Application Layers

## Form Requests

Validate and normalize incoming data. Translation-aware request preparation is used in places such as task creation.

## Policies

Authorize operations against the authenticated subject and domain resource. Policies should not contain persistence workflows.

## Controllers

Thin HTTP adapters. Avoid embedding database transactions or complex state machines in controllers.

## Services

Own business workflows and invariants. Examples:

- `ProjectStatusService`
- `TaskActionService`
- `ProjectService`
- `DepartmentService`
- `TenantUserService`
- `TeamService`
- subscription/payment services

## Models

Represent persistence and relationships. Tenant models bind to the `tenant` connection where the database is isolated per tenant.

## Resources

Shape stable JSON output and keep API presentation separate from database models.

## Infrastructure

Caching, notifications, Stripe, media, activity logs, queues, and scheduled commands are kept outside simple HTTP controller responsibilities.
