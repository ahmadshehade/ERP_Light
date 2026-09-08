# Subscription Plans

Subscription plans define the commercial options offered by the platform.

## Established concepts

- Plan identity
- Pricing records
- Stripe Price identifiers
- Interval-based pricing

A `SubscriptionPrice` concept was designed with fields including:

- `plan_id`
- decimal `price`
- nullable `stripe_price_id`

The system separates a plan from its concrete price/interval data.
