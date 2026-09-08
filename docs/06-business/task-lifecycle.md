# Task Lifecycle

## States

```text
Open
 │
 ▼
In Progress ──► Completed
 │
 ├────────────► Cancelled
 │
 ▼
On Hold ──────► In Progress
```

The full set is:

- `open`
- `in_progress`
- `completed`
- `cancelled`
- `on_hold`

## Complete task

Rules established in `TaskActionService`:

1. The user must be authorized to manage the task.
2. The task must be `InProgress`.
3. Due-date business rules are evaluated.
4. Successful completion writes `completed` and `completed_at`.
5. Related data can be loaded for the response after mutation.

## Cancel task

Cancellation is allowed from the relevant active/held states according to the service rules; an already completed or cancelled task cannot simply be cancelled again.

## Hold task

Only an `InProgress` task can be put `OnHold`.

## Project cancellation interaction

When a project is cancelled, any task not already completed/cancelled is updated to `Cancelled` in the same project transaction.
