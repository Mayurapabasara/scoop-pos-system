# ScoopPOS Test Report

**QA date:** 2026-10-25
**Scope:** Authentication, inventory, customer, sales, authorization, routing, validation, formatting, and production-readiness configuration.

## Database safety

No database-reset command was run against the local application database. The automated suite runs with `APP_ENV=testing`, `DB_CONNECTION=sqlite`, and `DB_DATABASE=:memory:` as configured in `phpunit.xml`. `RefreshDatabase` therefore uses only disposable in-memory test data.

A dedicated MySQL profile is available in `phpunit.mysql.xml`. It requires a local, ignored `.env.testing` configured for the `scoop_pos_testing` database and refuses to start without that file, preventing accidental use of development credentials. The profile was not run in this environment because the local MySQL80 service could not be started by this session and no dedicated test credentials are configured.

## Commands and results

| Command | Result | Evidence |
| --- | --- | --- |
| `php artisan test` | Passed | 60 tests, 221 assertions, 2.43 seconds. |
| `php artisan test --configuration=phpunit.mysql.xml` | Safely blocked | Exited before Laravel boot because `.env.testing` is absent; no database was touched. |
| `php artisan route:list --path=api -vv` | Passed | 13 API routes registered; all logout, user, item, and customer routes show `Authenticate:sanctum`. |
| `vendor/bin/pint --test` | Initially failed, then fixed | Detected formatting drift in `bootstrap/app.php`, `bootstrap/providers.php`, and `config/auth.php`. `vendor/bin/pint` applied its deterministic fixes; the subsequent `vendor/bin/pint --test` passed. |
| `composer validate --no-check-publish` | Passed | `composer.json` is valid. Composer also reported the sandbox Git-ownership warning; this did not affect dependency manifest validation. |
| `git diff --check` | Passed | No whitespace errors. |

## Production-readiness configuration

- `composer.json` now requires PHP 8.3 or later, matching the README and GitHub Actions runtime.
- `.env.production.example` documents safe production settings: debug disabled, HTTPS URL generation enabled after TLS termination, rotating daily logs, and an explicit CORS allowlist.
- Cross-origin API requests are denied unless `CORS_ALLOWED_ORIGINS` lists the calling HTTPS origin.
- The existing `POST /api/auth/login` route is limited to five requests per minute per email-and-IP pair. The route path, method, request body, and success response are unchanged.
- The automated suite verifies that the sixth login attempt receives HTTP 429.

## Endpoint audit

| Endpoint | Authentication | Verification coverage |
| --- | --- | --- |
| `POST /api/auth/login` | Public | Successful login, invalid password, required-field validation, and no token creation on failure. |
| `POST /api/auth/logout` | Sanctum bearer token | Successful revocation, missing-token rejection, and revoked-token rejection. |
| `GET /api/user` | Sanctum bearer token | Missing and revoked-token rejection. |
| `GET /api/items` | Sanctum bearer token | List response, category relationship, item-name search, and unauthenticated rejection. |
| `GET /api/items/{item}` | Sanctum bearer token | Resource response, unauthenticated rejection, and JSON 404 response. |
| `POST /api/items` | Sanctum bearer token + policy | Owner create success; cashier create returns 403; validation failures return 422 without persistence. |
| `PUT/PATCH /api/items/{item}` | Sanctum bearer token + policy | Cashier update success; validated price and quantity fields. |
| `DELETE /api/items/{item}` | Sanctum bearer token + policy | Owner success; Manager rejection; database deletion assertion. |
| `GET /api/customers` | Sanctum bearer token + policy | Owner, Manager, and Cashier list success; unauthenticated rejection. |
| `GET /api/customers/{customer}` | Sanctum bearer token + policy | All three roles can view; JSON 404 and unauthenticated coverage. |
| `POST /api/customers` | Sanctum bearer token + policy | All three roles can create; field and duplicate-email validation coverage. |
| `PUT/PATCH /api/customers/{customer}` | Sanctum bearer token + policy | All three roles can update; retaining the current email succeeds, conflicting email returns 422. |
| `DELETE /api/customers/{customer}` | Sanctum bearer token + policy | Owner success; Manager and Cashier return 403. |
| `GET /api/sales` | Sanctum bearer token + policy | Cashiers receive only their own sales; Managers receive all sales. |
| `GET /api/sales/{sale}` | Sanctum bearer token + policy | A Cashier is forbidden from viewing another Cashier's sale. |
| `POST /api/sales` | Sanctum bearer token + policy | Successful checkout creates sale, line, and payment records while deducting stock. Insufficient stock and mismatched payment totals return 422 without persistence. |

## Authorization audit

`RoleAuthorizationTest` exercises all 30 role/action/resource combinations through Laravel's Gate and policy discovery.

| Resource | Owner | Manager | Cashier |
| --- | --- | --- | --- |
| Inventory items | List, view, create, update, delete | List, view, create, update | List, view, update |
| Customers | List, view, create, update, delete | List, view, create, update | List, view, create, update |

The policies are also invoked by the API: read and delete actions call `Gate::authorize`, while create and update Form Requests call the corresponding policy before validation/persistence.

Sales use a separate policy: Owner and Manager can view every sale, while Cashiers can list and view only sales they completed. All three roles can create a sale.

## Defects found and resolved

- **Formatting drift:** fixed automatically by Laravel Pint in three framework/config files. The formatter and complete test suite both passed after the fix.
- **No remaining behavioral defects were found** by the automated suite or route/policy review.

## Final status

The current PHP test environment is green and the mandatory API routes and policy restrictions are registered and covered. Before release, repeat the same suite against a dedicated MySQL testing database to validate the production database engine configuration.
