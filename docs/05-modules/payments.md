# Payments

Payments are part of the central/platform lifecycle.

## Lifecycle boundary

The payment lifecycle is deliberately separated from Stripe checkout completion.

```text
Checkout completed
    ↓
store checkout information

Payment intent succeeded
    ↓
process payment lifecycle
```

The payment state machine should reject paying a non-pending payment again. Duplicate Stripe delivery must therefore be handled as an expected integration concern.
