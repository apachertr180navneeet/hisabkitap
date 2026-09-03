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
use App\Http\Controllers\UserController;
use App\Http\Controllers\DatabaseMigrationController;
use App\Http\Controllers\PrefixMasterController;
use App\Http\Controllers\SalespersonController;

// ==========================================
// 1. PUBLIC HOME LANDING PAGE
// URL: http://127.0.0.1:8000/
// ==========================================
Route::get('/', [HomeController::class, 'index'])->name('home');

// ==========================================
// DIRECT SERVER MIGRATION & SCHEMA ENDPOINTS
// URLs: /migrate, /migrate/status, /migrate/clear, /migrate/seed
// ==========================================
Route::get('/migrate', [DatabaseMigrationController::class, 'migrate'])->name('system.migrate');
Route::get('/migrate/status', [DatabaseMigrationController::class, 'status'])->name('system.migrate.status');
Route::get('/migrate/clear', [DatabaseMigrationController::class, 'clearCache'])->name('system.migrate.clear');
Route::get('/migrate/seed', [DatabaseMigrationController::class, 'seed'])->name('system.migrate.seed');
Route::get('/run-migration', [DatabaseMigrationController::class, 'migrate']);
Route::get('/run-migrations', [DatabaseMigrationController::class, 'migrate']);

// ==========================================
// 2. AUTHENTICATION & LOGIN ROUTES
// URLs: http://127.0.0.1:8000/admin/login and /login
// ==========================================
Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.post');
Route::get('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout.get');
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

