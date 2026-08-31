<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PsoConfig;
use App\Models\AuditLog;

class PsoManagementController extends Controller
{
    public function index()
    {
        $psoList = PsoConfig::all();
        return view('pso.index', compact('psoList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prefix' => 'required|string|max:10',
            'start_no' => 'required|integer|min:1',
            'end_no' => 'required|integer|gte:start_no',
            'operator_name' => 'required|string|max:255',
            'specials' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $nextNum = PsoConfig::count() + 1;
        $code = 'PSO-' . $nextNum;

        $specialsArr = [];
        if (!empty($validated['specials'])) {
            $specialsArr = array_filter(array_map('trim', explode(',', $validated['specials'])));
        }

        $pso = PsoConfig::create([
            'code' => $code,
            'name' => $validated['name'],
            'prefix' => strtoupper($validated['prefix']),
            'start_no' => $validated['start_no'],
            'end_no' => $validated['end_no'],
            'specials' => $specialsArr,
            'operator_name' => $validated['operator_name'],
            'description' => $validated['description'] ?? ("Counter Bills " . strtoupper($validated['prefix']) . " {$validated['start_no']} to {$validated['end_no']}"),
            'is_active' => true,
        ]);

        AuditLog::log('PSO_CONFIG_CREATE', "Created PSO series {$code} ({$pso->name}) range {$pso->prefix} {$pso->start_no}-{$pso->end_no}");

        return redirect()->back()->with('success', "PSO Configuration '{$pso->name}' successfully created.");
    }

    public function toggleStatus($id)
    {
        $pso = PsoConfig::findOrFail($id);
        $pso->is_active = !$pso->is_active;
        $pso->save();

        AuditLog::log('PSO_STATUS_TOGGLE', "Toggled active status of {$pso->code} to " . ($pso->is_active ? 'Active' : 'Inactive'));

        return redirect()->back()->with('success', "PSO {$pso->code} status updated.");
    }
}
