# Unit Tests

The Unit testing stage completed coverage for the important Tenant authorization, controller, and business-service areas discussed during development.

## Completed groups

- `PositionPolicyTest`
- `PositionControllerTest`
- `TeamPolicyTest`
- `TeamControllerTest`
- `ProjectPolicyTest`
- `ProjectControllerTest`
- `ProjectStatusServiceTest`
- `TaskPolicyTest`
- `TaskControllerTest`
- `TaskActionServiceTest`

## Test design lessons

### Mocking controller dependencies

Controllers were tested without re-running policy implementation logic. Authorization calls were overridden/mocked where necessary so the controller test remained focused on controller behavior.

### Service tests and database isolation

Tenant services can otherwise try to use the real MySQL tenant connection or missing tenant schemas. The test strategy therefore uses mocks or an isolated SQLite setup for business-rule tests when relationships/roles require a database.

### Enums

Tests must respect model casts. A common mistake was expecting a persisted string in a context where the model exposed an enum instance.

### Time values

When matching update arrays containing timestamps, tests should validate the semantic type/value (for example a Carbon instance) instead of relying on brittle exact object equality.
