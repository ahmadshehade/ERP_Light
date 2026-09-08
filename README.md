# SAASProgram

<p align="center">
  <h1 align="center">SAASProgram</h1>
</p>

<p align="center">
  Multi-Tenant RESTful API built with Laravel
</p>

<p align="center">
  A modular backend system for managing tenants, users, teams, projects, tasks, subscriptions, payments, and related business operations.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13.x-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-8.x-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Redis-Cache%20%26%20Queue-DC382D?style=flat-square&logo=redis&logoColor=white" alt="Redis">
  <img src="https://img.shields.io/badge/Sanctum-Authentication-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Sanctum">
</p>

---

## About the Project

**SAASProgram** is a multi-tenant backend application developed as a RESTful API using Laravel.

The project is designed around a clear separation between **central application data** and **tenant-specific data**, allowing each tenant to operate in an isolated environment while sharing the same application architecture.

The system has been developed with a focus on:

* Clean architecture
* Thin controllers
* Service-layer business logic
* Form Request validation
* Policy-based authorization
* Role and permission management
* Tenant data isolation
* Transactions and concurrency protection
* Redis caching
* Notifications and activity logging
* Scheduled background processing
* Stripe payment integration
* Automated testing

The project is structured to support the growth of a real-world multi-tenant business system rather than being limited to simple CRUD operations.

---

## Core Features

### Multi-Tenancy

The application separates central data from tenant data.

Central application data includes concepts such as:

* Users
* Companies / Tenants
* Subscription plans
* Subscriptions
* Payments

Tenant-specific data includes business resources such as:

* Tenant users
* Departments
* Positions
* Teams
* Projects
* Tasks

Tenant resolution and tenant database isolation are handled through the tenancy architecture used by the application.

---

### Authentication

The API uses Laravel Sanctum for API authentication.

Authentication is separated from tenant-level authorization so that the authenticated application user and the user's tenant membership remain distinct concepts.

---

### Authorization

Tenant authorization is based on roles and permissions.

The main tenant roles are:

* Owner
* Manager
* Employee
* Guest

Authorization is enforced through Laravel Policies and tenant permissions.

The tenant user context is used when evaluating permissions so that permissions are applied within the correct tenant scope.

---

### Users and Tenant Users

The application distinguishes between the central application user and the user's membership inside a tenant.

This allows the same application architecture to support:

```text
User
   │
   └── Tenant Membership
          │
          ├── Role
          ├── Permissions
          ├── Departments
          └── Positions
```

This separation is an important part of the tenant isolation strategy.

---

### Teams

Teams organize tenant users and are used in project and task management.

Teams participate in authorization and resource visibility, especially for employees whose access may depend on team membership.

---

### Projects

Projects support a controlled lifecycle rather than unrestricted status changes.

The current project lifecycle includes:

```text
Planned
   │
   ▼
In Progress
   ├──► On Hold
   │       │
   │       └──► In Progress
   │
   ├──► Cancelled
   │
   └──► Completed
```

Projects can be automatically started by scheduled processing when their configured start date is reached.

Project completion is protected by business rules, including validation that no incomplete tasks remain.

---

### Tasks

Tasks belong to projects and can also be associated with teams.

Task statuses include:

```text
Open
In Progress
On Hold
Completed
Cancelled
```

Task operations are handled through dedicated business logic instead of placing complex rules inside controllers.

Task completion, cancellation, and hold/resume operations are validated against the current state and the user's authorization.

---

### Subscriptions and Payments

The project includes subscription and payment concepts at the central level.

Subscription plans define available plans and pricing information.

Payments are integrated with Stripe and use webhook-driven processing to keep payment state synchronized with Stripe events.

---

### Stripe Integration

Stripe events are handled through webhooks.

A key part of the payment lifecycle is separating checkout completion from successful payment processing.

For example:

```text
Stripe Checkout
      │
      ▼
checkout.session.completed
      │
      └── Store checkout information

Payment
      │
      ▼
payment_intent.succeeded
      │
      └── Process successful payment
```

The implementation also protects the payment lifecycle against duplicate event processing so that the same payment is not processed more than once.

---

### Notifications

Business operations can trigger notifications, including project lifecycle events.

Notifications are deliberately separated from core controller logic and are executed as part of the relevant business workflow.

---

### Activity Logging

Important business operations are recorded using activity logging.

For example, project state changes record the previous and new state so that important domain actions remain traceable.

---

### Caching

Redis is used for caching.

The project uses cache tags for domain-specific invalidation, allowing resources such as projects, teams, and other tenant data to invalidate their related cache entries after mutations.

The cache strategy is designed around tenant-aware keys and resource-specific cache tags.

---

### Scheduled Processing

Scheduled commands are used for background business rules that should happen automatically.

