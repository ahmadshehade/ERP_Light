# API Overview

SAASProgram is designed as a JSON REST-oriented API.

## Resource-oriented approach

Typical resources include users, tenant users, departments, positions, teams, projects, tasks, plans, subscriptions, payments, and learning resources.

The exact registered route list is a repository concern and should be generated from `php artisan route:list` for release documentation. This file records the API design contract rather than claiming routes that were not explicitly established during development.

## Request flow

```text
HTTP request
→ authentication
→ tenant resolution (where tenant endpoint)
→ validation
→ policy authorization
→ controller
→ service
→ persistence
→ resource
→ JSON response
```

## Statelessness

Authentication credentials/tokens are sent with each API request. Server-side session state should not be required to understand the request.

## Recommended endpoint groups

```text
/api/v1/auth/...
/api/v1/users/...
/api/v1/tenant-users/...
/api/v1/departments/...
/api/v1/positions/...
/api/v1/teams/...
/api/v1/projects/...
/api/v1/tasks/...
/api/v1/subscription-plans/...
/api/v1/subscriptions/...
/api/v1/payments/...
```

> The names above describe the established resource intent; verify exact route URIs against the current route files before publishing as a consumer-facing contract.
