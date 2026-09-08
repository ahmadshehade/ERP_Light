# Positions

Positions describe job/organizational positions inside a tenant.

## Established schema concepts

- Tenant-specific positions table.
- Tenant-user/position many-to-many relationship through `tenant_user_positions`.

## Tests

`PositionPolicyTest` and `PositionControllerTest` were completed as part of the Unit testing stage.

A full PositionService test suite was intentionally not added because the testing strategy favors targeted tests of business logic and externally observable behavior rather than indiscriminate duplication of every service method.
