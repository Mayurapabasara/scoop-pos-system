# ScoopPOS Submission Checklist

## Completed repository artifacts

- [x] Laravel 12 REST API with Sanctum bearer authentication
- [x] Seeded Owner, Manager, and Cashier development users
- [x] Backend policy enforcement for the assessment permission matrix
- [x] Inventory CRUD, category relationship, and item-name search
- [x] Customer CRUD and duplicate-email handling
- [x] Feature tests, Pint formatting check, and CI workflow
- [x] Postman collection and token-free environment template
- [x] Database design and Mermaid ER diagram
- [x] README, `.env.example`, and [QA evidence](../TEST_REPORT.md)

## Before submitting

- [ ] Run `composer install`, configure a local MySQL 8 database, then run `php artisan migrate --seed`.
- [ ] Run `vendor/bin/pint --test` and `php artisan test` on the final commit.
- [ ] Import the Postman collection, log in, and execute the authenticated examples against the seeded API.
- [ ] Verify no `.env`, generated token, database dump, or other secret is staged: `git status --short`.
- [ ] Verify the GitHub Actions workflow is green after pushing.
- [ ] Confirm the repository URL, visibility, and submission instructions with the assessment owner before publishing.
