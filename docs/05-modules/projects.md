# Projects

Projects are tenant-owned work containers.

## Known fields

From the established model/migration design:

- `project_id` for related resources such as tasks
- `title`
- `description`
- `status`
- `priority`
- `start_date`
- `end_date`
- `is_active`

The model casts `status` to `ProjectStatus`, `priority` to `ProjectPriority`, and dates to date values.

## Default state

New projects are created in `Planned` state.

## Status service

`ProjectStatusService` handles start, hold, resume, cancel, and complete operations. See [Project Lifecycle](../06-business/project-lifecycle.md).

## Tests

- Project policy
- Project controller
- Project status service

were all included in the completed Unit test suite.
