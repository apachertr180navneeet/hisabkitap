<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Prefix;
use App\Models\Salesperson;
use Illuminate\Http\Request;

class SalespersonController extends Controller
{
    /**
     * Display a listing of all salespersons.
     */
    public function index()
    {
        $salespersons = Salesperson::with('prefix')->orderBy('code')->get();
        $allPrefixes = Prefix::where('is_active', true)->orderBy('prefix')->get();

        $stats = [
            'total'           => $salespersons->count(),
            'active'          => $salespersons->where('is_active', true)->count(),
            'inactive'        => $salespersons->where('is_active', false)->count(),
            'coveredPrefixes' => $salespersons->whereNotNull('prefix_id')->count(),
        ];

        return view('salesperson.index', compact('salespersons', 'allPrefixes', 'stats'));
    }

    /**
     * Store a newly created salesperson in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'prefix_id' => 'nullable|integer|exists:prefixes,id',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'nullable|email|max:255',
            'area'      => 'nullable|string|max:255',
        ]);

        $nextNum = (Salesperson::max('id') ?? 0) + 1;
        $code = 'SP-' . sprintf('%02d', $nextNum);

        $prefixCode = null;
        if (!empty($validated['prefix_id'])) {
            $pfx = Prefix::find($validated['prefix_id']);
            $prefixCode = $pfx ? $pfx->prefix : null;
        }

        $salesperson = Salesperson::create([
            'code'        => $code,
            'name'        => trim($validated['name']),
            'prefix_id'   => $validated['prefix_id'] ?? null,
            'prefix_code' => $prefixCode,
            'phone'       => $validated['phone'] ? trim($validated['phone']) : null,
            'email'       => $validated['email'] ? trim($validated['email']) : null,
            'area'        => $validated['area'] ? trim($validated['area']) : null,
            'is_active'   => true,
        ]);

        $pfxLog = $prefixCode ? " (Linked to Prefix: {$prefixCode})" : '';
        AuditLog::log('SALESPERSON_CREATE', "Created salesperson {$code} ({$salesperson->name}){$pfxLog}");

        return redirect()->back()->with('success', "Sales Person '{$salesperson->name}' ({$code}) created successfully.");
    }

    /**
     * Update the specified salesperson in storage.
     */
    public function update(Request $request, $id)
    {
        $salesperson = Salesperson::findOrFail($id);

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'prefix_id' => 'nullable|integer|exists:prefixes,id',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'nullable|email|max:255',
            'area'      => 'nullable|string|max:255',
        ]);

        $prefixCode = null;
        if (!empty($validated['prefix_id'])) {
            $pfx = Prefix::find($validated['prefix_id']);
            $prefixCode = $pfx ? $pfx->prefix : null;
        }

        $newName = trim($validated['name']);

        $salesperson->update([
            'name'        => $newName,
            'prefix_id'   => $validated['prefix_id'] ?? null,
            'prefix_code' => $prefixCode,
            'phone'       => $validated['phone'] ? trim($validated['phone']) : null,
            'email'       => $validated['email'] ? trim($validated['email']) : null,
            'area'        => $validated['area'] ? trim($validated['area']) : null,
        ]);

        $pfxLog = $prefixCode ? " [Linked Prefix: {$prefixCode}]" : ' [No Prefix Linked]';
        AuditLog::log('SALESPERSON_UPDATE', "Updated salesperson {$salesperson->code} ({$newName}){$pfxLog}");

        return redirect()->back()->with('success', "Sales Person '{$newName}' updated successfully.");
    }

    /**
     * Toggle active status of the salesperson.
     */
    public function toggleStatus($id)
    {
        $salesperson = Salesperson::findOrFail($id);
        $salesperson->is_active = !$salesperson->is_active;
        $salesperson->save();

        AuditLog::log('SALESPERSON_STATUS_TOGGLE', "Toggled salesperson {$salesperson->code} ({$salesperson->name}) status to " . ($salesperson->is_active ? 'Active' : 'Inactive'));

        return redirect()->back()->with('success', "Sales Person '{$salesperson->name}' status updated to " . ($salesperson->is_active ? 'Active' : 'Inactive') . ".");
    }

    /**
     * Remove the specified salesperson from storage.
     */
    public function destroy($id)
    {
        $salesperson = Salesperson::findOrFail($id);

        $code = $salesperson->code;
        $name = $salesperson->name;
        $salesperson->delete();

        AuditLog::log('SALESPERSON_DELETE', "Deleted salesperson {$code} ({$name})");

        return redirect()->back()->with('success', "Sales Person '{$name}' deleted successfully.");
    }
}
