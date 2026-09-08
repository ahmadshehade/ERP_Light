# Activity Logging

Spatie Activitylog is used to audit important business state changes.

## Project lifecycle example

After commit, a status transition logs an activity with properties including:

- `from_status`
- `to_status`

and a human-readable action such as:

- `Project started`
- `Project put on hold`
- `Project resumed`
- `Project cancelled`
- `Project completed`

Activity logging is intentionally coupled to successful committed state changes rather than pre-commit attempts.