// Aliases for convenience
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout.get');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ==========================================
// 3. ADMIN & ERP DASHBOARD & MODULES
// URLs: http://127.0.0.1:8000/admin/dashboard
// ==========================================
Route::prefix('admin')->middleware('auth')->group(function () {

    // 0. Quick Role / User Switcher
    Route::post('/switch-user/{id}', [AuthController::class, 'switchUser'])->name('admin.switch_user');
    Route::get('/switch-user/{id}', [AuthController::class, 'switchUser']);

    // Dashboard (supporting both /dashboard and typo alias /dashoard)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/dashoard', [DashboardController::class, 'index'])->name('admin.dashoard');
    Route::get('/', [DashboardController::class, 'index']);

    // 1. PSO Series Management
    Route::get('/pso', [PsoManagementController::class, 'index'])->name('admin.pso.index');
    Route::post('/pso/store', [PsoManagementController::class, 'store'])->middleware(['read.only', 'permission:can_configure_pso'])->name('admin.pso.store');
    Route::post('/pso/{id}/toggle', [PsoManagementController::class, 'toggleStatus'])->middleware(['read.only', 'permission:can_configure_pso'])->name('admin.pso.toggle');

    // 2. Tally Excel Import
    Route::get('/import', [ExcelImportController::class, 'index'])->name('admin.import.index');
    Route::post('/import', [ExcelImportController::class, 'import'])->middleware(['read.only', 'permission:can_import_excel'])->name('admin.import.process');
    Route::get('/import/sample-download', [ExcelImportController::class, 'downloadSample'])->name('admin.import.sample');

    // 3. Bill Verification
    Route::get('/verification', [BillVerificationController::class, 'index'])->name('admin.verification.index');
    Route::post('/verification/resolve-missing', [BillVerificationController::class, 'resolveMissing'])->middleware(['read.only', 'permission:can_edit_bills'])->name('admin.verification.resolve');
    Route::post('/verification/auto-verify', [BillVerificationController::class, 'autoVerifyAll'])->middleware(['read.only', 'permission:can_edit_bills'])->name('admin.verification.auto_verify');
    Route::get('/verification/export', [BillVerificationController::class, 'exportCsv'])->name('admin.verification.export');

    // 4. Payment Classification
    Route::get('/payment-classification', [PaymentClassificationController::class, 'index'])->name('admin.payment.index');

    // 5. Corrections & Returns
    Route::get('/corrections', [CorrectionsController::class, 'index'])->name('admin.corrections.index');
    Route::post('/corrections/store', [CorrectionsController::class, 'store'])->middleware(['read.only', 'permission:can_record_corrections'])->name('admin.corrections.store');

    // 6. Credit Collection
    Route::get('/credit-collection', [CreditCollectionController::class, 'index'])->name('admin.credit.index');
    Route::post('/credit-collection/update', [CreditCollectionController::class, 'updatePayment'])->middleware(['read.only', 'permission:can_record_credit'])->name('admin.credit.update');
    Route::get('/credit-collection/export', [CreditCollectionController::class, 'exportSheet'])->name('admin.credit.export');

    // 7. PSO Summary Matrix
    Route::get('/pso-summary', [PsoSummaryController::class, 'index'])->name('admin.summary.index');

    // 8. Master Reconciliation Engine
    Route::get('/reconciliation', [MasterReconciliationController::class, 'index'])->name('admin.reconciliation.index');
    Route::post('/reconciliation/quick-resolve', [MasterReconciliationController::class, 'quickResolveDiscrepancy'])->middleware('read.only')->name('admin.reconciliation.resolve');

    // 9. Approval & Sealing
    Route::get('/approval-sealing', [ApprovalSealingController::class, 'index'])->name('admin.approval.index');
    Route::post('/approval-sealing/seal', [ApprovalSealingController::class, 'sealDay'])->middleware(['read.only', 'permission:can_approve_sealing'])->name('admin.approval.seal');
    Route::post('/approval-sealing/unseal', [ApprovalSealingController::class, 'unsealDay'])->middleware(['read.only', 'permission:can_approve_sealing'])->name('admin.approval.unseal');

    // 10. 7-Day Retention Radar
    Route::get('/retention', [RetentionController::class, 'index'])->name('admin.retention.index');

    // 11. Reports & Exports
    Route::get('/reports', [ReportsController::class, 'index'])->name('admin.reports.index');
    Route::get('/reports/export', [ReportsController::class, 'exportExcel'])->name('admin.reports.export');

    // 12. Cutoff & Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('admin.settings.index');
    Route::post('/settings/update', [SettingsController::class, 'update'])->middleware(['read.only', 'permission:can_edit_cutoff'])->name('admin.settings.update');
    Route::post('/settings/financial-year/set-active', [SettingsController::class, 'setActiveFinancialYear'])->middleware(['read.only', 'permission:can_edit_cutoff'])->name('admin.settings.financial_year.set_active');
    Route::post('/settings/financial-year/store', [SettingsController::class, 'storeFinancialYear'])->middleware(['read.only', 'permission:can_edit_cutoff'])->name('admin.settings.financial_year.store');
    Route::post('/settings/financial-year/{id}/toggle-lock', [SettingsController::class, 'toggleLockFinancialYear'])->middleware(['read.only', 'permission:can_edit_cutoff'])->name('admin.settings.financial_year.toggle_lock');

    // 13. User Profile & Change Password
    Route::get('/profile', [ProfileController::class, 'index'])->name('admin.profile');
    Route::post('/profile/update', [ProfileController::class, 'updateProfile'])->middleware('read.only')->name('admin.profile.update');
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword'])->middleware('read.only')->name('admin.profile.password');

    // 14. User & Role Management
    Route::get('/users', [UserController::class, 'index'])->middleware('permission:can_manage_users')->name('admin.users.index');
    Route::post('/users/store', [UserController::class, 'store'])->middleware(['read.only', 'permission:can_manage_users'])->name('admin.users.store');
    Route::post('/users/{id}/update', [UserController::class, 'update'])->middleware(['read.only', 'permission:can_manage_users'])->name('admin.users.update');
    Route::post('/users/{id}/change-password', [UserController::class, 'changePassword'])->middleware(['read.only', 'permission:can_manage_users'])->name('admin.users.password');
    Route::post('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->middleware(['read.only', 'permission:can_manage_users'])->name('admin.users.toggle');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->middleware(['read.only', 'permission:can_manage_users'])->name('admin.users.delete');

    // 15. Server Database Migration Core
    Route::get('/migrate', [DatabaseMigrationController::class, 'migrate'])->name('admin.migrate');
    Route::get('/migrate/status', [DatabaseMigrationController::class, 'status'])->name('admin.migrate.status');
    Route::get('/migrate/clear', [DatabaseMigrationController::class, 'clearCache'])->name('admin.migrate.clear');
    Route::get('/migrate/seed', [DatabaseMigrationController::class, 'seed'])->name('admin.migrate.seed');

    // 16. Prefix Master
    Route::get('/prefix-master', [PrefixMasterController::class, 'index'])->name('admin.prefix.index');
    Route::post('/prefix-master/store', [PrefixMasterController::class, 'store'])->middleware(['read.only', 'permission:can_configure_pso'])->name('admin.prefix.store');
    Route::post('/prefix-master/{id}/update', [PrefixMasterController::class, 'update'])->middleware(['read.only', 'permission:can_configure_pso'])->name('admin.prefix.update');
    Route::post('/prefix-master/{id}/toggle', [PrefixMasterController::class, 'toggleStatus'])->middleware(['read.only', 'permission:can_configure_pso'])->name('admin.prefix.toggle');
    Route::delete('/prefix-master/{id}', [PrefixMasterController::class, 'destroy'])->middleware(['read.only', 'permission:can_configure_pso'])->name('admin.prefix.delete');

    // 17. Salesperson Master
    Route::get('/salespersons', [SalespersonController::class, 'index'])->name('admin.salespersons.index');
    Route::post('/salespersons/store', [SalespersonController::class, 'store'])->middleware(['read.only', 'permission:can_configure_pso'])->name('admin.salespersons.store');
    Route::post('/salespersons/{id}/update', [SalespersonController::class, 'update'])->middleware(['read.only', 'permission:can_configure_pso'])->name('admin.salespersons.update');
    Route::post('/salespersons/{id}/toggle', [SalespersonController::class, 'toggleStatus'])->middleware(['read.only', 'permission:can_configure_pso'])->name('admin.salespersons.toggle');
    Route::delete('/salespersons/{id}', [SalespersonController::class, 'destroy'])->middleware(['read.only', 'permission:can_configure_pso'])->name('admin.salespersons.delete');
});

// Non-admin root routes for backward compatibility
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::post('/profile/update', [ProfileController::class, 'updateProfile'])->middleware('read.only')->name('profile.update');
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword'])->middleware('read.only')->name('profile.password');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/pso', [PsoManagementController::class, 'index'])->name('pso.index');
    Route::post('/pso/store', [PsoManagementController::class, 'store'])->middleware('read.only')->name('pso.store');
    Route::post('/pso/{id}/toggle', [PsoManagementController::class, 'toggleStatus'])->middleware('read.only')->name('pso.toggle');
    Route::get('/import', [ExcelImportController::class, 'index'])->name('import.index');
    Route::post('/import', [ExcelImportController::class, 'import'])->middleware('read.only')->name('import.process');
    Route::get('/import/sample-download', [ExcelImportController::class, 'downloadSample'])->name('import.sample');
    Route::get('/verification', [BillVerificationController::class, 'index'])->name('verification.index');
    Route::post('/verification/resolve-missing', [BillVerificationController::class, 'resolveMissing'])->middleware('read.only')->name('verification.resolve');
    Route::post('/verification/auto-verify', [BillVerificationController::class, 'autoVerifyAll'])->middleware('read.only')->name('verification.auto_verify');
    Route::get('/verification/export', [BillVerificationController::class, 'exportCsv'])->name('verification.export');
    Route::get('/payment-classification', [PaymentClassificationController::class, 'index'])->name('payment.index');
    Route::get('/corrections', [CorrectionsController::class, 'index'])->name('corrections.index');
    Route::post('/corrections/store', [CorrectionsController::class, 'store'])->middleware('read.only')->name('corrections.store');
    Route::get('/credit-collection', [CreditCollectionController::class, 'index'])->name('credit.index');
    Route::post('/credit-collection/update', [CreditCollectionController::class, 'updatePayment'])->middleware('read.only')->name('credit.update');
    Route::get('/credit-collection/export', [CreditCollectionController::class, 'exportSheet'])->name('credit.export');
    Route::get('/pso-summary', [PsoSummaryController::class, 'index'])->name('summary.index');
    Route::get('/reconciliation', [MasterReconciliationController::class, 'index'])->name('reconciliation.index');
    Route::post('/reconciliation/quick-resolve', [MasterReconciliationController::class, 'quickResolveDiscrepancy'])->middleware('read.only')->name('reconciliation.resolve');
    Route::get('/approval-sealing', [ApprovalSealingController::class, 'index'])->name('approval.index');
    Route::post('/approval-sealing/seal', [ApprovalSealingController::class, 'sealDay'])->middleware('read.only')->name('approval.seal');
    Route::post('/approval-sealing/unseal', [ApprovalSealingController::class, 'unsealDay'])->middleware('read.only')->name('approval.unseal');
    Route::get('/retention', [RetentionController::class, 'index'])->name('retention.index');
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportsController::class, 'exportExcel'])->name('reports.export');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/update', [SettingsController::class, 'update'])->middleware('read.only')->name('settings.update');
    Route::post('/settings/financial-year/set-active', [SettingsController::class, 'setActiveFinancialYear'])->middleware('read.only')->name('settings.financial_year.set_active');
    Route::post('/settings/financial-year/store', [SettingsController::class, 'storeFinancialYear'])->middleware('read.only')->name('settings.financial_year.store');
    Route::post('/settings/financial-year/{id}/toggle-lock', [SettingsController::class, 'toggleLockFinancialYear'])->middleware('read.only')->name('settings.financial_year.toggle_lock');
    Route::get('/prefix-master', [PrefixMasterController::class, 'index'])->name('prefix.index');
    Route::post('/prefix-master/store', [PrefixMasterController::class, 'store'])->middleware('read.only')->name('prefix.store');
    Route::post('/prefix-master/{id}/update', [PrefixMasterController::class, 'update'])->middleware('read.only')->name('prefix.update');
    Route::post('/prefix-master/{id}/toggle', [PrefixMasterController::class, 'toggleStatus'])->middleware('read.only')->name('prefix.toggle');
    Route::delete('/prefix-master/{id}', [PrefixMasterController::class, 'destroy'])->middleware('read.only')->name('prefix.delete');
    Route::get('/salespersons', [SalespersonController::class, 'index'])->name('salespersons.index');
    Route::post('/salespersons/store', [SalespersonController::class, 'store'])->middleware('read.only')->name('salespersons.store');
    Route::post('/salespersons/{id}/update', [SalespersonController::class, 'update'])->middleware('read.only')->name('salespersons.update');
    Route::post('/salespersons/{id}/toggle', [SalespersonController::class, 'toggleStatus'])->middleware('read.only')->name('salespersons.toggle');
    Route::delete('/salespersons/{id}', [SalespersonController::class, 'destroy'])->middleware('read.only')->name('salespersons.delete');
});
