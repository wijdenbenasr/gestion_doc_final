<?php

use App\Http\Controllers\Admin\AdminCodificationController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\UserApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentWorkflowController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

// ─── Routes publiques (invités) ───────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');

    Route::get('/email/verify', [AuthController::class, 'showEmailVerification'])->name('auth.verify.show');
    Route::post('/email/verify', [AuthController::class, 'verifyEmailCode'])->name('auth.verify.submit');
    Route::post('/email/verify/resend', [AuthController::class, 'resendVerificationCode'])->name('auth.verify.resend');

    Route::get('/password/forgot', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/password/email', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/password/reset/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('password.update');
});

// ─── Routes authentifiées ─────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/account/profile', [AccountController::class, 'show'])->name('account.profile.show');
    Route::post('/account/profile/image', [AccountController::class, 'updateProfileImage'])->name('account.profile.image.update');
    Route::delete('/account/profile/image', [AccountController::class, 'destroyProfileImage'])->name('account.profile.image.destroy');
    Route::get('/account/password', [AuthController::class, 'showChangePassword'])->name('account.password.edit');
    Route::put('/account/password', [AuthController::class, 'changePassword'])->name('account.password.update');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllRead');

    // Dashboard general (redirige selon rôle)
    Route::get('/', [DocumentController::class, 'dashboard'])->name('dashboard');

    // Téléchargement (tous rôles authentifiés)
    Route::get('/documents/{document}/download', DownloadController::class)->name('documents.download');
    Route::get('/documents/{document}/pdf', [ExportController::class, 'pdf'])->name('documents.export.pdf');
    Route::get('/documents/{id}/convert-pdf', [DocumentController::class, 'convertToPdf'])->name('documents.convert.pdf');

    // Mes documents (tous rôles)
    Route::get('/my-documents', [DocumentController::class, 'myDocuments'])->name('documents.my');

    // Archive des documents finalisés (tous rôles)
    Route::get('/documents/archive', [DocumentController::class, 'archive'])->name('documents.archive');

    // ── Créateur ──────────────────────────────────────────────────────────────
    Route::middleware('role:creator')->group(function () {
        Route::prefix('documents')->name('documents.')->group(function () {
            Route::get('/creator', [DocumentController::class, 'indexCreator'])->name('creator.index');
            Route::get('/create', [DocumentController::class, 'create'])->name('create');
            Route::post('/', [DocumentController::class, 'store'])->name('store');
            Route::get('/{document}/edit', [DocumentController::class, 'edit'])->name('edit');
            Route::put('/{document}', [DocumentController::class, 'update'])->name('update');
            Route::delete('/{document}', [DocumentController::class, 'requestDeletion'])->name('requestDeletion');
        });

        // Signature
        Route::post('/workflow/{id}/sign', [DocumentWorkflowController::class, 'creatorSign'])->name('workflow.creator.sign');

        Route::prefix('workflow')->name('workflow.')->group(function () {
            // Étape 1 : envoi à l'admin pour codification
            Route::post('/creator/{document}/send', [DocumentWorkflowController::class, 'creatorSendToAdmin'])->name('creator.send');
            // Étape 3 : envoi au validateur après réception du code
            Route::post('/creator/{document}/send-to-validator', [DocumentWorkflowController::class, 'creatorSendToValidator'])->name('creator.send_to_validator');
        });
    });

    // ── Vérificateur (Validator / Validateur) ───────────────────────────────────
    Route::middleware('role:validator')->prefix('workflow')->name('workflow.')->group(function () {
        Route::get('/validator', [DocumentWorkflowController::class, 'validatorIndex'])->name('validator.index');
        Route::post('/validator/{document}/validate', [DocumentWorkflowController::class, 'validatorValidate'])->name('validator.validate');
        Route::post('/validator/{document}/reject', [DocumentWorkflowController::class, 'validatorReject'])->name('validator.reject');
    });

    // ── Approbateur ───────────────────────────────────────────────────────────
    Route::middleware('role:approver')->prefix('workflow')->name('workflow.')->group(function () {
        Route::get('/approver', [DocumentWorkflowController::class, 'approverIndex'])->name('approver.index');
        Route::post('/approver/{document}/validate', [DocumentWorkflowController::class, 'approverValidate'])->name('approver.validate');
        Route::post('/approver/{document}/reject', [DocumentWorkflowController::class, 'approverReject'])->name('approver.reject');
    });

    // ── Administrateur ────────────────────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Gestion des utilisateurs
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::get('/users/pending', [UserApprovalController::class, 'index'])->name('users.pending');
        Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/resend-code', [AdminUserController::class, 'resendVerificationCode'])->name('users.resend_code');
        Route::post('/users/{user}/approve', [UserApprovalController::class, 'approve'])->name('users.approve');

        // Export - AVANT les routes avec parametres
        Route::get('/documents/export/csv', [ExportController::class, 'csv'])->name('documents.export.csv');
        Route::get('/documents/export/pdf', [ExportController::class, 'pdfExport'])->name('documents.export.follow-up-pdf');
        Route::get('/documents/export/word', [ExportController::class, 'wordExport'])->name('documents.export.follow-up-word');

        // Gestion des documents (CRUD admin)
        Route::get('/documents/create', [DocumentController::class, 'create'])->name('documents.create');
        Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
        Route::put('/documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
        Route::delete('/documents/{document}', [DocumentController::class, 'destroyAdmin'])->name('documents.destroy');

        // Codification (étape 2 du workflow)
        Route::get('/documents/codification', [AdminCodificationController::class, 'index'])->name('documents.codification');

        // Vue d'ensemble des documents (avec filtres)
        Route::get('/documents', [AdminDashboardController::class, 'documents'])->name('documents.index');
        Route::post('/documents/{document}/codify', [AdminCodificationController::class, 'codify'])->name('documents.codify');

        // Validation finale + signature admin (étape 9)
        Route::post('/workflow/{document}/validate', [DocumentWorkflowController::class, 'adminValidate'])->name('workflow.validate');
        Route::post('/workflow/{document}/sign', [DocumentWorkflowController::class, 'adminSign'])->name('workflow.sign');
        Route::post('/workflow/{document}/reject', [DocumentWorkflowController::class, 'adminReject'])->name('workflow.reject');

        // Export PDF pour un document specifique
        Route::get('/documents/{document}/pdf', [ExportController::class, 'pdf'])->name('documents.export.pdf');
    });
});
