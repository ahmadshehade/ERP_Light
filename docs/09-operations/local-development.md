# Local Development

## Known development environment

- Ubuntu 24.04
- PHP 8.3.x
- MySQL
- Redis
- Nginx
- VS Code

The earlier XAMPP-based setup was replaced with native Linux services.

## Core commands

```bash
php artisan serve
php artisan route:list
php artisan test
php artisan test tests/Unit/...
php artisan test tests/Feature/...
php artisan cache:clear
php artisan config:clear
php artisan queue:work
php artisan schedule:work
```

Adjust commands to the project's configured runtime and deployment method.
