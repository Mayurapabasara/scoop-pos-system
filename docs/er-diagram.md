# ScoopPOS ER Diagram

This diagram reflects the current Laravel migrations. `personal_access_tokens` is Laravel Sanctum's polymorphic token table; a token belongs to a user in this application.

```mermaid
erDiagram
    USERS {
        bigint id PK
        string name
        string email UK
        string role "owner | manager | cashier"
        string password
        timestamp email_verified_at
        timestamp created_at
        timestamp updated_at
    }

    CATEGORIES {
        bigint id PK
        string name UK
        text description "nullable"
        timestamp created_at
        timestamp updated_at
    }

    ITEMS {
        bigint id PK
        bigint category_id FK
        string name
        text description "nullable"
        decimal unit_price "10,2"
        unsigned_int stock_quantity
        timestamp created_at
        timestamp updated_at
    }

    CUSTOMERS {
        bigint id PK
        string name
        string email UK "nullable"
        string phone "nullable, max 30"
        timestamp created_at
        timestamp updated_at
    }

    PERSONAL_ACCESS_TOKENS {
        bigint id PK
        string tokenable_type
        bigint tokenable_id
        string name
        string token UK
        timestamp last_used_at
        timestamp expires_at
        timestamp created_at
        timestamp updated_at
    }

    CATEGORIES ||--o{ ITEMS : contains
    USERS ||--o{ PERSONAL_ACCESS_TOKENS : tokenable
```

`items.category_id` uses `ON DELETE RESTRICT`, so a category with inventory cannot be deleted. Customers intentionally have no sales/order relation because those entities are outside the assessment scope.
