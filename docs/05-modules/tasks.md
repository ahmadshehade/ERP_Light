# Tasks

Tasks are tenant work items associated with projects and optionally teams.

## Known fields

- `project_id`
- `team_id`
- `title`
- `description`
- `status`
- `priority`
- `start_date`
- `due_date`
- `completed_at`
- `is_active`

## Enums

### TaskStatus

- `open`
- `in_progress`
- `completed`
- `cancelled`
- `on_hold`

### TaskPriority

- `low`
- `medium`
- `high`
- `urgent`

## Action service

`TaskActionService` owns state-changing task actions such as completion, cancellation, and hold/resume operations.

## Business protection

Completing a task requires it to be `InProgress`. Due-date business rules and authorization are evaluated before completing it.

## Tests

- `TaskPolicyTest`
- `TaskControllerTest`
- `TaskActionServiceTest`
