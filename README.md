# BrandBondhu

AI-powered branding and marketing platform for small businesses and Facebook sellers in Bangladesh.
CSE470 Software Engineering project, built with **Laravel 13 (PHP) using the MVC architecture**.

**New to the project? Read the step-by-step guide: [docs/How-to-Run-BrandBondhu-on-Localhost.pdf](docs/How-to-Run-BrandBondhu-on-Localhost.pdf)**

## Features implemented (1 to 10)

| # | Feature | What works |
| --- | --- | --- |
| 1 | Authentication and account management | Register with email OTP, login (web session) and JWT login for the API (`POST /api/login`, `GET /api/me`), forgot/reset password with a 6-digit code, change password, deactivate (log in again to reactivate) and delete account |
| 2 | Role-based access control | Seller, Collaborator, Designer and Admin roles. `role:` and `permission:` middleware on every route, `@can()` in views, admin users page with role changes and a permission matrix |
| 3 | Profile management | Shop setup for sellers (with logo upload) and portfolio setup for designers (skills, prices, work items, public profile page) |
| 4 | Team and collaborators | Sellers invite staff by link, choose per-feature permissions, edit and remove them. Audit log of every workspace action with filters |
| 5 | Subscriptions and AI credits | Free, Basic, Premium and Enterprise plans, plan and kit limits, credits meter in the sidebar, credit ledger, low-credit and out-of-credit warnings |
| 6 | Payments | Simulated bKash, Nagad and card checkout with OTP and PIN (Luhn check for cards, masked numbers only), transaction history, printable invoice and PDF download |
| 7 | Notifications | In-app notification center, bell that polls every 20 seconds with pop-up toasts, mark one or all as read |
| 8 | Brand identity kit generator | AI brand kit (logo, palette, fonts, tagline, voice, mission), regenerate any single asset, set the active kit, edit and delete |
| 9 | Brand style guide | Auto-generated guide from a kit, four layout presets, customisable rules and sections, PDF export |
| 10 | Content manager | AI captions and hashtags (English and Bangla), create/edit/delete posts, scheduling, reminders (bell polling and `php artisan posts:remind` every minute) |

AI features use `app/Services/Ai/AiService.php`. With `AI_DRIVER=local` (default) a built-in offline generator is used, so the app works with no internet or API key. With `AI_DRIVER=http` it calls the FastAPI AI service at `AI_SERVICE_URL` and falls back to the local generator if that service is down.

## MVC structure

| Layer | Location | Contents |
| --- | --- | --- |
| Model | `app/Models` | `User`, `Shop`, `DesignerProfile`, `PortfolioItem`, `TeamMember`, `AuditLog`, `Plan`, `Subscription`, `CreditTransaction`, `Payment`, `BrandKit`, `StyleGuide`, `Post` |
| View | `resources/views` | Blade pages for auth, dashboard, account, profiles, team, billing, brand kits, style guides, posts, notifications, admin, and PDF templates |
| Controller | `app/Http/Controllers` | One controller per feature (for example `BrandKitController`, `CheckoutController`, `PostController`), plus `Api/AuthController` for JWT |
| Services | `app/Services` | `AiService` + `LocalAiEngine`, `CreditService`, `SubscriptionService`, `PaymentService`, `OtpService`, `JwtService`, `PdfService`, `PostReminderService`, `AuditLogger` |
| Middleware | `app/Http/Middleware` | `RoleMiddleware`, `PermissionMiddleware`, `EnsureAccountVerified`, `JwtAuthenticate` |
| Routes | `routes/web.php`, `routes/api.php` | Grouped by feature and protected by role/permission middleware |
| Database | `database/migrations`, `database/seeders` | All tables, the four plans, and demo data for every feature |

Request flow: Browser -> Route -> Middleware (auth, role, permission) -> Controller -> Service / Model (MySQL) -> Blade View -> HTML response.

## Run locally (short version)

Requirements: **PHP 8.4+** and Composer (Laravel Herd or php.new), and MySQL (XAMPP) or SQLite.
XAMPP's own PHP 8.2 is too old for Laravel 13, so use XAMPP only for MySQL.

```bash
composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
# start MySQL in XAMPP, then (type "yes" if it offers to create the database):
php artisan migrate --seed
php artisan serve
```

Open http://127.0.0.1:8000 (redirects to the login page).

- Email is written to `storage/logs/laravel.log`. With `SHOW_DEMO_CODES=true` (default) OTP codes are also shown on screen.
- Optional: `php artisan schedule:work` sends post reminders on time in the background.
- Tests: `php artisan test` (28 feature tests).

## Demo accounts (password: `password123`)

| Role | Email |
| --- | --- |
| Seller | seller@brandbondhu.test |
| Collaborator | collaborator@brandbondhu.test |
| Designer | designer@brandbondhu.test |
| Admin | admin@brandbondhu.test |

The seed also creates a shop, a paid Basic plan, an active brand kit with a style guide, scheduled and draft posts, a designer portfolio and some notifications.
