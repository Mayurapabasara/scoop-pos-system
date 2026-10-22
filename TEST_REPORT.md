# ScoopPOS Test Report

**QA date:** 2026-10-22  
**Scope:** Authentication, inventory, customer APIs, authorization, routing, validation, and formatting.

## Database safety

No database-reset command was run against the local application database. The automated suite runs with `APP_ENV=testing`, `DB_CONNECTION=sqlite`, and `DB_DATABASE=:memory:` as configured in `phpunit.xml`. `RefreshDatabase` therefore uses only disposable in-memory test data.

The suite has not been run against a configured MySQL test database in this environment. MySQL-specific verification remains a deployment-environment follow-up once disposable test credentials are available.

## Commands and results

| Command | Result | Evidence |
| --- | --- | --- |
| `php artisan test` | Passed | 54 tests, 176 assertions, 2.64 seconds. |
| `php artisan route:list --path=api -vv` | Passed | 13 API routes registered; all logout, user, item, and customer routes show `Authenticate:sanctum`. |
| `vendor/bin/pint --test` | Initially failed, then fixed | Detected formatting drift in `bootstrap/app.php`, `bootstrap/providers.php`, and `config/auth.php`. `vendor/bin/pint` applied its deterministic fixes; the subsequent `vendor/bin/pint --test` passed. |
| `composer validate --no-check-publish` | Passed | `composer.json` is valid. Composer also reported the sandbox Git-ownership warning; this did not affect dependency manifest validation. |
| `git diff --check` | Passed | No whitespace errors. |

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

## Authorization audit

`RoleAuthorizationTest` exercises all 30 role/action/resource combinations through Laravel's Gate and policy discovery.

| Resource | Owner | Manager | Cashier |
| --- | --- | --- | --- |
| Inventory items | List, view, create, update, delete | List, view, create, update | List, view, update |
| Customers | List, view, create, update, delete | List, view, create, update | List, view, create, update |

The policies are also invoked by the API: read and delete actions call `Gate::authorize`, while create and update Form Requests call the corresponding policy before validation/persistence.

## Defects found and resolved

- **Formatting drift:** fixed automatically by Laravel Pint in three framework/config files. The formatter and complete test suite both passed after the fix.
- **No remaining behavioral defects were found** by the automated suite or route/policy review.

## Final status

The current PHP test environment is green and the mandatory API routes and policy restrictions are registered and covered. Before release, repeat the same suite against a dedicated MySQL testing database to validate the production database engine configuration.
