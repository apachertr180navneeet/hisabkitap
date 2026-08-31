
<?php

use Illuminate\Support\Facades\Route;
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

use App\Http\Controllers\AuthController;

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/login/quick', [AuthController::class, 'quickLogin'])->name('login.quick');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout.get');

// 1. Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// 2. PSO Management
Route::get('/pso', [PsoManagementController::class, 'index'])->name('pso.index');
Route::post('/pso/store', [PsoManagementController::class, 'store'])->name('pso.store');
Route::post('/pso/{id}/toggle', [PsoManagementController::class, 'toggleStatus'])->name('pso.toggle');

// 3. Tally Excel Import
Route::get('/import', [ExcelImportController::class, 'index'])->name('import.index');
Route::post('/import', [ExcelImportController::class, 'import'])->name('import.process');
Route::get('/import/sample-download', [ExcelImportController::class, 'downloadSample'])->name('import.sample');

// 4. Bill Verification
Route::get('/verification', [BillVerificationController::class, 'index'])->name('verification.index');
Route::post('/verification/resolve-missing', [BillVerificationController::class, 'resolveMissing'])->name('verification.resolve');
Route::post('/verification/auto-verify', [BillVerificationController::class, 'autoVerifyAll'])->name('verification.auto_verify');
Route::get('/verification/export', [BillVerificationController::class, 'exportCsv'])->name('verification.export');

// 5. Payment Classification
Route::get('/payment-classification', [PaymentClassificationController::class, 'index'])->name('payment.index');

// 6. Corrections & Returns
Route::get('/corrections', [CorrectionsController::class, 'index'])->name('corrections.index');
Route::post('/corrections/store', [CorrectionsController::class, 'store'])->name('corrections.store');

// 7. Credit Collection
Route::get('/credit-collection', [CreditCollectionController::class, 'index'])->name('credit.index');
Route::post('/credit-collection/update', [CreditCollectionController::class, 'updatePayment'])->name('credit.update');
Route::get('/credit-collection/export', [CreditCollectionController::class, 'exportSheet'])->name('credit.export');

// 8. PSO Summary
Route::get('/pso-summary', [PsoSummaryController::class, 'index'])->name('summary.index');

// 9. Master Reconciliation
Route::get('/reconciliation', [MasterReconciliationController::class, 'index'])->name('reconciliation.index');
Route::post('/reconciliation/quick-resolve', [MasterReconciliationController::class, 'quickResolveDiscrepancy'])->name('reconciliation.resolve');

// 10. Approval & Sealing
Route::get('/approval-sealing', [ApprovalSealingController::class, 'index'])->name('approval.index');
Route::post('/approval-sealing/seal', [ApprovalSealingController::class, 'sealDay'])->name('approval.seal');
Route::post('/approval-sealing/unseal', [ApprovalSealingController::class, 'unsealDay'])->name('approval.unseal');

// 11. 7-Day Retention Window
Route::get('/retention', [RetentionController::class, 'index'])->name('retention.index');

// 12. Reports & Exports
Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
Route::get('/reports/export', [ReportsController::class, 'exportExcel'])->name('reports.export');

// 13. Cutoff & Settings
Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
Route::post('/settings/update', [SettingsController::class, 'update'])->name('settings.update');

// 14. Quick Persona & Scenario Switchers
Route::get('/switch-role', [RoleSwitchController::class, 'switchRole'])->name('role.switch');
Route::get('/set-scenario', [RoleSwitchController::class, 'setScenario'])->name('scenario.set');
