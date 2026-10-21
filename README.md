IQT-FSD-2026

# IQT-FSD-2026 Task Board

A full stack task board built for the Full Stack Developer assessment. Laravel exposes a versioned REST API, Vue 3 renders the board, and MySQL stores the tasks. A weather panel calls the public Open-Meteo API through the backend.

Live demo: run it locally at http://127.0.0.1:8000 (no public host is attached to this project).

## Stack

- PHP 8.4, Laravel 13
- Vue 3, Vite, Axios
- MySQL 8
- PHPUnit for the API
- GitHub Actions for tests and the production asset build

## What you can do

- See a responsive list of tasks
- Add, edit, and delete a task
- Mark a task completed or reopen it
- Search, filter, sort, and page through the list
- Look up current weather and a four-day forecast for a city

## Setup instructions

Requirements: PHP 8.4+, Composer, Node.js 20+, and MySQL 8.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Create the database, then point `.env` at it. The example file already uses MySQL database `task_manager` and user `root`. Set `DB_PASSWORD` if your root user has a password. Tables are created on InnoDB so utf8mb4 indexes and transactions are available even when the server default engine is MyISAM.

```sql
CREATE DATABASE task_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Open http://127.0.0.1:8000

Windows PHP often has no certificate store configured. HTTPS calls, including Open-Meteo, are verified with Mozilla's CA bundle at `storage/certs/cacert.pem` when that file is present. Verification stays on.

For front-end hot reload, run `npm run dev` in a second terminal and keep `php artisan serve` running. If you use Apache, set the virtual host document root to the `public` directory so asset URLs resolve from the site root.

Tests use an in-memory SQLite database and do not touch MySQL:

```bash
php artisan test
```

### Docker (optional local container)

`docker compose up --build` starts PHP and MySQL. The app container runs migrations and the seeder, then serves http://127.0.0.1:8000. MySQL is published on host port 3307 with root password `root`. This container uses `artisan serve` so it is a local demo, not the production process described below.

## Architecture

```
Browser (Vue 3)
  → Axios client /api/v1
    → Form request (validation)
      → Controller
        → Service
          → Eloquent model
            → JSON resource
```

The controller stays thin. Validation lives in form requests. Completion time is set in `TaskService`, so a client cannot invent `completed_at`. List filters use an allow-list for sort columns. The weather call is a separate service with a timeout, a typed failure, and a 10 minute cache.

The API is stateless and versioned at `/api/v1`. It is rate limited to 60 requests per minute, and the weather route is limited to 30. Responses include `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, and `Permissions-Policy`.

