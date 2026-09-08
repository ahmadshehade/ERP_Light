# Scheduled Commands

## Project scheduler

A console command named:

```bash
php artisan tenant:process-scheduled-projects
```

was implemented to process projects whose scheduled start condition has been reached.

## Intended flow

```text
Scheduler / Cron
      ↓
iterate tenants
      ↓
initialize tenant context
      ↓
find ready Planned projects
      ↓
ProjectStatusService::start()
      ↓
afterCommit side effects
```

## Operational requirement

The tenant context must be initialized correctly before querying tenant projects. Earlier development failures included missing `tenant` connection configuration and incorrect tenancy initialization calls.

## Current business decision

The scheduler starts eligible **projects only**. It does not automatically change every task's status.
