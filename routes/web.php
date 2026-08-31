<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PsoManagementController;
use App\Http\Controllers\ExcelImportController;
use App\Http\Controllers\BillVerificationController;
use App\Http\Controllers\PaymentClassificationController;
use App\Http\Controllers\CorrectionsController;
use App\Http\Controllers\CreditCollectionController;
use App\Http\Controllers\PsoSummaryController;
use App\Http\Controllers\MasterReconciliationController;
use App\Http\Controllers\ApprovalSealingController;
use App\Http\Controllers\RetentionController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\RoleSwitchController;

// ==========================================
// 1. PUBLIC HOME LANDING PAGE
// URL: http://127.0.0.1:8000/
// ==========================================
Route::get('/', [HomeController::class, 'index'])->name('home');

// ==========================================
// 2. AUTHENTICATION & LOGIN ROUTES
// URLs: http://127.0.0.1:8000/admin/login and /login
// ==========================================
Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.post');
Route::get('/admin/login/quick', [AuthController::class, 'quickLogin'])->name('admin.login.quick');
Route::get('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout.get');
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

// Aliases for convenience
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/login/quick', [AuthController::class, 'quickLogin'])->name('login.quick');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout.get');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ==========================================
// 3. ADMIN & ERP DASHBOARD & MODULES
// URLs: http://127.0.0.1:8000/admin/dashboard
// ==========================================
Route::prefix('admin')->group(function () {
    
    // Dashboard (supporting both /dashboard and typo alias /dashoard)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/dashoard', [DashboardController::class, 'index'])->name('admin.dashoard');
    Route::get('/', [DashboardController::class, 'index']);

    // 1. PSO Series Management
    Route::get('/pso', [PsoManagementController::class, 'index'])->name('admin.pso.index');
    Route::post('/pso/store', [PsoManagementController::class, 'store'])->name('admin.pso.store');
    Route::post('/pso/{id}/toggle', [PsoManagementController::class, 'toggleStatus'])->name('admin.pso.toggle');

    // 2. Tally Excel Import
    Route::get('/import', [ExcelImportController::class, 'index'])->name('admin.import.index');
    Route::post('/import', [ExcelImportController::class, 'import'])->name('admin.import.process');
    Route::get('/import/sample-download', [ExcelImportController::class, 'downloadSample'])->name('admin.import.sample');

    // 3. Bill Verification
    Route::get('/verification', [BillVerificationController::class, 'index'])->name('admin.verification.index');
    Route::post('/verification/resolve-missing', [BillVerificationController::class, 'resolveMissing'])->name('admin.verification.resolve');
    Route::post('/verification/auto-verify', [BillVerificationController::class, 'autoVerifyAll'])->name('admin.verification.auto_verify');
    Route::get('/verification/export', [BillVerificationController::class, 'exportCsv'])->name('admin.verification.export');

    // 4. Payment Classification
    Route::get('/payment-classification', [PaymentClassificationController::class, 'index'])->name('admin.payment.index');

    // 5. Corrections & Returns
    Route::get('/corrections', [CorrectionsController::class, 'index'])->name('admin.corrections.index');
    Route::post('/corrections/store', [CorrectionsController::class, 'store'])->name('admin.corrections.store');

    // 6. Credit Collection
    Route::get('/credit-collection', [CreditCollectionController::class, 'index'])->name('admin.credit.index');
    Route::post('/credit-collection/update', [CreditCollectionController::class, 'updatePayment'])->name('admin.credit.update');
    Route::get('/credit-collection/export', [CreditCollectionController::class, 'exportSheet'])->name('admin.credit.export');

    // 7. PSO Summary Matrix
    Route::get('/pso-summary', [PsoSummaryController::class, 'index'])->name('admin.summary.index');

    // 8. Master Reconciliation Engine
    Route::get('/reconciliation', [MasterReconciliationController::class, 'index'])->name('admin.reconciliation.index');
    Route::post('/reconciliation/quick-resolve', [MasterReconciliationController::class, 'quickResolveDiscrepancy'])->name('admin.reconciliation.resolve');

    // 9. Approval & Sealing
    Route::get('/approval-sealing', [ApprovalSealingController::class, 'index'])->name('admin.approval.index');
    Route::post('/approval-sealing/seal', [ApprovalSealingController::class, 'sealDay'])->name('admin.approval.seal');
    Route::post('/approval-sealing/unseal', [ApprovalSealingController::class, 'unsealDay'])->name('admin.approval.unseal');

    // 10. 7-Day Retention Radar
    Route::get('/retention', [RetentionController::class, 'index'])->name('admin.retention.index');

    // 11. Reports & Exports
    Route::get('/reports', [ReportsController::class, 'index'])->name('admin.reports.index');
    Route::get('/reports/export', [ReportsController::class, 'exportExcel'])->name('admin.reports.export');

    // 12. Cutoff & Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('admin.settings.index');
    Route::post('/settings/update', [SettingsController::class, 'update'])->name('admin.settings.update');

    // 13. Role & Scenario Switchers
    Route::get('/switch-role', [RoleSwitchController::class, 'switchRole'])->name('admin.role.switch');
    Route::get('/set-scenario', [RoleSwitchController::class, 'setScenario'])->name('admin.scenario.set');

    // 14. User Profile & Change Password
    Route::get('/profile', [ProfileController::class, 'index'])->name('admin.profile');
    Route::post('/profile/update', [ProfileController::class, 'updateProfile'])->name('admin.profile.update');
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword'])->name('admin.profile.password');
});

