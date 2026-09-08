# Departments

Departments group tenant users into organizational units.

## Relationships

A tenant user may belong to departments through the tenant-specific relationship designed during development.

## Service layer

`DepartmentService` owns departmental business operations and associated cache behavior rather than pushing those rules into controllers.
