# Caching

Redis is used for application caching.

## Cache naming

The project uses a cache-tag enum such as `NameOfCache::PROJECT` / `TEAM` to group domain entries.

## Tenant-aware keys

Service cache keys incorporate tenant/user context where required. `TeamService` was designed with keys that include tenant identity and authenticated user identity.

## Invalidation

Mutation services flush the relevant tag after the transaction commits.

Example concept:

```text
Project mutation
   ↓
DB transaction
   ↓
afterCommit
   ├── flush PROJECT cache tag
   ├── notify
   └── activity log
```

## Development issue

An early Redis cache result produced `__PHP_Incomplete_Class`, which was resolved by clearing stale cache data and ensuring cached objects are compatible with the currently loaded class definitions.
