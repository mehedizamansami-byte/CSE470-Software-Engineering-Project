# BrandBondhu

AI-powered branding and marketing platform for small businesses and Facebook sellers in Bangladesh.
CSE470 Software Engineering project, built with **Laravel (PHP) using the MVC architecture**.

## MVC structure

| Layer | Location | Contents so far |
| --- | --- | --- |
| Model | `app/Models` | `User` (with role and active flag) |
| View | `resources/views` | `layouts/app`, `auth/login`, `auth/register`, `dashboard/index`, `admin/users` (Blade) |
| Controller | `app/Http/Controllers` | `Auth/LoginController`, `Auth/RegisterController`, `DashboardController`, `AdminController` |
| Routes | `routes/web.php` | login, register, logout, dashboard, admin users |
| Services | `app/Services` | reserved for reusable logic such as AI calls |
| Middleware | `app/Http/Middleware` | `RoleMiddleware` (`role:admin`) |
| Database | `database/migrations`, `database/seeders` | users table with `role`, demo seeder |

Request flow: Browser -> Route -> Controller -> Model (MySQL) / Service -> Blade View -> HTML response.

## Run locally

Requirements: PHP 8.3+, Composer, MySQL (XAMPP works).

```bash
composer install
cp .env.example .env
php artisan key:generate
# create an empty MySQL database named brandbondhu, then:
php artisan migrate --seed
php artisan serve
```

Open http://127.0.0.1:8000 (redirects to the login page).

## Demo accounts (password: `password123`)

| Role | Email |
| --- | --- |
| Seller | seller@brandbondhu.test |
| Collaborator | collaborator@brandbondhu.test |
| Designer | designer@brandbondhu.test |
| Admin | admin@brandbondhu.test |

Only the admin can open `/admin/users`; other roles get a 403 page.
