<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Prefix;
use App\Models\PsoConfig;
use Illuminate\Http\Request;

class PrefixMasterController extends Controller
{
    /**
     * Display listing of all prefixes.
     */
    public function index()
    {
        $prefixes = Prefix::orderBy('code')->get();

        return view('prefix.index', compact('prefixes'));
    }

    /**
     * Store a newly created prefix.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'prefix' => 'required|string|max:10|unique:prefixes,prefix',
            'name'   => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $nextNum = (Prefix::max('id') ?? 0) + 1;
        $code = 'PFX-' . $nextNum;

        $prefix = Prefix::create([
            'code'        => $code,
            'prefix'      => strtoupper(trim($validated['prefix'])),
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active'   => true,
        ]);

        AuditLog::log('PREFIX_CREATE', "Created prefix master entry {$code} — {$prefix->prefix} ({$prefix->name})");

        return redirect()->back()->with('success', "Prefix '{$prefix->prefix}' ({$prefix->name}) created successfully.");
    }

    /**
     * Update an existing prefix.
     */
    public function update(Request $request, $id)
    {
        $prefix = Prefix::findOrFail($id);

        $validated = $request->validate([
            'prefix'      => 'required|string|max:10|unique:prefixes,prefix,' . $id,
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $oldPrefix = $prefix->prefix;

        $prefix->update([
            'prefix'      => strtoupper(trim($validated['prefix'])),
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        AuditLog::log('PREFIX_UPDATE', "Updated prefix {$prefix->code}: '{$oldPrefix}' → '{$prefix->prefix}' ({$prefix->name})");

        return redirect()->back()->with('success', "Prefix '{$prefix->prefix}' updated successfully.");
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleStatus($id)
    {
        $prefix = Prefix::findOrFail($id);
        $prefix->is_active = !$prefix->is_active;
        $prefix->save();

        AuditLog::log('PREFIX_STATUS_TOGGLE', "Toggled prefix {$prefix->code} ({$prefix->prefix}) to " . ($prefix->is_active ? 'Active' : 'Inactive'));

        return redirect()->back()->with('success', "Prefix '{$prefix->prefix}' status updated to " . ($prefix->is_active ? 'Active' : 'Inactive') . ".");
    }

    /**
     * Delete a prefix (only if not used by any PSO config).
     */
    public function destroy($id)
    {
        $prefix = Prefix::findOrFail($id);

        // Safety check: prevent deletion if prefix is used by any PSO config
        $usedByPso = PsoConfig::where('prefix', $prefix->prefix)->exists();
        if ($usedByPso) {
            return redirect()->back()->with('error', "Cannot delete prefix '{$prefix->prefix}' — it is currently assigned to one or more PSO configurations.");
        }

        $code = $prefix->code;
        $prefixStr = $prefix->prefix;
        $prefix->delete();

        AuditLog::log('PREFIX_DELETE', "Deleted prefix master entry {$code} ({$prefixStr})");

        return redirect()->back()->with('success', "Prefix '{$prefixStr}' deleted successfully.");
    }
}
