<?php

use App\Http\Controllers\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\SystemEventController;
use App\Http\Controllers\Admin\TemplateController as AdminTemplateController;
use App\Http\Controllers\Admin\TransactionController as AdminTransactionController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CoverLetterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\JobOfferController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\ResumeExportController;
use App\Http\Controllers\ResumeImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicSiteController::class, 'home'])->name('home');
Route::get('/fonctionnalites', fn () => redirect()->route('home').'#fonctionnalites')->name('features');
Route::get('/modeles', [PublicSiteController::class, 'templates'])->name('templates');
Route::get('/modeles/{slug}/apercu', [PublicSiteController::class, 'templatePreview'])->name('templates.preview');
Route::get('/tarifs', [PublicSiteController::class, 'pricing'])->name('pricing');
Route::get('/ressources', fn () => view('public.pages.ressources'))->name('resources');
Route::view('/confidentialite', 'public.pages.confidentialite')->name('privacy');
Route::view('/conditions', 'public.pages.conditions')->name('terms');
Route::view('/mentions-legales', 'public.pages.mentions')->name('legal');
Route::get('/contact', [ContactController::class, 'create'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:6,1')->name('contact.store');

Route::post('/webhooks/paiement/{provider}', PaymentWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('payments.webhook');

Route::middleware(['auth', 'active', 'verified.when'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/cv/importer', [ResumeImportController::class, 'create'])->name('resumes.import');
    Route::post('/cv/importer', [ResumeImportController::class, 'store'])->name('resumes.import.store');
    Route::get('/cv/importer/{import}', [ResumeImportController::class, 'review'])->name('resumes.import.review');
    Route::post('/cv/importer/{import}', [ResumeImportController::class, 'confirm'])->name('resumes.import.confirm');

    Route::get('/cv', [ResumeController::class, 'index'])->name('resumes.index');
    Route::post('/cv', [ResumeController::class, 'store'])->name('resumes.store');
    Route::get('/cv/{resume}/modifier', [ResumeController::class, 'edit'])->name('resumes.edit');
    Route::put('/cv/{resume}', [ResumeController::class, 'update'])->name('resumes.update');
    Route::patch('/cv/{resume}', [ResumeController::class, 'autosave'])->name('resumes.autosave');
    Route::delete('/cv/{resume}', [ResumeController::class, 'destroy'])->name('resumes.destroy');
    Route::post('/cv/{resume}/dupliquer', [ResumeController::class, 'duplicate'])->name('resumes.duplicate');
    Route::get('/cv/{resume}/apercu', [ResumeController::class, 'preview'])->name('resumes.preview');
    Route::get('/cv/{resume}/versions', [ResumeController::class, 'versions'])->name('resumes.versions');
    Route::post('/cv/{resume}/versions/{version}/restaurer', [ResumeController::class, 'restore'])->name('resumes.restore');
    Route::get('/cv/{resume}/export/pdf', [ResumeExportController::class, 'pdf'])->name('resumes.export.pdf');
    Route::get('/cv/{resume}/export/docx', [ResumeExportController::class, 'docx'])->name('resumes.export.docx');

    Route::get('/lettres', [CoverLetterController::class, 'index'])->name('letters.index');
    Route::get('/lettres/creer', [CoverLetterController::class, 'create'])->name('letters.create');
    Route::post('/lettres', [CoverLetterController::class, 'store'])->name('letters.store');
    Route::get('/lettres/{letter}/modifier', [CoverLetterController::class, 'edit'])->name('letters.edit');
    Route::put('/lettres/{letter}', [CoverLetterController::class, 'update'])->name('letters.update');
    Route::delete('/lettres/{letter}', [CoverLetterController::class, 'destroy'])->name('letters.destroy');
    Route::get('/lettres/{letter}/apercu', [CoverLetterController::class, 'preview'])->name('letters.preview');
    Route::get('/lettres/{letter}/export/pdf', [CoverLetterController::class, 'pdf'])->name('letters.export.pdf');
    Route::get('/lettres/{letter}/export/docx', [CoverLetterController::class, 'docx'])->name('letters.export.docx');

    Route::get('/assistant', [AiAssistantController::class, 'index'])->name('assistant.index');
    Route::post('/assistant/cv/{resume}/resume', [AiAssistantController::class, 'summary'])->middleware('throttle:ai')->name('assistant.summary');
    Route::post('/assistant/cv/{resume}/experience', [AiAssistantController::class, 'experience'])->middleware('throttle:ai')->name('assistant.experience');
    Route::post('/assistant/cv/{resume}/competences', [AiAssistantController::class, 'skills'])->middleware('throttle:ai')->name('assistant.skills');
    Route::post('/assistant/grammaire', [AiAssistantController::class, 'grammar'])->middleware('throttle:ai')->name('assistant.grammar');
    Route::post('/assistant/ton', [AiAssistantController::class, 'tone'])->middleware('throttle:ai')->name('assistant.tone');
    Route::post('/assistant/lettre', [AiAssistantController::class, 'letter'])->middleware('throttle:ai')->name('assistant.letter');
    Route::post('/assistant/analyse', [AiAssistantController::class, 'analyze'])->middleware('throttle:ai')->name('assistant.analyze');
    Route::post('/assistant/cv/{resume}/adapter', [AiAssistantController::class, 'adapt'])->middleware('throttle:ai')->name('assistant.adapt');

    Route::get('/offres', [JobOfferController::class, 'index'])->name('offers.index');
    Route::post('/offres', [JobOfferController::class, 'store'])->name('offers.store');
    Route::delete('/offres/{offer}', [JobOfferController::class, 'destroy'])->name('offers.destroy');

    Route::get('/candidatures', [JobApplicationController::class, 'index'])->name('applications.index');
    Route::post('/candidatures', [JobApplicationController::class, 'store'])->name('applications.store');
    Route::put('/candidatures/{application}', [JobApplicationController::class, 'update'])->name('applications.update');
    Route::delete('/candidatures/{application}', [JobApplicationController::class, 'destroy'])->name('applications.destroy');

    Route::get('/abonnement', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/abonnement/{plan}', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('/abonnement/paiement/{transaction}', [BillingController::class, 'payment'])->name('billing.payment');
    Route::post('/abonnement/paiement/{transaction}/bac-a-sable', [BillingController::class, 'sandbox'])->name('billing.sandbox');

    Route::get('/parametres', [PreferenceController::class, 'edit'])->name('settings.edit');
    Route::put('/parametres', [PreferenceController::class, 'update'])->name('settings.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'active', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('/utilisateurs', [AdminUserController::class, 'index'])->name('users.index');
    Route::put('/utilisateurs/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::get('/offres', [AdminPlanController::class, 'index'])->name('plans.index');
    Route::put('/offres/{plan}', [AdminPlanController::class, 'update'])->name('plans.update');
    Route::get('/modeles', [AdminTemplateController::class, 'index'])->name('templates.index');
    Route::put('/modeles/{template}', [AdminTemplateController::class, 'update'])->name('templates.update');
    Route::get('/transactions', [AdminTransactionController::class, 'index'])->name('transactions.index');
    Route::get('/reglages', [AdminSettingController::class, 'edit'])->name('settings.edit');
    Route::put('/reglages', [AdminSettingController::class, 'update'])->name('settings.update');
    Route::get('/journaux', [SystemEventController::class, 'index'])->name('events.index');
    Route::get('/messages', [AdminContactMessageController::class, 'index'])->name('messages.index');
});

require __DIR__.'/auth.php';
