# Endpoint Map

This map is a documentation checklist rather than a generated route dump. Exact route names and middleware should be synchronized with `routes/api.php` and module route files before release.

| Domain | Expected API responsibilities | Verification |
|---|---|---|
| Auth | Register/login/logout/session identity | `route:list` + Feature tests |
| Users | User/profile management | Feature tests |
| Tenant users | Membership, activation, departments, positions | Feature tests |
| Departments | CRUD/management | Feature tests |
| Positions | CRUD/management | Feature tests |
| Teams | CRUD/membership | Feature tests |
| Projects | CRUD + state actions | Feature tests |
| Tasks | CRUD + action state transitions | Feature tests |
| Plans | Plan management | Feature tests |
| Subscriptions | Subscribe/change/cancel lifecycle | Feature tests |
| Payments | Payment records/lifecycle | Feature + webhook tests |
| Media | Upload/attach/remove media | Feature/integration tests |
| Learning | Courses/sections/lessons/enrollments/reviews/quizzes | Needs repository verification |
| Webhooks | Stripe event endpoints | Existing webhook tests |
