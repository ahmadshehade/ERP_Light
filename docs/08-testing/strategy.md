# Testing Strategy

The project uses a layered test strategy.

## Unit tests

Focus on isolated logic:

- Policies
- Services
- Business rules
- State transitions
- Boundary behavior

## Feature tests

Focus on HTTP behavior:

```text
Request
→ Route
→ Middleware
→ Authentication
→ Tenant resolution
→ Validation
→ Authorization
→ Controller
→ Service
→ Database
→ Resource
→ Response
```

## Testing principle

Do not test the same responsibility twice merely for coverage. For example, a Controller Unit test can mock authorization/dependencies rather than re-proving the entire Policy test suite.

## Priorities

1. Unit tests for domain/business logic.
2. Feature tests for public API contracts.
3. Integration tests for tenant database boundaries and Stripe/webhook interactions.
4. Coverage measurement after stable test execution.