One example is scheduled project processing:

```text
tenant:process-scheduled-projects
```

The command evaluates tenants and starts projects whose configured start date has been reached.

Tenant initialization and tenant database context are established before tenant-specific processing occurs.

---

### File and Media Handling

The application includes media/file handling for supported resources and uses Spatie Media Library.

Chunked uploads are supported through the chunk-upload infrastructure used by the project.

Large file handling and media-library constraints are treated separately from the business layer.

---

## Architecture

The API follows a layered architecture designed to keep HTTP concerns separate from business rules.

The typical request flow is:

```text
Client
  │
  ▼
API Route
  │
  ▼
Middleware
  │
  ├── Authentication
  └── Tenant Resolution
  │
  ▼
Form Request
  │
  ▼
Policy / Authorization
  │
  ▼
Controller
  │
  ▼
Service
  │
  ├── Business Rules
  ├── Transactions
  ├── Cache
  ├── Notifications
  └── Activity Logging
  │
  ▼
Model / Database
  │
  ▼
API Resource
  │
  ▼
JSON Response
```

### Architectural Principles

The project follows these principles:

* Controllers remain thin.
* Validation lives in Form Requests.
* Authorization lives in Policies.
* Business rules live in Services.
* Domain state is represented through Enums where appropriate.
* Database mutations use transactions where consistency is important.
* Side effects can be delayed until transaction commit.
* API responses are represented through Resources.
* Business exceptions use a consistent application-level exception strategy.
* Tenant data is isolated from central application data.

---

## Central Database vs Tenant Database

The application separates central and tenant data.

### Central

The central database contains application-wide data such as:

```text
Users
Companies / Tenants
Subscription Plans
Subscriptions
Payments
```

### Tenant

Each tenant database contains tenant-specific operational data such as:

```text
Tenant Users
Departments
Positions
Teams
Projects
Tasks
Media
Activities
and other tenant resources
```

Tenant models explicitly use the tenant database connection where required.

This separation is fundamental to the application's multi-tenant architecture.

---

## Database Design

The database design is divided into two logical areas.

```text
Central Database
│
├── users
├── companies / tenants
├── subscription_plans
├── subscription_prices
├── subscriptions
└── payments

Tenant Database
│
├── tenant_users
├── departments
├── positions
├── department_user
├── tenant_user_positions
├── teams
├── projects
├── tasks
└── related tenant tables
```

The exact schema and relationships are documented in:

`docs/database/`

---

## REST API

The project exposes versioned API endpoints under the API namespace.

The API is designed around resource-oriented endpoints and appropriate HTTP semantics.

Typical operations follow:

```text
GET     /api/v1/projects
POST    /api/v1/projects
GET     /api/v1/projects/{project}
PUT     /api/v1/projects/{project}
DELETE  /api/v1/projects/{project}
```

Business actions such as lifecycle transitions use dedicated endpoints where appropriate rather than forcing unrelated operations into generic CRUD updates.

---

## HTTP Status Codes

The API uses HTTP status codes according to the result of the operation.

Common responses include:

```text
200 OK
201 Created
204 No Content
401 Unauthorized
403 Forbidden
404 Not Found
409 Conflict
422 Unprocessable Entity
500 Internal Server Error
```

Business-rule conflicts use `409 Conflict` where appropriate.

Validation failures use `422 Unprocessable Entity`.

---

## Error Handling

The application uses a centralized exception strategy for application-level business errors.

Business rules can throw dedicated exceptions such as:

```text
BusinessRuleException
```

This allows controllers to remain free from large exception-handling blocks and keeps domain validation close to the business operation that owns it.

---

## Concurrency and Data Consistency

Important write operations are protected against race conditions and inconsistent state.

Where necessary, business operations use:

* Database transactions
* Tenant-specific database connections
* Controlled state transitions
* Validation of the current persisted state
* Post-commit side effects
* Cache invalidation after successful persistence

Project and task state changes are examples where consistency is particularly important.

---

## Testing

Testing is an important part of the project architecture.

The current strategy separates tests into:

### Unit Tests

Unit tests cover isolated business behavior such as:

* Policies
* Services
* Business rules
* Project status transitions
* Task action logic

Current tested areas include:

* Position Policy
* Position Controller
* Team Policy
* Team Controller
* Project Policy
* Project Controller
* Project Status Service
* Task Policy
* Task Controller
* Task Action Service

### Feature Tests

Feature tests are intended to verify the complete HTTP flow:

```text
Request
  ↓
Route
  ↓
Middleware
  ↓
Authentication
  ↓
Tenant Resolution
  ↓
Authorization
  ↓
Validation
  ↓
Controller
  ↓
Service
  ↓
Database
  ↓
Resource
  ↓
JSON Response
```

Feature coverage is the next major layer after the completed Unit-test work.

