# ScoopPOS Implementation Plan

## Purpose and audit baseline

This is a prioritized implementation plan, not an implementation. It is based on the `Backend Engineer Intern Practical Exam` PDF and a read-only audit of the repository on 2026-10-22.

The assessment requires a Laravel + MySQL backend for inventory items and customers, authentication, backend-enforced Owner/Manager/Cashier permissions, migrations/seeders, an ER diagram, tests, and GitHub-ready documentation. It does **not** prescribe inventory or customer field names, response envelopes, pagination, token type, or a C# client.

### Current state

The project is a Laravel 12 skeleton. Laravel Sanctum and its migration are installed, but no assessment-specific API has been implemented.

| Area | Status | Audit evidence |
| --- | --- | --- |
| Laravel baseline | Implemented, but version alignment needed | Laravel 12 is declared; the available PHP CLI is 8.2.12 while project guidance asks for PHP 8.3+. |
| MySQL configuration | Incomplete | `config/database.php` supports MySQL and local `.env` selects it, but `.env.example` defaults to SQLite. |
| Users / roles | Incomplete | Stock `users` migration/model has no `role`; only one generic `Test User` is seeded. |
| Sanctum tokens | Incomplete | Package/config/migration exist, but `User` lacks `HasApiTokens` and no token issue/revoke flow exists. |
| Authentication endpoints | Missing | No `/api/auth/login` or `/api/auth/logout` routes, controller, or request validation. |
| Inventory API | Missing | No item model, migration, controller, request, resource, policy, route, or test exists. |
| Customer API | Missing | No customer model, migration, controller, request, resource, policy, route, or test exists. |
| RBAC enforcement | Missing | No role representation, policies/gates, or authorization middleware exists. |
| API response/error consistency | Missing | No API resources or JSON exception policy exists. |
| Test suite | Incomplete | Only default example tests exist; test DB configuration is commented out in `phpunit.xml`. |
| README / environment template | Incomplete | `README.md` is stock Laravel text; `.env.example` exists but lacks ScoopPOS/MySQL/test instructions. |
| Postman collection | Missing | No collection or environment file is in the repository. |
| ER diagram | Missing | No ER diagram or database-design document is in the repository. |
| Optional WPF client | Not implemented; not required | The PDF explicitly marks WPF as bonus only. |

## Assessment requirements versus enhancements

### Mandatory assessment work

- Laravel backend using MySQL, migrations, seeders, appropriate authentication and authorization.
- Authenticated inventory-item CRUD and customer CRUD.
- Backend-enforced role matrix:

  | Resource | Owner | Manager | Cashier |
  | --- | --- | --- | --- |
  | Inventory items | Create, read, update, delete | Create, read, update | Read, update |
  | Customers | Create, read, update, delete | Create, read, update | Create, read, update |

- Role-specific seeded or manually created initial users.
- RESTful authentication, inventory, and customer APIs.
- Tests supporting API reliability.
- Database design and ER diagram.
- Public GitHub-ready source, meaningful history, README, `.env.example`, and setup/run/seed instructions.

### Optional or encouraged work

- Item name search is encouraged by the assessment. Implement it only after mandatory CRUD/RBAC tests pass, as `GET /api/items?search=...`.
- Pagination, filtering beyond name search, sorting, token expiry/abilities, rate limiting, OpenAPI, Docker, CI, soft deletes, audit logs, and a browser frontend are enhancements, not assessment requirements.
- The C# WPF desktop client is a bonus. Do not start it until the mandatory Laravel backend is complete and verified.

## Assumptions to confirm before Phase 1

The PDF does not define item or customer attributes. Do not silently treat the following as assessment requirements. Record the chosen schema in the README and ER diagram before implementation.

