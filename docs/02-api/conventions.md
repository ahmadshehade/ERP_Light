# API Conventions

## HTTP methods

| Method | Typical use |
|---|---|
| GET | Read resource/collection |
| POST | Create resource or execute a non-CRUD action |
| PUT/PATCH | Update resource |
| DELETE | Delete resource where supported |

## Response responsibilities

Controllers should return a consistent API response and use Resources for resource representation when a resource payload is returned.

## Validation

Invalid input is expected to return `422 Unprocessable Entity` with field-level errors using Laravel's validation structure unless the application's global exception handler normalizes it further.

## Authorization

Authentication failure is `401`; authenticated but unauthorized access is `403`.

## State conflicts

Business-rule conflicts such as invalid state transitions are represented by `409 Conflict`.

## Not found

A missing resource should return `404 Not Found`.

## Creation/update

Create operations typically return `201 Created`; successful reads/updates typically return `200 OK`. Delete operations may use `204 No Content` where no response body is required.
