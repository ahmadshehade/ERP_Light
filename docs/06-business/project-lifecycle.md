# Project Lifecycle

## States

The project uses `ProjectStatus` values including:

- `Planned`
- `InProgress`
- `OnHold`
- `Cancelled`
- `Completed`

## Transition diagram

```text
Planned
   │
   ▼
In Progress ───────► Cancelled
   │
   ├───────────────► Completed
   │
   ▼
On Hold
   │
   └──────────────► In Progress
```

## Start

`start(Project)` requires `Planned`.

Effects:
- status becomes `InProgress`;
- if `start_date` is missing, it is set to now;
- `end_date` becomes null;
- cache/notification/activity happen after commit.

## Hold

Only `InProgress` projects can be put on hold.

## Resume

Only `OnHold` projects can be resumed to `InProgress`.

## Cancel

Only `InProgress` projects can be cancelled.

Effects:
- status becomes `Cancelled`;
- `end_date` becomes now;
- incomplete tasks are changed to `Cancelled`;
- completed/cancelled tasks are left unchanged;
- side effects run after commit.

## Complete

Only `InProgress` projects can be completed.

The service first verifies that no task remains outside `Completed` or `Cancelled`. If any incomplete task exists, completion fails with `409 Conflict` via `BusinessRuleException`.

## Scheduled start

A scheduled command checks for planned projects whose start time/date is ready and invokes the status service.

The project service intentionally **does not auto-start or mutate task statuses** as a side effect of project start.
