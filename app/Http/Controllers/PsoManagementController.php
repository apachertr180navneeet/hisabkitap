<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Prefix;
use App\Models\PsoConfig;
use App\Models\User;
use Illuminate\Http\Request;

class PsoManagementController extends Controller
{
    public function index()
    {
        $psoList = PsoConfig::withCount('bills')->get();

        return view('pso.index', compact('psoList'));
    }

    public function create()
    {
        $prefixes = Prefix::where('is_active', true)->orderBy('prefix')->get();

        $maxId = PsoConfig::max('id') ?? 0;
        $codes = PsoConfig::pluck('code')->toArray();
        $maxNum = 0;
        foreach ($codes as $c) {
            if (preg_match('/PSO-(\d+)/i', $c, $matches)) {
                $num = (int)$matches[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }
        $nextNum = max($maxId, $maxNum) + 1;
        $suggestedCode = 'PSO-' . $nextNum;

        $operators = User::orderBy('name')->pluck('name')
            ->merge(PsoConfig::distinct()->pluck('operator_name'))
            ->unique()
            ->filter()
            ->values();

        $drivers = PsoConfig::whereNotNull('driver_name')->where('driver_name', '!=', '')->distinct()->pluck('driver_name')->values();
        $helpers = PsoConfig::whereNotNull('helper_1')->pluck('helper_1')
            ->merge(PsoConfig::whereNotNull('helper_2')->pluck('helper_2'))
            ->merge(PsoConfig::whereNotNull('helper_3')->pluck('helper_3'))
            ->unique()->filter()->values();
        $gadiOptions = PsoConfig::whereNotNull('gadi_number')->where('gadi_number', '!=', '')->distinct()->pluck('gadi_number')->values();

        return view('pso.create', compact('prefixes', 'suggestedCode', 'operators', 'drivers', 'helpers', 'gadiOptions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'prefix' => 'nullable',
            'financial_year' => 'nullable',
            'start_no' => 'nullable',
            'end_no' => 'nullable',
            'series' => 'nullable|array',
            'operator_name' => 'required|string|max:255',
            'driver_name' => 'nullable|string|max:255',
            'helper_1' => 'nullable|string|max:255',
            'helper_2' => 'nullable|string|max:255',
            'helper_3' => 'nullable|string|max:255',
            'gadi_number' => 'nullable|string|max:255',
            'vehicle_no' => 'nullable|string|max:255',
            'specials' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'nullable',
        ]);

        $maxId = PsoConfig::max('id') ?? 0;
        $codes = PsoConfig::pluck('code')->toArray();
        $maxNum = 0;
        foreach ($codes as $c) {
            if (preg_match('/PSO-(\d+)/i', $c, $matches)) {
                $num = (int)$matches[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }
        $nextNum = max($maxId, $maxNum) + 1;
        $code = 'PSO-' . $nextNum;

        $specialsArr = [];
        if (!empty($validated['specials'])) {
            $specialsArr = array_values(array_filter(array_map('trim', explode(',', $validated['specials']))));
        }

        $activeFy = \App\Models\SystemSetting::getVal('financial_year', '2026-2027');

        // Parse series ranges (from dynamic 'series' rows or parallel arrays or single fields)
        $seriesList = [];
        if ($request->has('series') && is_array($request->input('series'))) {
            foreach ($request->input('series') as $item) {
                if (!empty($item['prefix'])) {
                    $start = isset($item['start_no']) ? (int)$item['start_no'] : 1;
                    $end = isset($item['end_no']) ? (int)$item['end_no'] : 10;
                    if ($end < $start) $end = $start;
                    $seriesList[] = [
                        'prefix' => strtoupper(trim($item['prefix'])),
                        'financial_year' => !empty($item['financial_year']) ? trim($item['financial_year']) : $activeFy,
                        'start_no' => $start,
                        'end_no' => $end,
                    ];
                }
            }
        } elseif (is_array($request->input('prefix'))) {
            $prefixes = $request->input('prefix');
            $starts = (array)$request->input('start_no');
            $ends = (array)$request->input('end_no');
            $fys = (array)$request->input('financial_year');
            foreach ($prefixes as $i => $pfx) {
                if (!empty($pfx)) {
                    $start = isset($starts[$i]) ? (int)$starts[$i] : 1;
                    $end = isset($ends[$i]) ? (int)$ends[$i] : 10;
                    if ($end < $start) $end = $start;
                    $seriesList[] = [
                        'prefix' => strtoupper(trim($pfx)),
                        'financial_year' => !empty($fys[$i]) ? trim($fys[$i]) : $activeFy,
                        'start_no' => $start,
                        'end_no' => $end,
                    ];
                }
            }
        }

        if (empty($seriesList)) {
            $singlePrefix = strtoupper(trim((string)$request->input('prefix', 'CB')));
            $singleStart = (int)$request->input('start_no', 1);
            $singleEnd = (int)$request->input('end_no', 10);
            if ($singleEnd < $singleStart) $singleEnd = $singleStart;
            $seriesList[] = [
                'prefix' => $singlePrefix ?: 'CB',
                'financial_year' => (string)$request->input('financial_year', $activeFy),
                'start_no' => $singleStart,
                'end_no' => $singleEnd,
            ];
        }

        $primary = $seriesList[0];

        $pso = PsoConfig::create([
            'code' => $code,
            'prefix' => $primary['prefix'],
            'financial_year' => $primary['financial_year'],
            'series_ranges' => $seriesList,
            'start_no' => $primary['start_no'],
            'end_no' => $primary['end_no'],
            'specials' => $specialsArr,
            'operator_name' => $validated['operator_name'],
            'driver_name' => $validated['driver_name'] ?? $request->input('driver_name'),
            'helper_1' => $validated['helper_1'] ?? $request->input('helper_1'),
            'helper_2' => $validated['helper_2'] ?? $request->input('helper_2'),
            'helper_3' => $validated['helper_3'] ?? $request->input('helper_3'),
            'gadi_number' => $validated['gadi_number'] ?? $request->input('gadi_number') ?? $request->input('vehicle_no'),
            'description' => $validated['description'] ?? ('Counter Bills ' . $primary['prefix'] . " {$primary['start_no']} to {$primary['end_no']}"),
            'is_active' => $request->has('is_active') ? (bool)$request->is_active : true,
        ]);

        AuditLog::log('PSO_CONFIG_CREATE', "Created PSO series {$code} range {$pso->prefix} {$pso->start_no}-{$pso->end_no}");

        return redirect()->route('admin.pso.index')->with('success', "PSO Configuration '{$pso->code}' successfully created.");
    }

    public function edit($id)
    {
        $pso = PsoConfig::withCount('bills')->findOrFail($id);
        $prefixes = Prefix::where('is_active', true)->orderBy('prefix')->get();
        $operators = User::orderBy('name')->pluck('name')
            ->merge(PsoConfig::distinct()->pluck('operator_name'))
            ->unique()
            ->filter()
            ->values();

        $drivers = PsoConfig::whereNotNull('driver_name')->where('driver_name', '!=', '')->distinct()->pluck('driver_name')->values();
        $helpers = PsoConfig::whereNotNull('helper_1')->pluck('helper_1')
            ->merge(PsoConfig::whereNotNull('helper_2')->pluck('helper_2'))
            ->merge(PsoConfig::whereNotNull('helper_3')->pluck('helper_3'))
            ->unique()->filter()->values();
        $gadiOptions = PsoConfig::whereNotNull('gadi_number')->where('gadi_number', '!=', '')->distinct()->pluck('gadi_number')->values();

        return view('pso.edit', compact('pso', 'prefixes', 'operators', 'drivers', 'helpers', 'gadiOptions'));
    }

    public function update(Request $request, $id)
    {
        $pso = PsoConfig::findOrFail($id);

        $validated = $request->validate([
            'prefix' => 'nullable',
            'financial_year' => 'nullable',
            'start_no' => 'nullable',
            'end_no' => 'nullable',
            'series' => 'nullable|array',
            'operator_name' => 'required|string|max:255',
            'driver_name' => 'nullable|string|max:255',
            'helper_1' => 'nullable|string|max:255',
            'helper_2' => 'nullable|string|max:255',
            'helper_3' => 'nullable|string|max:255',
            'gadi_number' => 'nullable|string|max:255',
            'vehicle_no' => 'nullable|string|max:255',
            'specials' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'nullable',
        ]);

        $specialsArr = [];
        if (!empty($validated['specials'])) {
            $specialsArr = array_values(array_filter(array_map('trim', explode(',', $validated['specials']))));
        }

        $activeFy = \App\Models\SystemSetting::getVal('financial_year', '2026-2027');

        // Parse series ranges
        $seriesList = [];
        if ($request->has('series') && is_array($request->input('series'))) {
            foreach ($request->input('series') as $item) {
                if (!empty($item['prefix'])) {
                    $start = isset($item['start_no']) ? (int)$item['start_no'] : 1;
                    $end = isset($item['end_no']) ? (int)$item['end_no'] : 10;
                    if ($end < $start) $end = $start;
                    $seriesList[] = [
                        'prefix' => strtoupper(trim($item['prefix'])),
                        'financial_year' => !empty($item['financial_year']) ? trim($item['financial_year']) : $activeFy,
                        'start_no' => $start,
                        'end_no' => $end,
                    ];
                }
            }
        } elseif (is_array($request->input('prefix'))) {
            $prefixes = $request->input('prefix');
            $starts = (array)$request->input('start_no');
            $ends = (array)$request->input('end_no');
            $fys = (array)$request->input('financial_year');
            foreach ($prefixes as $i => $pfx) {
                if (!empty($pfx)) {
                    $start = isset($starts[$i]) ? (int)$starts[$i] : 1;
                    $end = isset($ends[$i]) ? (int)$ends[$i] : 10;
                    if ($end < $start) $end = $start;
                    $seriesList[] = [
                        'prefix' => strtoupper(trim($pfx)),
                        'financial_year' => !empty($fys[$i]) ? trim($fys[$i]) : $activeFy,
                        'start_no' => $start,
                        'end_no' => $end,
                    ];
                }
            }
        }

        if (empty($seriesList)) {
            $singlePrefix = strtoupper(trim((string)$request->input('prefix', $pso->prefix)));
            $singleStart = (int)$request->input('start_no', $pso->start_no);
            $singleEnd = (int)$request->input('end_no', $pso->end_no);
            if ($singleEnd < $singleStart) $singleEnd = $singleStart;
            $seriesList[] = [
                'prefix' => $singlePrefix ?: $pso->prefix,
                'financial_year' => (string)$request->input('financial_year', $pso->financial_year ?? $activeFy),
                'start_no' => $singleStart,
                'end_no' => $singleEnd,
            ];
        }

        $primary = $seriesList[0];

        $pso->update([
            'prefix' => $primary['prefix'],
            'financial_year' => $primary['financial_year'],
            'series_ranges' => $seriesList,
            'start_no' => $primary['start_no'],
            'end_no' => $primary['end_no'],
            'specials' => $specialsArr,
            'operator_name' => $validated['operator_name'],
            'driver_name' => $validated['driver_name'] ?? $request->input('driver_name'),
            'helper_1' => $validated['helper_1'] ?? $request->input('helper_1'),
            'helper_2' => $validated['helper_2'] ?? $request->input('helper_2'),
            'helper_3' => $validated['helper_3'] ?? $request->input('helper_3'),
            'gadi_number' => $validated['gadi_number'] ?? $request->input('gadi_number') ?? $request->input('vehicle_no'),
            'description' => $validated['description'] ?? null,
            'is_active' => $request->has('is_active') ? (bool)$request->is_active : false,
        ]);

        AuditLog::log('PSO_CONFIG_UPDATE', "Updated PSO series {$pso->code} range {$pso->prefix} {$pso->start_no}-{$pso->end_no}");

        return redirect()->route('admin.pso.index')->with('success', "PSO Configuration '{$pso->code}' updated successfully.");
    }

    public function toggleStatus($id)
    {
        $pso = PsoConfig::findOrFail($id);
        $pso->is_active = ! $pso->is_active;
        $pso->save();

        AuditLog::log('PSO_STATUS_TOGGLE', "Toggled active status of {$pso->code} to " . ($pso->is_active ? 'Active' : 'Inactive'));

        return redirect()->back()->with('success', "PSO {$pso->code} status updated.");
    }

    public function destroy($id)
    {
        $pso = PsoConfig::findOrFail($id);

        if ($pso->bills()->count() > 0) {
            return redirect()->route('admin.pso.index')->with('warning', "Cannot delete PSO Series {$pso->code} because it already has linked bill records. You can disable it instead.");
        }

        $code = $pso->code;
        $pso->delete();

        AuditLog::log('PSO_CONFIG_DELETE', "Deleted PSO series {$code}");

        return redirect()->route('admin.pso.index')->with('success', "PSO Configuration '{$code}' deleted successfully.");
    }
}