## REST API

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/api/v1/tasks` | List tasks. Query: `search`, `status`, `priority`, `sort`, `direction`, `per_page`, `page` |
| POST | `/api/v1/tasks` | Create a task |
| GET | `/api/v1/tasks/{id}` | Show one task |
| PUT/PATCH | `/api/v1/tasks/{id}` | Update a task. Send `status` to complete or reopen it |
| DELETE | `/api/v1/tasks/{id}` | Delete a task |
| GET | `/api/v1/integrations/weather?city=London` | Current conditions and a four-day forecast |

Create body:

```json
{
  "title": "Ship the board",
  "description": "Optional notes",
  "priority": "high",
  "due_date": "2026-10-22"
}
```

`priority` is `low`, `medium`, or `high`. `status` is `pending` or `completed`. Validation errors return 422. Unknown tasks return 404.

## Database schema

Source of truth: `database/migrations/2026_10_21_120000_create_tasks_table.php`. A MySQL copy is in `docs/schema.sql`.

`tasks`

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint, primary key | |
| title | varchar(160) | Required |
| description | text, nullable | |
| priority | varchar(20), indexed | `low`, `medium`, `high`. Default `medium` |
| status | varchar(20), indexed | `pending`, `completed`. Default `pending` |
| due_date | date, nullable, indexed | |
| completed_at | timestamp, nullable | Set by the server when status becomes completed |
| created_at, updated_at | timestamps | |

Composite index: `(status, due_date)` for the open-tasks-by-date query.

Laravel's default migrations also create `users`, `password_reset_tokens`, `sessions`, `cache`, and `jobs`. This board does not authenticate users. Those tables support the framework session and cache drivers. Weather responses use the `cache` table.

## Step 3: API integration

**API used:** [Open-Meteo](https://open-meteo.com/) geocoding and forecast endpoints. No API key.

**Implementation:** The weather card calls `GET /api/v1/integrations/weather?city=`. `WeatherService` resolves the city with Open-Meteo's geocoding API, requests current conditions and four daily highs and lows, and maps WMO weather codes to labels. A successful response is cached for 10 minutes. The browser never calls Open-Meteo directly, so the timeout, the provider URL, and failure handling stay on the server. An unknown city returns 404. A provider failure returns 502 with a short message. The card shows temperature, condition, humidity, wind, the daily range, and an attribution link.

**Screenshot:** `docs/open-meteo-integration.png`

**Code:** `app/Services/WeatherService.php`, `app/Http/Controllers/Api/WeatherController.php`, `resources/js/components/WeatherPanel.vue`

## Step 4: Code review and architecture

### What steps would you take to secure a web application?

Treat every input as untrusted and keep secrets out of the browser and out of git. Authenticate with a proven tool such as Laravel Sanctum or a hosted identity provider. Hash passwords, regenerate the session on login, and keep tokens short-lived. Check authorization on the server for every action.

Use HTTPS only, with HSTS and secure cookies. Add security headers: nosniff, a frame policy, a strict content security policy, and a tight referrer policy. Keep CSRF protection on cookie-authenticated routes. Give API routes authentication, rate limits, and allow-listed fields so a client cannot mass-assign a protected column.

Use parameterized queries through the ORM. Return generic errors to users and put details in logs. Store secrets in the environment or a secret manager, and run production with debug off. Pin dependencies and scan them in CI. Encrypt backups, and give the database user only the privileges the app needs. If the app accepts files, check the type, store them outside the web root, and serve them from another origin. Log sensitive changes and review them the same way features are reviewed.

### How would you improve the performance of a slow React application?

Measure first. The React Profiler, the browser performance panel, and the network waterfall show whether time goes to rendering, fetching, or a long task.

For rendering, move state out of components that do not need it, split context so one change does not redraw the tree, and memoize only what the profiler shows is expensive. Give lists stable keys and window long ones. Load routes and heavy libraries with code splitting. Do not copy large objects into state on each keystroke.

For data, remove request waterfalls: fetch in parallel, cache with a query library, and paginate or filter on the server. Give images dimensions, a modern format, and lazy loading. Inspect the bundle and drop duplicate libraries and unused locales. Defer third-party scripts.

Check referential bugs that defeat memoization. Compare each change on a production build. Development mode is slower and can point at the wrong problem.

### Explain the difference between SQL and NoSQL databases and when you would use each.

SQL databases such as MySQL and PostgreSQL store rows in tables with a schema, relations, and transactions. They fit records that reference each other and must stay consistent: tasks and users, orders and payments. You get constraints, joins, and reliable updates. The cost is deliberate schema changes, queries, and indexes.

NoSQL is a group of models. Document stores such as MongoDB keep flexible documents and suit data whose shape changes often. Key-value stores suit caches and sessions. Wide-column and graph stores suit their own access patterns. They often scale out more easily, and many of them trade immediate consistency for availability during a partition.

I would use MySQL or PostgreSQL for this task board and for most business data, because the records are relational and a transaction matters. I would add a document or key-value store for high-volume events, caching, or content with no stable schema. Many systems use both: SQL as the source of truth, and Redis or a document store beside it for speed.

### How would you deploy a full-stack application to AWS?

Split the app the way it already runs: a PHP API, a built Vue bundle, and MySQL.

`npm run build` produces static files. For this project I would let Laravel serve them from `public` so the browser has one origin. A separate S3 and CloudFront origin is the alternative when the client is deployed on its own.

Run Laravel on Elastic Beanstalk, ECS Fargate, or a small EC2 group behind an Application Load Balancer. The process is php-fpm and nginx, not `artisan serve`. Read the app key and other secrets from Secrets Manager or Parameter Store. Set `APP_ENV=production` and `APP_DEBUG=false`.

Put MySQL on RDS in private subnets, with backups and a security group that accepts traffic only from the app. Run migrations once, as a release step, before traffic moves.

Terminate TLS with an ACM certificate and redirect HTTP to HTTPS. Ship logs to CloudWatch. CI should test, build, then deploy. Point the load balancer health check at `/up`. Start with a small instance and scale when the metrics say so. Azure and Google Cloud map to the same shape: managed MySQL, a container or app service, storage or the app host for assets, and a load balancer with TLS.

## Project layout

```
app/Http/Controllers/Api   REST controllers
app/Http/Requests          Input validation
app/Http/Resources         Response shape
app/Services               Task rules and the Open-Meteo client
resources/js               Vue 3 interface
database/migrations        Schema
tests/Feature              API tests
```

## Submission checklist

- GitHub repository: push this project and use that URL
- Live demo: http://127.0.0.1:8000 after the setup above
- Setup instructions, schema, API integration, and architecture answers: this file
- Screenshot: `docs/open-meteo-integration.png`
