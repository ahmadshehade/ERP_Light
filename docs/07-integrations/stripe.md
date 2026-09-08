# Stripe Integration

Stripe integration is central to the payment/subscription lifecycle.

## Event separation

A critical architectural decision was made after observing duplicate/related Stripe event delivery:

```text
checkout.session.completed
        │
        └── store checkout information

payment_intent.succeeded
        │
        └── execute payment lifecycle
```

The checkout event must not independently “pay” the same payment when the payment intent event will perform that state transition.

## Idempotency lesson

A payment can only transition from the expected pending state once. A second attempt produced:

```text
Only pending payments can be paid.
```

The correct architectural response was to make webhook responsibilities explicit and make duplicate delivery harmless.

## Webhook validation

The project includes tests for:

- invalid Stripe webhook signature;
- invalid Stripe webhook payload;
- successful checkout information persistence;
- payment lifecycle handling.

## Security

- Verify the Stripe webhook signature.
- Never trust client-provided webhook data.
- Keep Stripe secret/signing keys outside source control.
- Design event handlers to tolerate duplicate delivery.
