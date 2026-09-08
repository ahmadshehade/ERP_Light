# Authentication

Laravel Sanctum is the API authentication mechanism established for the project.

## Responsibilities

- Authenticate the API caller.
- Expose the authenticated `User` to downstream application code.
- Allow authorization to use the central identity plus tenant membership context.

## Important distinction

Authentication answers:

> Who is this user?

Authorization answers:

> What may this user do in this tenant and on this resource?

The project deliberately keeps these concepts separate.

## Security expectations

- Never trust tenant identifiers from client input without validating them against the authenticated context.
- Do not use central-user permissions as a substitute for tenant permissions.
- Never expose payment secrets or Stripe signing secrets in API responses.
- Validate all state-changing requests through Form Requests and Policies.