1. Resolved in database-design phase: `categories` represent the shop's flavor groups, and each `item` has a required `category_id`. Item fields are `id`, `category_id`, `name`, nullable `description`, `unit_price`, `stock_quantity`, and timestamps. This is an interpretation of the business scenario's flavor/category wording, not a mandated schema.
2. Proposed minimal `customers` fields: `id`, `name`, `email` nullable/unique, `phone` nullable, timestamps. Decide whether email is required before writing rules and a unique index.
3. Use Laravel Sanctum personal-access bearer tokens. The assessment permits an appropriate Laravel mechanism; the project already includes Sanctum, while its Passport example is illustrative rather than mandatory.
4. A successful login returns the token and a safe user resource (`id`, `name`, `email`, `role`), never a password, remember token, or token hash.
5. `DELETE` permanently deletes records unless soft deletion is explicitly approved. Soft deletes are not required by the assessment.

## Prioritized phases

### Phase 0 - Establish a safe, reproducible baseline

**Priority:** P0. Complete before schema or endpoint work.

**Relevant files**

- `composer.json`, `composer.lock`
- `.env.example`, `phpunit.xml`, `config/database.php`
- `README.md`

**Work**

1. Confirm the target PHP version. Project instructions specify PHP 8.3+, while the available CLI reports 8.2.12; use PHP 8.3+ or explicitly align the project requirement before verification.
2. Configure MySQL as the documented application default and a distinct disposable MySQL testing database. Do not point PHPUnit at a developer database.
3. Keep real `.env` credentials untracked. Make `.env.example` a credential-free ScoopPOS template.
4. Replace stock README content with a short project overview, prerequisites, setup, migration/seed, test, and API-client instructions as later phases make them available.

**Acceptance criteria**

- PHP/Laravel/MySQL versions and local setup steps are accurate and reproducible.
- `APP_ENV=testing` resolves to a separate database, with no commented-out or accidental production/local test database configuration.
- No credentials are committed.

**Dependencies:** None.

**Verification commands**

```powershell
php -v
php artisan about
php artisan config:show database
php artisan test
```

Run the final command only after the isolated testing database is configured.

### Phase 1 - Define data model, roles, and ER diagram

**Priority:** P0.

**Relevant files**

- `database/migrations/*_add_role_to_users_table.php`
- `database/migrations/*_create_categories_table.php`, `*_create_items_table.php`
- `database/migrations/*_create_customers_table.php`
- `app/Models/User.php`, `app/Models/Item.php`, `app/Models/Customer.php`
- `database/factories/UserFactory.php`, `database/factories/ItemFactory.php`, `database/factories/CustomerFactory.php`
- `docs/er-diagram.md` (or equivalent tracked diagram source/render)

**Work**

1. Use the selected category/item/customer fields documented in `docs/database-design.md`.
2. Add a constrained, indexed role value to `users` with the exact Owner/Manager/Cashier values used consistently by policies, seeders, resources, and tests. Prefer a string plus application enum/value object if database-portable constraints are desired; avoid an untested MySQL-only enum decision.
3. Create item and customer migrations with appropriate nullability, numeric precision/checks, unique indexes, and timestamps based on the agreed fields. Use database constraints for actual invariants.
4. Add guarded Eloquent models, casts for money/quantity fields, and factories. Define relationships only when the schema genuinely has them; none is mandated between a customer and an item.
5. Add a clear ER diagram showing `users`, `personal_access_tokens` (Sanctum polymorphic relation), `items`, and `customers`, including key columns and constraints.

**Acceptance criteria**

- Fresh migration creates users with roles, Sanctum token storage, items, and customers in MySQL.
- The ER diagram matches the migrations exactly.
- Models do not expose unsafe mass assignment.

**Dependencies:** Phase 0 and agreed schema assumptions.

**Verification commands**

```powershell
php artisan migrate:fresh --seed --env=testing
php artisan migrate:status --env=testing
php artisan tinker --execute="App\\Models\\Item::query()->count(); App\\Models\\Customer::query()->count();"
```

### Phase 2 - Seed deterministic roles and build bearer-token authentication

**Priority:** P0.

**Relevant files**

- `app/Models/User.php`
- `database/seeders/DatabaseSeeder.php` and role-specific seeders if useful
- `app/Http/Controllers/Api/AuthController.php`
- `app/Http/Requests/Auth/LoginRequest.php`
- `app/Http/Resources/UserResource.php`
- `routes/api.php`
- `tests/Feature/Auth/*`

