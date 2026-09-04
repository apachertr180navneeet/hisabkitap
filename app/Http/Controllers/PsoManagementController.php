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
        $nextNum = (PsoConfig::max('id') ?? 0) + 1;
        $suggestedCode = 'PSO-' . $nextNum;
        $operators = User::orderBy('name')->pluck('name')
            ->merge(PsoConfig::distinct()->pluck('operator_name'))
            ->unique()
            ->filter()
            ->values();

        return view('pso.create', compact('prefixes', 'suggestedCode', 'operators'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:50|unique:pso_configs,code',
            'name' => 'required|string|max:255',
            'prefix' => 'required|string|max:10',
            'start_no' => 'required|integer|min:1',
            'end_no' => 'required|integer|gte:start_no',
            'operator_name' => 'required|string|max:255',
            'specials' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'nullable',
        ]);

        $nextNum = (PsoConfig::max('id') ?? 0) + 1;
        $code = !empty($validated['code']) ? trim($validated['code']) : ('PSO-' . $nextNum);

        $specialsArr = [];
        if (!empty($validated['specials'])) {
            $specialsArr = array_values(array_filter(array_map('trim', explode(',', $validated['specials']))));
        }

        $pso = PsoConfig::create([
            'code' => $code,
            'name' => $validated['name'],
            'prefix' => strtoupper(trim($validated['prefix'])),
            'start_no' => $validated['start_no'],
            'end_no' => $validated['end_no'],
            'specials' => $specialsArr,
            'operator_name' => $validated['operator_name'],
            'description' => $validated['description'] ?? ('Counter Bills ' . strtoupper($validated['prefix']) . " {$validated['start_no']} to {$validated['end_no']}"),
            'is_active' => $request->has('is_active') ? (bool)$request->is_active : true,
        ]);

        AuditLog::log('PSO_CONFIG_CREATE', "Created PSO series {$code} ({$pso->name}) range {$pso->prefix} {$pso->start_no}-{$pso->end_no}");

        return redirect()->route('admin.pso.index')->with('success', "PSO Configuration '{$pso->name}' ({$pso->code}) successfully created.");
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

        return view('pso.edit', compact('pso', 'prefixes', 'operators'));
    }

    public function update(Request $request, $id)
    {
        $pso = PsoConfig::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:pso_configs,code,' . $id,
            'name' => 'required|string|max:255',
            'prefix' => 'required|string|max:10',
            'start_no' => 'required|integer|min:1',
            'end_no' => 'required|integer|gte:start_no',
            'operator_name' => 'required|string|max:255',
            'specials' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'nullable',
        ]);

        $specialsArr = [];
        if (!empty($validated['specials'])) {
            $specialsArr = array_values(array_filter(array_map('trim', explode(',', $validated['specials']))));
        }

        $pso->update([
            'code' => trim($validated['code']),
            'name' => $validated['name'],
            'prefix' => strtoupper(trim($validated['prefix'])),
            'start_no' => $validated['start_no'],
            'end_no' => $validated['end_no'],
            'specials' => $specialsArr,
            'operator_name' => $validated['operator_name'],
            'description' => $validated['description'],
            'is_active' => $request->has('is_active') ? (bool)$request->is_active : false,
        ]);

        AuditLog::log('PSO_CONFIG_UPDATE', "Updated PSO series {$pso->code} ({$pso->name}) range {$pso->prefix} {$pso->start_no}-{$pso->end_no}");

        return redirect()->route('admin.pso.index')->with('success', "PSO Configuration '{$pso->name}' ({$pso->code}) updated successfully.");
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
        $name = $pso->name;
        $pso->delete();

        AuditLog::log('PSO_CONFIG_DELETE', "Deleted PSO series {$code} ({$name})");

        return redirect()->route('admin.pso.index')->with('success', "PSO Configuration '{$name}' ({$code}) deleted successfully.");
    }
}
