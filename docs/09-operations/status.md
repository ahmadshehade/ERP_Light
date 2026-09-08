# Development Status

## Completed

### Architecture
- API-only direction established.
- Layered architecture with thin controllers.
- Service-oriented business logic.
- Form Request validation.
- Policy + permission authorization.
- Central/tenant separation.
- Enum-backed state modeling.
- Transactions and after-commit side effects.

### Tenant operational domain
- Tenant users.
- Departments.
- Positions and tenant-user position pivot.
- Teams.
- Projects.
- Tasks.

### Project/task lifecycle
- Project start/hold/resume/cancel/complete service rules.
- Scheduled project start command.
- Task state/action service rules.
- Explicit decision not to auto-change task status when projects start.

### Payments
- Stripe webhook signature/payload validation tests.
- Checkout information persistence.
- Payment success lifecycle separation.
- Duplicate-event issue identified and lifecycle responsibilities separated.

### Testing
- Major Tenant Policy and Controller Unit tests completed.
- `ProjectStatusServiceTest` completed.
- `TaskActionServiceTest` completed.

## In progress / next

- Full Feature/API test suite.
- Cross-tenant isolation tests at HTTP level.
- Final route/API contract inventory.
- Release-quality OpenAPI/Swagger documentation.
- Coverage measurement.
- Final repository verification of learning-domain modules and exact schemas.

## Important status caveat

Some original product modules such as Courses, Sections, Lessons, Enrollments, Reviews, and Quizzes were part of the broader project model discussed during development, but their latest implementation state is not fully established in the available conversation context. They are documented here without invented endpoints or schema details.