**Work**

1. Add Sanctum's `HasApiTokens` trait to `User`; retain password hashing and hide sensitive attributes.
2. Seed one documented, deterministic account for each role with hashed passwords. Never commit plaintext production credentials; development seed credentials are acceptable only when clearly documented.
3. Implement `POST /api/auth/login` with validated credentials, a generic invalid-credential response, and a Sanctum bearer token.
4. Implement authenticated `POST /api/auth/logout` to revoke the current access token. Define and test behavior when no current token exists.
5. Return consistent JSON resources/errors and use correct status codes (200 login/logout; 401 unauthenticated or invalid credentials; 422 malformed credentials).

**Acceptance criteria**

- A seeded Owner, Manager, and Cashier can log in and use an issued bearer token.
- Logout invalidates only the token used for that request.
- Invalid credentials do not disclose whether an email/account exists.
- Protected API endpoints return JSON 401 responses without a valid bearer token.

**Dependencies:** Phases 0-1.

**Verification commands**

```powershell
php artisan route:list --path=api/auth
php artisan test --filter=Auth
```

### Phase 3 - Implement and enforce authorization policies

**Priority:** P0. Do this before exposing CRUD routes.

**Relevant files**

- `app/Policies/ItemPolicy.php`, `app/Policies/CustomerPolicy.php`
- `app/Models/User.php`
- `app/Providers/AppServiceProvider.php` only if policy registration is needed
- `tests/Feature/Authorization/*`

**Work**

1. Implement policy abilities (`viewAny`, `view`, `create`, `update`, `delete`) that exactly reproduce the assessment matrix.
2. Authorize every controller action server-side, including collection reads and update routes. Do not rely on client UI, token naming, or route visibility for RBAC.
3. Ensure unauthenticated requests stop at authentication (401) and authenticated but unauthorized users receive 403.

**Acceptance criteria**

- Owner has all specified abilities.
- Manager is forbidden from both delete operations.
- Cashier is forbidden from item creation and deletion and from customer deletion, while retaining the specified read/update/create permissions.
- Each forbidden route returns 403, not 404 or a successful no-op.

**Dependencies:** Phase 2; Item and Customer model classes from Phase 1.

**Verification commands**

```powershell
php artisan test --filter=Authorization
php artisan test --filter=Policy
```

### Phase 4 - Deliver inventory and customer CRUD APIs

**Priority:** P0.

**Relevant files**

- `routes/api.php`
- `app/Http/Controllers/Api/ItemController.php`
- `app/Http/Controllers/Api/CustomerController.php`
- `app/Http/Requests/Item/StoreItemRequest.php`, `UpdateItemRequest.php`
- `app/Http/Requests/Customer/StoreCustomerRequest.php`, `UpdateCustomerRequest.php`
- `app/Http/Resources/ItemResource.php`, `CustomerResource.php`
- `tests/Feature/Items/*`, `tests/Feature/Customers/*`

**Work**

1. Register `auth:sanctum`-protected RESTful routes for the required endpoints:

   - `GET /api/items`, `GET /api/items/{item}`, `POST /api/items`, `PUT/PATCH /api/items/{item}`, `DELETE /api/items/{item}`
   - `GET /api/customers`, `GET /api/customers/{customer}`, `POST /api/customers`, `PUT/PATCH /api/customers/{customer}`, `DELETE /api/customers/{customer}`

2. Keep controllers thin: resolve models via route binding, call policy authorization, validate via Form Requests, persist guarded model attributes, and return API Resources.
3. Validate all client input based on the approved schema. Validate price/quantity ranges and unique customer values at both request and database levels where applicable.
4. Return appropriate REST status codes: 200 for retrieval/update, 201 with `Location` when created, 204 for deletion, 404 for missing records, and 422 for validation failures.
5. Make not-found and validation responses consistently JSON for API requests.

**Acceptance criteria**

