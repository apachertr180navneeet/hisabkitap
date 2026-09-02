<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DatabaseMigrationController extends Controller
{
    /**
     * Run all pending database migrations on the live server.
     */
    public function migrate(Request $request)
    {
        try {
            $exitCode = Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();
            $status = $exitCode === 0 ? 'success' : 'error';
            $message = $exitCode === 0 ? 'Database migrations executed successfully!' : 'Migration exited with warnings or errors.';
        } catch (\Throwable $e) {
            $exitCode = 1;
            $output = $e->getMessage();
            $status = 'error';
            $message = 'Migration exception: ' . $e->getMessage();
        }

        if ($request->wantsJson() || $request->query('json')) {
            return response()->json([
                'status' => $status,
                'message' => $message,
                'exit_code' => $exitCode,
                'output' => $output,
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        return $this->renderView('Migrate Tables (Database Upgrade)', $status, $message, $output);
    }

    /**
     * Check migration status.
     */
    public function status(Request $request)
    {
        try {
            $exitCode = Artisan::call('migrate:status');
            $output = Artisan::output();
            $status = 'info';
            $message = 'Current database migration status.';
        } catch (\Throwable $e) {
            $exitCode = 1;
            $output = $e->getMessage();
            $status = 'error';
            $message = 'Error checking status: ' . $e->getMessage();
        }

        if ($request->wantsJson() || $request->query('json')) {
            return response()->json([
                'status' => $status,
                'message' => $message,
                'exit_code' => $exitCode,
                'output' => $output,
            ]);
        }

        return $this->renderView('Migration Status', $status, $message, $output);
    }

    /**
     * Clear all application caches (Config, Routes, Views, Cache).
     */
    public function clearCache(Request $request)
    {
        try {
            Artisan::call('optimize:clear');
            $output = Artisan::output();
            $status = 'success';
            $message = 'All application caches cleared successfully!';
        } catch (\Throwable $e) {
            $output = $e->getMessage();
            $status = 'error';
            $message = 'Error clearing caches: ' . $e->getMessage();
        }

        if ($request->wantsJson() || $request->query('json')) {
            return response()->json([
                'status' => $status,
                'message' => $message,
                'output' => $output,
            ]);
        }

        return $this->renderView('Clear Application Caches', $status, $message, $output);
    }

    /**
     * Seed database seeds.
     */
    public function seed(Request $request)
    {
        try {
            $exitCode = Artisan::call('db:seed', ['--force' => true]);
            $output = Artisan::output();
            $status = $exitCode === 0 ? 'success' : 'error';
            $message = $exitCode === 0 ? 'Database seeding completed successfully!' : 'Database seeding exited with errors.';
        } catch (\Throwable $e) {
            $output = $e->getMessage();
            $status = 'error';
            $message = 'Seeding exception: ' . $e->getMessage();
        }

        if ($request->wantsJson() || $request->query('json')) {
            return response()->json([
                'status' => $status,
                'message' => $message,
                'output' => $output,
            ]);
        }

        return $this->renderView('Database Seeding', $status, $message, $output);
    }

    /**
     * Render the visual server migration dashboard.
     */
    protected function renderView(string $title, string $status, string $message, string $output)
    {
        // Collect list of existing database tables
        $tables = [];
        try {
            $dbTables = DB::select('SHOW TABLES');
            foreach ($dbTables as $row) {
                $tableObj = (array)$row;
                $tables[] = reset($tableObj);
            }
        } catch (\Throwable $e) {
            // DB may not be connected
        }

        $billsColumns = [];
        try {
            if (Schema::hasTable('bills')) {
                $billsColumns = Schema::getColumnListing('bills');
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return view('system.migrate', compact('title', 'status', 'message', 'output', 'tables', 'billsColumns'));
    }
}
