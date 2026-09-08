# Architecture Decisions Record

## ADR-001 — API-first / REST-oriented design

**Decision:** Expose business operations through HTTP resources and actions while keeping the application stateless.

**Reason:** The frontend/client should interact with a predictable JSON API rather than server-rendered views.

**Consequence:** HTTP methods, status codes, validation responses, Resources, authentication middleware, and Feature tests become first-class concerns.

## ADR-002 — Modular organization

**Decision:** Use `nwidart/laravel-modules` with a dedicated Tenant module and central/platform code.

**Reason:** Tenant-specific code has a clear boundary and can evolve independently from platform concerns.

## ADR-003 — Service layer for business logic

**Decision:** Controllers delegate workflows to services.

**Reason:** Business operations need transactions, policies, cache handling, notifications, logging, and reusable workflows without bloating controllers.

## ADR-004 — Policy + permission based authorization

**Decision:** Use Laravel Policies together with Spatie Permission. Tenant authorization is evaluated through the tenant membership context (`TenantUser`) rather than assuming the central `User` is the tenant authorization subject.

**Reason:** The same central user can participate in different tenant contexts with different roles/permissions.

## ADR-005 — Dedicated tenant database

**Decision:** Tenant operational data is kept in isolated tenant databases.

**Reason:** Strong data isolation and a clean company boundary.

## ADR-006 — Enum-backed domain states

**Decision:** Use PHP enums such as `ProjectStatus`, `TaskStatus`, `TaskPriority`, and `ProjectPriority`.

**Reason:** Avoid magic strings throughout business code and make allowed values explicit.

## ADR-007 — Transaction + afterCommit for state changes

**Decision:** State mutations use transactions and side effects occur after commit.

**Reason:** Keep database state and external side effects consistent.

## ADR-008 — Do not auto-change task statuses when a project starts

**Decision:** Project start only changes project state. Task state changes remain governed by task rules/services.

**Reason:** A project starting does not imply every task is ready or should enter an active state.

## ADR-009 — Stripe event separation

**Decision:** `checkout.session.completed` stores checkout information; `payment_intent.succeeded` is the event that performs payment lifecycle processing.

**Reason:** Stripe may emit multiple related events for the same checkout. Treating both as “pay” operations creates duplicate state transitions.

## ADR-010 — Cache by tenant-aware context

**Decision:** Cache keys and cache tags incorporate tenant/context identity where required, and tenant mutation services flush relevant tags.

**Reason:** Prevent stale or cross-tenant data and keep invalidation predictable.

## ADR-011 — Feature tests after Unit tests

**Decision:** Unit tests validate isolated business rules; Feature tests validate HTTP behavior end-to-end.

**Reason:** Both levels catch different classes of bugs. Unit tests are fast; Feature tests verify middleware, validation, auth, policies, persistence, and response contracts together.
