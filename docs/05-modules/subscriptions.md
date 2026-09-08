# Subscriptions

Subscriptions represent a company's commercial entitlement to the platform.

## Responsibilities

- Connect company/tenant to a selected plan.
- Track subscription lifecycle.
- Work with Stripe identifiers where applicable.
- Derive subscription periods from price interval/business rules.

A development bug occurred when a price object/JSON-like value was inserted into a field expected to represent an ID. The service layer was then aligned so interval/price values are normalized before persistence.
