# Troubleshooting Log

This file preserves important problems and the architectural lessons they revealed.

## Missing tenant table

Example error:

```text
Table 'tenant...tasks' doesn't exist
```

**Cause:** tenant database existed but required tenant migration/table did not.

**Lesson:** tenant provisioning must include tenant migrations.

## Missing media table

`tenant.media` did not exist when Media Library code executed.

**Lesson:** Spatie Media Library migration belongs in the tenant schema when tenant models own the media.

## Tenant connection not configured

Scheduled-command/service tests failed because the `tenant` connection was not available.

**Lesson:** tenant initialization is required before using tenant models, and tests should not accidentally hit production-like tenant infrastructure.

## Incorrect tenancy initialization

An attempted `Stancl\Tenancy` initialization method did not match the installed version/API.

**Lesson:** verify package-specific lifecycle APIs from the installed version before wiring console commands.

## Enum comparison mismatch

A project/task status read as an enum through model casting but test/service logic expected the raw string.

**Lesson:** keep enum comparisons consistent and use enum values at persistence/query boundaries.

## Stripe double processing

Both checkout completion and payment intent success were causing payment processing.

**Lesson:** map each external event to one authoritative lifecycle responsibility and make webhook handling idempotent.

## Cache `__PHP_Incomplete_Class`

A stale cached serialized object referenced a class definition no longer loaded as expected.

**Lesson:** clear stale caches during model/class refactors and prefer cacheable representations with stable serialization characteristics.

## Upload size limit

A large file around 253 MB exceeded an effective Media Library limit.

**Lesson:** align chunk upload configuration, Media Library limits, reverse-proxy limits, PHP limits, and application validation.
