# Authorization

Authorization is handled through Laravel Policies combined with Spatie Permission.

## Tenant context

A central `User` may have a tenant membership represented by `TenantUser`. Permissions are evaluated in the tenant context.

A key implementation correction during development was replacing inappropriate calls such as `TenantUser->can()` with the permission API actually available in the tenant authorization model, notably `TenantUser->hasPermissionTo()`.

## Policy responsibilities

Policies answer questions such as:

- Can the user view this project?
- Can the user manage this task?
- Can the user access any resources of this type?
- Is the resource active and inside the user's tenant/team scope?

## Manager/employee scope

A recurring rule in the project is that a Manager can manage/view broader tenant resources, while an Employee may be restricted to resources associated with their teams or assigned scope.

The precise action matrix is documented separately in [Roles and Permissions](roles-permissions.md).
