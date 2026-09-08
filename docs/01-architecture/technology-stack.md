# Technology Stack

| Area | Technology | Role |
|---|---|---|
| Backend | Laravel 13.x | API framework |
| Language | PHP 8.3.x | Application runtime |
| Database | MySQL | Central and tenant persistence |
| Cache | Redis | Cache, session/queue infrastructure where configured |
| Auth | Laravel Sanctum | API authentication |
| Tenancy | stancl/tenancy 3.10.x | Tenant identification and database context |
| Modules | nwidart/laravel-modules | Modular project organization |
| Permissions | Spatie Laravel Permission | Roles and permissions |
| Activity log | Spatie Activitylog | Auditing/business event log |
| Translation | Spatie Translatable | Translatable model fields |
| Media | Spatie Media Library | Model media management |
| Chunk upload | Pion Laravel Chunk Upload | Large/chunked file uploads |
| Payments | Stripe | Checkout/payment workflow |
| HTTP testing | Laravel/PHPUnit | API verification |

The exact package patch versions should be verified from `composer.lock` before treating this table as a release manifest.
