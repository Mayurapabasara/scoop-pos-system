# ScoopPOS API

ScoopPOS is a Laravel 12 REST API for managing an ice cream shop's inventory and customers. It uses Laravel Sanctum bearer tokens and backend-enforced role authorization.

## Features

- Sanctum login and logout with token revocation
- Owner, Manager, and Cashier roles enforced with Laravel policies
- Inventory CRUD with category relationships and item-name search
- Customer CRUD with duplicate-email protection
- Completed-sale creation with item snapshots, payment records, and atomic stock deduction
- Form Request validation and API Resources
- PHPUnit feature coverage, Laravel Pint, and GitHub Actions CI

## Requirements

- PHP 8.3 or later with `pdo_mysql`, `pdo_sqlite`, `mbstring`, and `xml`
- Composer 2
- MySQL 8 or later for local development

## Local setup

1. Install PHP dependencies.

   ```bash
   composer install
   ```

2. Copy the safe template and generate an application key.

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   In PowerShell, use `Copy-Item .env.example .env` instead of `cp`.

3. Create an empty MySQL database, then set the `DB_*` variables in `.env`. Do not commit `.env`.

4. Run migrations and development seed data.

   ```bash
   php artisan migrate --seed
   ```

5. Start the API.

   ```bash
   php artisan serve
   ```

The default base URL is `http://127.0.0.1:8000/api`.

## Development accounts

The seeder creates local-development accounts only. Each uses the password `password`; change or remove these accounts outside local development.

| Role | Email |
| --- | --- |
| Owner | `owner@scooppos.test` |
| Manager | `manager@scooppos.test` |
| Cashier | `cashier@scooppos.test` |

## Authentication

Log in to obtain a bearer token:

```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "owner@scooppos.test",
  "password": "password"
}
```

Send the returned token on protected requests:

```http
Authorization: Bearer <token>
```

`POST /api/auth/logout` revokes the token used for that request. Public registration is intentionally not provided.

## API endpoints

| Method | Endpoint | Access |
| --- | --- | --- |
| POST | `/api/auth/login` | Public |
| POST | `/api/auth/logout` | Authenticated |
| GET | `/api/items?search={name}` | All roles |
| GET | `/api/items/{item}` | All roles |
| POST | `/api/items` | Owner, Manager |
| PUT/PATCH | `/api/items/{item}` | All roles |
| DELETE | `/api/items/{item}` | Owner |
| GET | `/api/customers` | All roles |
| GET | `/api/customers/{customer}` | All roles |
| POST | `/api/customers` | All roles |
| PUT/PATCH | `/api/customers/{customer}` | All roles |
| DELETE | `/api/customers/{customer}` | Owner |
| GET | `/api/sales` | Owner/Manager: all; Cashier: own sales |
| GET | `/api/sales/{sale}` | Owner/Manager or the selling Cashier |
| POST | `/api/sales` | All roles |

Item creation and updates require a valid `category_id`, a non-negative price with up to two decimal places, and a non-negative integer stock quantity. Customer email is optional but unique when supplied.

All successful item and customer payloads are JSON API Resources under `data`. Create operations return `201 Created` and a `Location` header; deletion returns `204 No Content`. Validation failures use `422`, unauthenticated requests use `401`, forbidden role actions use `403`, and missing resources use `404`.

## Completing a sale

`POST /api/sales` records an immutable completed sale. It locks the requested inventory rows, verifies stock, stores unit-price and item-name snapshots, records one or more payments, and deducts stock in the same transaction. The payment total must exactly equal the calculated item total.

```json
{
  "customer_id": 1,
  "items": [
    { "item_id": 1, "quantity": 2 },
    { "item_id": 2, "quantity": 1 }
  ],
  "payments": [
    { "method": "cash", "amount": "11.00" }
  ]
}
```

Allowed payment methods are `cash`, `card`, and `digital`; a payment may include an optional `transaction_reference`. A sale cannot be updated or deleted through the API. Refunds are deliberately deferred until refund policy and payment-provider requirements are defined.

## Postman

Import both files from the `postman` directory:

- `ScoopPOS.postman_collection.json`
- `ScoopPOS.local.postman_environment.json`

Start with **Authentication → Login as Owner**. Its test script stores the returned token in the collection, then the inventory and customer requests use it automatically. The collection examples assume the database was seeded, so category ID `1` exists. Create requests capture their generated item/customer IDs for their following update and delete requests.

The included environment template deliberately contains an empty token and no credentials.

## Testing and quality checks

```bash
vendor/bin/pint --test
php artisan test
```

The default PHPUnit configuration uses a disposable in-memory SQLite database (`DB_DATABASE=:memory:`); it does not reset the local MySQL database.

### MySQL integration test profile

Run the same suite against MySQL before a release. This profile is intentionally separate from the default suite and cannot start until a local `.env.testing` file exists:

```powershell
Copy-Item .env.testing.example .env.testing
# Set only test-database credentials in .env.testing, then run:
php artisan test --configuration=phpunit.mysql.xml
```

The profile always selects `scoop_pos_testing`, and `RefreshDatabase` recreates its tables. Never point `.env.testing` at a development or production database. Create a least-privilege MySQL account that can create, alter, and drop tables only in that test database.

## Production configuration

Use `.env.production.example` as a credential-free reference for the deployment environment; do not copy it to source control with real secrets. Production must set `APP_DEBUG=false`, an HTTPS `APP_URL`, and `FORCE_HTTPS=true` only after TLS is terminated by the web server or a trusted reverse proxy.

Cross-origin API access is denied by default. If a browser client needs it, set `CORS_ALLOWED_ORIGINS` to an explicit comma-separated allowlist of HTTPS origins. Do not use `*` for authenticated clients.

Login is limited to five attempts per minute for each email-and-IP pair. Production logging uses Laravel's rotating `daily` log channel in the provided template; ship and alert on those logs through the hosting platform or a central log service.

GitHub Actions runs Composer validation, Pint, and the test suite on PHP 8.3 via [.github/workflows/ci.yml](.github/workflows/ci.yml).

## Project documents

- [Database design](docs/database-design.md)
- [ER diagram](docs/er-diagram.md)
- [Submission checklist](docs/submission-checklist.md)
- [QA test report](TEST_REPORT.md)

## Security notes

- `.env` is ignored and must never be committed.
- `.env.example` is a placeholder-only template; it contains no application key, password, token, or cloud secret.
- Passwords are hashed by Laravel and omitted from JSON responses.
- Authorization is enforced server-side through policies, not only in documentation or Postman.
