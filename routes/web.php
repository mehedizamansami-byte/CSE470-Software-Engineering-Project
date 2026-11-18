<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BrandKitController;
use App\Http\Controllers\CaptionController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\StyleGuideController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

// ---------------------------------------------------------------------
// Feature 1: guests (register, login, forgot password, team invitations)
// ---------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:10,1');

    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'send'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:10,1')->name('password.update');

    Route::get('/invitations/{token}', [InvitationController::class, 'show'])->name('invitations.show');
    Route::post('/invitations/{token}', [InvitationController::class, 'accept'])->name('invitations.accept');
});

// ---------------------------------------------------------------------
// Logged in, OTP not verified yet
// ---------------------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/verify', [OtpController::class, 'show'])->name('otp.show');
    Route::post('/verify', [OtpController::class, 'verify'])->middleware('throttle:10,1')->name('otp.verify');
    Route::post('/verify/resend', [OtpController::class, 'resend'])->middleware('throttle:3,1')->name('otp.resend');
});

// ---------------------------------------------------------------------
// Logged in and verified
// ---------------------------------------------------------------------
Route::middleware(['auth', 'verified.account'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Feature 1: account management
    Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('/account', [AccountController::class, 'update'])->name('account.update');
    Route::put('/account/password', [AccountController::class, 'password'])->name('account.password');
    Route::post('/account/deactivate', [AccountController::class, 'deactivate'])->name('account.deactivate');
    Route::delete('/account', [AccountController::class, 'destroy'])->name('account.destroy');

    // Feature 7: notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/feed', [NotificationController::class, 'feed'])->name('notifications.feed');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/notifications/{id}/open', [NotificationController::class, 'read'])->name('notifications.open');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');

    // Feature 3: public designer profile (any logged-in user)
    Route::get('/designers/{designer}', [PortfolioController::class, 'show'])->name('designers.show');

    // Feature 3: shop profile (sellers, or collaborators with "shop" permission)
    Route::middleware('permission:shop')->group(function () {
        Route::get('/shop', [ShopController::class, 'edit'])->name('shop.edit');
        Route::put('/shop', [ShopController::class, 'update'])->name('shop.update');
    });

    // Feature 3: designer portfolio
    Route::middleware('permission:portfolio')->group(function () {
        Route::get('/portfolio', [PortfolioController::class, 'edit'])->name('portfolio.edit');
        Route::put('/portfolio', [PortfolioController::class, 'update'])->name('portfolio.update');
        Route::post('/portfolio/items', [PortfolioController::class, 'storeItem'])->name('portfolio.items.store');
        Route::delete('/portfolio/items/{item}', [PortfolioController::class, 'destroyItem'])->name('portfolio.items.destroy');
    });

    // Feature 4: team, collaborators and audit log (seller only)
    Route::middleware('permission:team')->group(function () {
        Route::get('/team', [TeamController::class, 'index'])->name('team.index');
        Route::post('/team', [TeamController::class, 'invite'])->name('team.invite');
        Route::put('/team/{member}', [TeamController::class, 'update'])->name('team.update');
        Route::delete('/team/{member}', [TeamController::class, 'destroy'])->name('team.destroy');
        Route::get('/team/audit-log', [TeamController::class, 'auditLog'])->name('team.audit');
    });

    // Feature 5 + 6: plans, credits, checkout and invoices (seller only)
    Route::middleware('permission:billing')->group(function () {
        Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
        Route::post('/billing/plans/{plan}', [BillingController::class, 'subscribe'])->name('billing.subscribe');
        Route::post('/billing/credits', [BillingController::class, 'buyCredits'])->name('billing.credits');
        Route::get('/billing/transactions', [BillingController::class, 'transactions'])->name('billing.transactions');

        Route::get('/checkout/{payment}', [CheckoutController::class, 'show'])->name('checkout.show');
        Route::post('/checkout/{payment}', [CheckoutController::class, 'start'])->name('checkout.start');
        Route::get('/checkout/{payment}/verify', [CheckoutController::class, 'verifyForm'])->name('checkout.verify');
        Route::post('/checkout/{payment}/verify', [CheckoutController::class, 'verify'])->middleware('throttle:10,1')->name('checkout.confirm');
        Route::post('/checkout/{payment}/cancel', [CheckoutController::class, 'cancel'])->name('checkout.cancel');

        Route::get('/invoices/{payment}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/invoices/{payment}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    });

    // Feature 8: brand identity kits
    Route::middleware('permission:brand_kit')->group(function () {
        Route::get('/brand-kits', [BrandKitController::class, 'index'])->name('brand-kits.index');
        Route::get('/brand-kits/create', [BrandKitController::class, 'create'])->name('brand-kits.create');
        Route::post('/brand-kits', [BrandKitController::class, 'store'])->middleware('throttle:20,1')->name('brand-kits.store');
        Route::get('/brand-kits/{kit}', [BrandKitController::class, 'show'])->name('brand-kits.show');
        Route::get('/brand-kits/{kit}/edit', [BrandKitController::class, 'edit'])->name('brand-kits.edit');
        Route::put('/brand-kits/{kit}', [BrandKitController::class, 'update'])->name('brand-kits.update');
        Route::delete('/brand-kits/{kit}', [BrandKitController::class, 'destroy'])->name('brand-kits.destroy');
        Route::post('/brand-kits/{kit}/regenerate', [BrandKitController::class, 'regenerate'])->middleware('throttle:30,1')->name('brand-kits.regenerate');
        Route::post('/brand-kits/{kit}/activate', [BrandKitController::class, 'activate'])->name('brand-kits.activate');
    });

    // Feature 9: brand style guide
    Route::middleware('permission:style_guide')->group(function () {
        Route::get('/style-guides', [StyleGuideController::class, 'index'])->name('style-guides.index');
        Route::post('/brand-kits/{kit}/style-guide', [StyleGuideController::class, 'generate'])->middleware('throttle:20,1')->name('style-guides.generate');
        Route::get('/brand-kits/{kit}/style-guide', [StyleGuideController::class, 'show'])->name('style-guides.show');
        Route::put('/brand-kits/{kit}/style-guide', [StyleGuideController::class, 'update'])->name('style-guides.update');
        Route::get('/brand-kits/{kit}/style-guide/pdf', [StyleGuideController::class, 'pdf'])->name('style-guides.pdf');
    });

    // Feature 10: content manager
    Route::middleware('permission:content')->group(function () {
        Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
        Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
        Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
        Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
        Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
        Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
        Route::post('/posts/{post}/publish', [PostController::class, 'publish'])->name('posts.publish');
        Route::post('/captions', [CaptionController::class, 'generate'])->middleware('throttle:30,1')->name('captions.generate');
    });

    // Feature 2: admin only
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
        Route::put('/users/{user}/role', [AdminController::class, 'updateRole'])->name('admin.users.role');
        Route::get('/roles', [AdminController::class, 'roles'])->name('admin.roles');
    });
});