---

## Development and Environment

The project is developed using:

* Ubuntu
* PHP 8.3
* Laravel 13
* MySQL
* Redis
* Composer
* Node.js / NPM
* VS Code

The application uses Laravel's standard environment configuration together with the additional infrastructure required for tenancy, caching, queues, media management, and Stripe integration.

---

## Main Technologies

| Technology                   | Purpose                                                  |
| ---------------------------- | -------------------------------------------------------- |
| Laravel                      | Application framework                                    |
| PHP                          | Backend language                                         |
| MySQL                        | Central and tenant databases                             |
| Redis                        | Cache / queues / session infrastructure where configured |
| Laravel Sanctum              | API authentication                                       |
| Stancl Tenancy               | Multi-tenancy                                            |
| Spatie Permission            | Roles and permissions                                    |
| Spatie Activitylog           | Activity logging                                         |
| Spatie Translatable          | Translatable model attributes                            |
| Spatie Media Library         | Media management                                         |
| Pion Laravel Chunk Upload    | Chunked file uploads                                     |
| Stripe                       | Payments and webhooks                                    |
| PHPUnit / Laravel Test Suite | Automated testing                                        |

---

## Project Documentation

The complete technical documentation is available under:

```text
docs/
```

Start here:

* [`docs/README.md`](docs/README.md)
* [`docs/INDEX.md`](docs/INDEX.md)

The documentation covers:

* Architecture
* API design
* Authentication
* Authorization
* Multi-tenancy
* Database structure
* Modules
* Business rules
* Project lifecycle
* Task lifecycle
* Stripe
* Notifications
* Media handling
* Caching
* Scheduled jobs
* Testing
* Development notes
* Architectural decisions

---

## Project Structure

The application follows Laravel's structure together with modular tenant functionality.

A simplified view:

```text
SaasProgram/
│
├── app/
├── bootstrap/
├── config/
├── database/
├── docs/
│   ├── README.md
│   ├── INDEX.md
│   ├── architecture/
│   ├── api/
│   ├── authentication/
│   ├── authorization/
│   ├── tenancy/
│   ├── database/
│   ├── modules/
│   ├── business/
│   ├── payments/
│   ├── notifications/
│   ├── media/
│   ├── infrastructure/
│   └── testing/
│
├── Modules/
│   ├── Central/
│   └── Tenant/
│
├── routes/
├── storage/
├── tests/
├── artisan
├── composer.json
└── README.md
```

---

## Installation

Clone the repository and install dependencies:

```bash
git clone <repository-url>
cd SaasProgram

composer install
npm install
```

Create the environment file:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Configure the central database, tenant database settings, Redis, Stripe, and other environment variables in `.env`.

Run the required migrations and seeders according to the project tenancy/database setup.

Then start the application using the configured local web server.

---

## Testing

Run the complete test suite:

```bash
php artisan test
```

Run Unit tests:

```bash
php artisan test tests/Unit
```

Run a specific test file:

```bash
php artisan test tests/Unit/Tenant/TaskActionServiceTest.php
```

Feature tests can be executed in the same way once their endpoint coverage is in place.

---

## Development Philosophy

The project has been developed incrementally, with the architecture evolving around real business requirements rather than forcing every feature into generic CRUD patterns.

Important decisions include:

* Keep controllers thin.
* Keep business rules in Services.
* Keep authorization in Policies.
* Keep validation in Form Requests.
* Keep tenant data isolated.
* Use transactions for critical state changes.
* Protect state transitions through explicit business rules.
* Avoid duplicate payment processing.
* Invalidate cache after successful writes.
* Trigger side effects after successful database commits when appropriate.
* Test important business logic independently before testing complete HTTP flows.

---

## Current Development Status

The project currently has the core architecture and several major business domains implemented.

Completed or substantially implemented areas include:

* Central / Tenant separation
* Authentication
* Tenant authorization
* Roles and permissions
* Tenant users
* Departments
* Positions
* Teams
* Projects
* Tasks
* Project lifecycle management
* Task lifecycle management
* Scheduled project processing
* Redis caching
* Activity logging
* Notifications
* Stripe payment integration
* Media management
* Unit testing for core policies, controllers, and business services

The next major verification layer is broader Feature/API testing and continued endpoint-level validation.

---

## Security

The application should be deployed with production-safe values for:

* Application secrets
* Database credentials
* Redis credentials
* Stripe secrets
* Webhook signing secrets
* Authentication configuration

Never commit `.env` or production credentials to source control.

Tenant isolation must be preserved at every data-access boundary.

Authorization should always be evaluated within the correct tenant context.

---

## License

This project is developed as a private application/project and does not inherit the Laravel framework license for the application's own code.

For dependency licenses, refer to the respective packages used by the project.
