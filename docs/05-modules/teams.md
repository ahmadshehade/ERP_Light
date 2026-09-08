# Teams

Teams represent operational groups used to scope project/task visibility and management.

## Relationships

Teams participate in project/task authorization through tenant-user membership.

## Service design

`TeamService` uses cache tags and tenant/user-aware cache keys. A previous parameterization error in TeamService was traced to passing a boolean where an insert/update array was expected; this reinforced strict input construction before persistence.

## Tests

- `TeamPolicyTest`
- `TeamControllerTest`

were completed in the Unit test stage.
