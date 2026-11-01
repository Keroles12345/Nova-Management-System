# NOVA Commerce Suite

A Laravel application for user authentication and roles, team task management, and e-commerce analytics in a responsive dashboard.

## Requirements and setup

- PHP 8.3 or later, Composer, and a database supported by Laravel.
- Configure `.env` and the database connection, then run:

```powershell
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

The Blade and CSS interface works without Vite. Demo data is seeded only in `local` and `testing` environments.

Local seed accounts:

| Role | Email | Password |
|---|---|---|
| Admin | `admin@example.test` | `password` |
| Manager | `manager@example.test` | `password` |
| User | `user@example.test` | `password` |

Change demo passwords and never use them in production. Public registration always creates a `user` account; only administrators can assign roles.

## Authentication and authorization

Passwords are stored using Laravel's `hashed` cast. The web interface uses Laravel sessions, while the API issues HS256-signed JWTs. Tokens validate the signature, issuer, expiration, and user token version. Signing out increments the token version and revokes existing tokens for that user.

For production, set `JWT_SECRET` to a strong random secret distinct from `APP_KEY`. Configure `JWT_TTL` in seconds (default: 3600) and set `JWT_ISSUER` to the service URL. Do not commit secrets.

Roles are `admin`, `manager`, and `user`:

- Administrators manage users, change roles, and delete accounts. They cannot change or delete their own account from the management screen.
- Managers and administrators can access store analytics and manage team tasks.
- Users can access their own tasks only; they cannot view or modify another user's tasks.

## API

All endpoints are under `/api` and accept JSON:

| Method | Endpoint | Access |
|---|---|---|
| `POST` | `/api/auth/register` | Public |
| `POST` | `/api/auth/login` | Public |
| `GET` | `/api/me` | JWT |
| `POST` | `/api/auth/logout` | JWT |
| `GET`, `POST` | `/api/tasks` | JWT |
| `GET`, `PUT`, `PATCH`, `DELETE` | `/api/tasks/{id}` | JWT; task ownership is enforced |
| `GET` | `/api/users` | Admin |
| `PATCH` | `/api/users/{id}/role` | Admin |
| `DELETE` | `/api/users/{id}` | Admin |

Send tokens using `Authorization: Bearer <token>`. Registration and login return an `access_token`, its lifetime, and the user profile. Task creation accepts `title`, optional `description`, and optional `due_date`. Valid task statuses are `pending`, `in_progress`, and `completed`.

## Dashboard and tasks

The manager and administrator dashboard includes completed sales, order and customer counts, completed and cancelled order totals, a daily sales chart, top-selling products, and recent orders. Orders can be filtered by date range and status. The task workspace supports creating, editing, and deleting tasks, deadlines, status updates, and user-specific task access.

## Tests

```powershell
php artisan test
```
