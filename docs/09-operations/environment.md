# Environment Configuration

Important configuration areas include:

- central database connection
- tenant database connection/provisioning
- Redis
- queue connection
- Sanctum
- Stripe keys/webhook secret
- tenancy central domains
- media storage

## Security

Secrets must stay in environment configuration and never be committed.

## Redis

The development environment used `phpredis`, with Redis also acting as a cache/queue backend where configured.

## Tenancy

Local central domains previously included:

```text
127.0.0.1
localhost
```

Verify the final `config/tenancy.php` before publishing deployment instructions.
