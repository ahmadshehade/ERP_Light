# Feature Tests

Feature tests are the next major testing phase after the completed Unit suite.

## Required endpoint coverage

### Authentication

- login success/failure
- logout
- unauthenticated access
- invalid credentials/validation

### Tenant resolution

- tenant request resolves to correct database
- unknown tenant denied/not resolved
- tenant A cannot access tenant B resources

### CRUD endpoints

For each main resource:

- create `201`
- read `200`
- update `200`
- delete `204` or project-specific response
- validation `422`
- unauthorized `403`
- missing resource `404`

### Project actions

- start
- hold
- resume
- cancel
- complete
- invalid transitions `409`
- complete with incomplete tasks `409`

### Task actions

- complete
- hold
- cancel
- invalid transitions `409`
- permission restrictions `403`

### Stripe

Existing webhook tests should remain part of the full suite.

## Recommended execution

```bash
php artisan test
```

Then focus individual areas:

```bash
php artisan test tests/Feature/...
```

Coverage can be added once the environment has Xdebug/PCOV configured appropriately.