- Every required route works for a role allowed by the matrix.
- Invalid input makes no partial database change.
- Missing model IDs return JSON 404.
- Item/customer resources contain only intended public fields.
- Every protected action is authenticated and policy-authorized.

**Dependencies:** Phases 1-3.

**Verification commands**

```powershell
php artisan route:list --path=api
php artisan test --filter=Items
php artisan test --filter=Customers
php artisan test --filter=Crud
```

### Phase 5 - Complete the required automated test coverage

**Priority:** P0. This is the completion gate for the backend.

**Relevant files**

- `tests/Feature/Auth/*`
- `tests/Feature/Authorization/*`
- `tests/Feature/Items/*`
- `tests/Feature/Customers/*`
- `tests/Feature/Api/*` where shared helpers improve clarity
- `tests/TestCase.php`, `phpunit.xml`

**Work**

1. Replace the stock example tests with focused feature tests using `RefreshDatabase` and factories.
2. Cover successful and failed login, bearer-token logout/revocation, all unauthenticated endpoints, the complete role matrix, inventory CRUD, customer CRUD, 422 responses, JSON 404 responses, and persisted database consistency after create/update/delete attempts.
3. Run all tests only against the separate test database configured in Phase 0.

**Acceptance criteria**

- Every mandatory assessment behavior has at least one direct automated assertion.
- Every resource/role combination and every disallowed action is explicitly tested.
- Test data is isolated and never mutates the local/development database.
- The entire test suite passes.

**Dependencies:** Phases 0-4.

**Verification commands**

```powershell
php artisan test
php artisan test --parallel
```

Use parallel execution only after the test database strategy supports separate parallel databases.

### Phase 6 - Add encouraged search and submission artifacts

**Priority:** P1 after all P0 tests pass.

**Relevant files**

- `app/Http/Controllers/Api/ItemController.php`
- `app/Http/Requests/Item/IndexItemRequest.php` if query validation is needed
- `tests/Feature/Items/ItemSearchTest.php`
- `postman/ScoopPOS.postman_collection.json`
- `postman/ScoopPOS.local.postman_environment.json` (no secret token committed)
- `README.md`, `docs/er-diagram.md`

**Work**

1. Add the encouraged name search as a validated query parameter, preserving ordinary `GET /api/items` behavior. Document case-sensitivity and partial-match behavior.
2. Create a Postman collection covering login, bearer-token capture/use, logout, both CRUD resources, unauthorized requests, and forbidden role actions. Include a safe environment template rather than a live token.
3. Finish README instructions for MySQL setup, migrations, seeding, tests, seed accounts, API base URL, Postman import, and ER diagram location.
4. Review `git status`, remote configuration, commit history, ignored credentials, and public-repository status with the repository owner. Publishing or changing repository visibility requires the owner's GitHub authorization.

**Acceptance criteria**

- `GET /api/items?search=<name>` returns only the documented matching records and has automated tests.
- A reviewer can run the API from README instructions and exercise it through Postman.
- ER diagram, README, `.env.example`, and source are all tracked and internally consistent.
- GitHub readiness is verified; actual publication/visibility is completed by the owner or with explicit authorization.

**Dependencies:** All P0 phases.

**Verification commands**

```powershell
php artisan test --filter=ItemSearch
php artisan test
php artisan route:list --path=api
git status --short
git log --oneline --decorate -10
```

## Deferred bonus phase

### Phase 7 - Optional C# WPF client

Start only after Phase 6 is complete. This phase is outside the mandatory backend assessment. It would add a .NET 8+ WPF project with login, inventory, and customer screens consuming the verified API and honoring server responses. It must have its own setup/run section in the README.

## Recommended implementation order

`Phase 0 -> Phase 1 -> Phase 2 -> Phase 3 -> Phase 4 -> Phase 5 -> Phase 6 -> optional Phase 7`

The highest-risk defects to avoid are an unisolated testing database, an incomplete policy matrix, token issuance without revocation, relying on front-end permissions, undocumented/invented schema fields, and claiming successful verification before tests actually run.
