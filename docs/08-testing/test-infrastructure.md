# Test Infrastructure Lessons

## Tenant connection failures

A common failure mode was attempting to test tenant models against a real tenant connection without a configured tenant database. This produces errors such as:

```text
Database connection [tenant] not configured.
```

or missing-table errors.

## Missing SQLite file

When tests used SQLite, the database file itself needed to exist before connecting. The final service test setup used an isolated temporary SQLite file and the schema/roles needed by the tested logic.

## Roles table dependency

Testing `TenantUser::role(...)` requires the appropriate Spatie Permission schema and role data. Without those tables/records, a unit test can fail for infrastructure reasons instead of business logic reasons.

## Mocking best practice

When a service receives an array containing time-dependent values, match the semantic parts of the array with a callback rather than requiring exact object equality.
