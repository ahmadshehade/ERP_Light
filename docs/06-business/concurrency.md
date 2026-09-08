# Concurrency and Race Conditions

Concurrency was treated as a real application concern, especially for state-changing services.

## Core risk

Two requests can attempt the same transition at nearly the same time:

```text
Request A → reads InProgress
Request B → reads InProgress
Request A → completes
Request B → also tries to complete
```

## Current protections

- Transactions around critical state changes.
- Strict state checks before mutation.
- Idempotency considerations for external webhooks.
- After-commit side effects.

## Stripe-specific idempotency

Related Stripe events must not cause the same payment to be processed twice.

## Future hardening

For extremely high contention transitions, the repository can consider row-level locking or idempotency keys where appropriate. Such additions should be driven by measured contention rather than added everywhere blindly.
