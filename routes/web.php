<?php

use App\Http\Controllers\Admin\AuditController as AdminAuditController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DepositController as AdminDepositController;
use App\Http\Controllers\Admin\InvestmentController as AdminInvestmentController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\ReferralController as AdminReferralController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\TransactionController as AdminTransactionController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\WithdrawalController as AdminWithdrawalController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WithdrawalController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::view('/mentions-legales', 'legal')->name('legal');
Route::view('/a-propos', 'pages.about')->name('about');
Route::view('/faq', 'pages.faq')->name('faq');
Route::view('/contact', 'pages.contact')->name('contact');
Route::view('/conditions', 'pages.terms')->name('terms');
Route::view('/confidentialite', 'pages.privacy')->name('privacy');
Route::get('/projets', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projets/{project:slug}', [ProjectController::class, 'show'])->name('projects.show');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:register');
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'active', 'password.fresh'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/projets/{project:slug}/apercu', [InvestmentController::class, 'preview'])->name('investments.preview');
    Route::post('/projets/{project:slug}/investir', [InvestmentController::class, 'store'])->name('investments.store');
    Route::get('/portefeuille', [InvestmentController::class, 'index'])->name('investments.index');
    Route::get('/portefeuille/{investment}', [InvestmentController::class, 'show'])->name('investments.show');
    Route::get('/deposer', [DepositController::class, 'create'])->name('deposits.create');
    Route::post('/deposer', [DepositController::class, 'store'])->name('deposits.store');
    Route::get('/retirer', [WithdrawalController::class, 'create'])->name('withdrawals.create');
    Route::post('/retirer', [WithdrawalController::class, 'store'])->name('withdrawals.store');
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/parrainage', ReferralController::class)->name('referral');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/lire', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/lire-tout', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/mot-de-passe', [ProfileController::class, 'password'])->name('profile.password');
    Route::get('/kyc', [KycController::class, 'edit'])->name('kyc.edit');
    Route::post('/kyc', [KycController::class, 'store'])->name('kyc.store');
});

Route::middleware(['auth', 'active', 'password.fresh', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('/utilisateurs', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/utilisateurs/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::post('/utilisateurs/{user}/bloquer', [AdminUserController::class, 'block'])->name('users.block');
    Route::post('/utilisateurs/{user}/debloquer', [AdminUserController::class, 'unblock'])->name('users.unblock');
    Route::post('/utilisateurs/{user}/bonus', [AdminUserController::class, 'bonus'])->name('users.bonus');
    Route::post('/utilisateurs/{user}/ajustement', [AdminUserController::class, 'adjust'])->name('users.adjust');
    Route::post('/utilisateurs/{user}/mot-de-passe', [AdminUserController::class, 'password'])->name('users.password');
    Route::post('/utilisateurs/{user}/kyc', [AdminUserController::class, 'kyc'])->name('users.kyc');

    Route::get('/projets', [AdminProjectController::class, 'index'])->name('projects.index');
    Route::get('/projets/nouveau', [AdminProjectController::class, 'create'])->name('projects.create');
    Route::post('/projets', [AdminProjectController::class, 'store'])->name('projects.store');
    Route::get('/projets/{project}', [AdminProjectController::class, 'show'])->name('projects.show');
    Route::get('/projets/{project}/modifier', [AdminProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projets/{project}', [AdminProjectController::class, 'update'])->name('projects.update');
    Route::post('/projets/{project}/distribuer', [AdminProjectController::class, 'distribute'])->name('projects.distribute');
    Route::post('/projets/{project}/cloturer', [AdminProjectController::class, 'close'])->name('projects.close');
    Route::delete('/projets/{project}', [AdminProjectController::class, 'destroy'])->name('projects.destroy');

    Route::get('/investissements', [AdminInvestmentController::class, 'index'])->name('investments.index');
    Route::post('/investissements/{investment}', [AdminInvestmentController::class, 'update'])->name('investments.update');

    Route::get('/depots', [AdminDepositController::class, 'index'])->name('deposits.index');
    Route::get('/depots/{deposit}', [AdminDepositController::class, 'show'])->name('deposits.show');
    Route::post('/depots/{deposit}/approuver', [AdminDepositController::class, 'approve'])->name('deposits.approve');
    Route::post('/depots/{deposit}/refuser', [AdminDepositController::class, 'reject'])->name('deposits.reject');
    Route::get('/depots/{deposit}/preuve', [AdminDepositController::class, 'proof'])->name('deposits.proof');

    Route::get('/retraits', [AdminWithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::get('/retraits/{withdrawal}', [AdminWithdrawalController::class, 'show'])->name('withdrawals.show');
    Route::post('/retraits/{withdrawal}/approuver', [AdminWithdrawalController::class, 'approve'])->name('withdrawals.approve');
    Route::post('/retraits/{withdrawal}/refuser', [AdminWithdrawalController::class, 'reject'])->name('withdrawals.reject');

    Route::get('/parrainage', [AdminReferralController::class, 'index'])->name('referrals.index');
    Route::get('/transactions', [AdminTransactionController::class, 'index'])->name('transactions.index');
    Route::get('/notifications', [AdminNotificationController::class, 'create'])->name('notifications.create');
    Route::post('/notifications', [AdminNotificationController::class, 'store'])->name('notifications.store');
    Route::get('/audit', [AdminAuditController::class, 'index'])->name('audit.index');
    Route::get('/parametres', [AdminSettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/parametres', [AdminSettingsController::class, 'update'])->name('settings.update');
});
