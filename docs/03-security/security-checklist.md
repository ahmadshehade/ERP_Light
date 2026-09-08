# Security Checklist

Before production release:

- [ ] All API routes requiring authentication use Sanctum middleware.
- [ ] Tenant routes resolve tenancy before tenant queries.
- [ ] Policies deny cross-tenant access.
- [ ] Tenant permissions come from tenant membership context.
- [ ] Validation exists for every state-changing endpoint.
- [ ] Business-rule conflicts return stable `409` responses.
- [ ] Stripe signatures are verified.
- [ ] Stripe secrets exist only in environment configuration.
- [ ] No tenant data leaks through shared cache keys.
- [ ] Error responses do not expose stack traces/secrets.
- [ ] Upload MIME/type/size limits are enforced.
- [ ] Queued jobs do not lose tenant context.
- [ ] Scheduled commands initialize the correct tenant context.
- [ ] Tests cover tenant isolation and authorization boundaries.
