<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Prefix;
use App\Models\PsoConfig;
use App\Models\Salesperson;
use Illuminate\Http\Request;

class PrefixMasterController extends Controller
{
    /**
     * Display listing of all prefixes.
     */
    public function index()
    {
        $prefixes = Prefix::with('salespersons')->orderBy('code')->get();
        $salespersons = Salesperson::where('is_active', true)->orderBy('name')->get();

        return view('prefix.index', compact('prefixes', 'salespersons'));
    }

    /**
     * Store a newly created prefix.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'prefix'         => 'required|string|max:10|unique:prefixes,prefix',
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string|max:500',
            'salesperson_id' => 'nullable|integer|exists:salespersons,id',
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

        // If a salesperson was selected, link this prefix on the salesperson record
        if (!empty($validated['salesperson_id'])) {
            $sp = Salesperson::find($validated['salesperson_id']);
            if ($sp) {
                $sp->update([
                    'prefix_id'   => $prefix->id,
                    'prefix_code' => $prefix->prefix,
                ]);
            }
        }

        AuditLog::log('PREFIX_CREATE', "Created prefix master entry {$code} — {$prefix->prefix} ({$prefix->name})");

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'prefix' => $prefix,
                'message' => "Prefix '{$prefix->prefix}' ({$prefix->name}) created successfully."
            ]);
        }

        return redirect()->back()->with('success', "Prefix '{$prefix->prefix}' ({$prefix->name}) created successfully.");
    }

    /**
     * Update an existing prefix.
     */
    public function update(Request $request, $id)
    {
        $prefix = Prefix::findOrFail($id);

        $validated = $request->validate([
            'prefix'         => 'required|string|max:10|unique:prefixes,prefix,' . $id,
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string|max:500',
            'salesperson_id' => 'nullable|integer|exists:salespersons,id',
        ]);

        $oldPrefix = $prefix->prefix;
        $newPrefix = strtoupper(trim($validated['prefix']));

        $prefix->update([
            'prefix'      => $newPrefix,
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        // Synchronize salesperson link (prefix_id on salespersons table)
        if (array_key_exists('salesperson_id', $validated)) {
            $newSpId = $validated['salesperson_id'];

            // Clear previous salespersons linked to this prefix if a different one is selected or cleared
            Salesperson::where('prefix_id', $prefix->id)
                ->where('id', '!=', $newSpId ?? 0)
                ->update([
                    'prefix_id'   => null,
                    'prefix_code' => null,
                ]);

            // Assign to newly selected salesperson
            if (!empty($newSpId)) {
                Salesperson::where('id', $newSpId)->update([
                    'prefix_id'   => $prefix->id,
                    'prefix_code' => $newPrefix,
                ]);
            }
        }

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

        // Unlink any salespersons pointing to this prefix
        Salesperson::where('prefix_id', $prefix->id)->update([
            'prefix_id'   => null,
            'prefix_code' => null,
        ]);

        $code = $prefix->code;
        $prefixStr = $prefix->prefix;
        $prefix->delete();

        AuditLog::log('PREFIX_DELETE', "Deleted prefix master entry {$code} ({$prefixStr})");

        return redirect()->back()->with('success', "Prefix '{$prefixStr}' deleted successfully.");
    }
}