// Non-admin root routes for backward compatibility
Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
Route::post('/profile/update', [ProfileController::class, 'updateProfile'])->name('profile.update');
Route::post('/profile/change-password', [ProfileController::class, 'changePassword'])->name('profile.password');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/pso', [PsoManagementController::class, 'index'])->name('pso.index');
Route::post('/pso/store', [PsoManagementController::class, 'store'])->name('pso.store');
Route::post('/pso/{id}/toggle', [PsoManagementController::class, 'toggleStatus'])->name('pso.toggle');
Route::get('/import', [ExcelImportController::class, 'index'])->name('import.index');
Route::post('/import', [ExcelImportController::class, 'import'])->name('import.process');
Route::get('/import/sample-download', [ExcelImportController::class, 'downloadSample'])->name('import.sample');
Route::get('/verification', [BillVerificationController::class, 'index'])->name('verification.index');
Route::post('/verification/resolve-missing', [BillVerificationController::class, 'resolveMissing'])->name('verification.resolve');
Route::post('/verification/auto-verify', [BillVerificationController::class, 'autoVerifyAll'])->name('verification.auto_verify');
Route::get('/verification/export', [BillVerificationController::class, 'exportCsv'])->name('verification.export');
Route::get('/payment-classification', [PaymentClassificationController::class, 'index'])->name('payment.index');
Route::get('/corrections', [CorrectionsController::class, 'index'])->name('corrections.index');
Route::post('/corrections/store', [CorrectionsController::class, 'store'])->name('corrections.store');
Route::get('/credit-collection', [CreditCollectionController::class, 'index'])->name('credit.index');
Route::post('/credit-collection/update', [CreditCollectionController::class, 'updatePayment'])->name('credit.update');
Route::get('/credit-collection/export', [CreditCollectionController::class, 'exportSheet'])->name('credit.export');
Route::get('/pso-summary', [PsoSummaryController::class, 'index'])->name('summary.index');
Route::get('/reconciliation', [MasterReconciliationController::class, 'index'])->name('reconciliation.index');
Route::post('/reconciliation/quick-resolve', [MasterReconciliationController::class, 'quickResolveDiscrepancy'])->name('reconciliation.resolve');
Route::get('/approval-sealing', [ApprovalSealingController::class, 'index'])->name('approval.index');
Route::post('/approval-sealing/seal', [ApprovalSealingController::class, 'sealDay'])->name('approval.seal');
Route::post('/approval-sealing/unseal', [ApprovalSealingController::class, 'unsealDay'])->name('approval.unseal');
Route::get('/retention', [RetentionController::class, 'index'])->name('retention.index');
Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
Route::get('/reports/export', [ReportsController::class, 'exportExcel'])->name('reports.export');
Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
Route::post('/settings/update', [SettingsController::class, 'update'])->name('settings.update');
Route::get('/switch-role', [RoleSwitchController::class, 'switchRole'])->name('role.switch');
Route::get('/set-scenario', [RoleSwitchController::class, 'setScenario'])->name('scenario.set');
