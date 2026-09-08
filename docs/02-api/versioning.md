# API Versioning

The current design uses versioned API namespaces such as `Api/V1` in Form Request paths.

## Rule

Once a public version is released, breaking changes should not silently change existing contracts.

Examples of breaking changes:

- Removing fields.
- Changing field types.
- Changing enum values.
- Removing endpoints.
- Changing authorization semantics unexpectedly.

## Evolution strategy

Prefer adding optional fields and new endpoints within the same version. Introduce a new version for breaking contract changes.
