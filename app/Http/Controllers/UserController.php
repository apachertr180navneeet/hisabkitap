<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\AuditLog;

class UserController extends Controller
{
    /**
     * Display User & Role Management interface
     */
    public function index()
    {
        $users = User::orderBy('id', 'asc')->get();
        $roles = User::getPredefinedRoles();

        $stats = [
            'totalUsers' => $users->count(),
            'activeUsers' => $users->where('is_active', true)->count(),
            'superAdmins' => $users->filter(fn($u) => $u->isSuperAdmin())->count(),
            'operators' => $users->where('role_code', 'OPERATOR')->count(),
            'approvers' => $users->where('role_code', 'APPROVER')->count(),
        ];

        return view('users.index', compact('users', 'roles', 'stats'));
    }

    /**
     * Store new User with selected Role & Permissions
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role_code' => 'required|string|in:SUPER_ADMIN,OPERATOR,APPROVER',
        ]);

        $roles = User::getPredefinedRoles();
        $roleInfo = $roles[$validated['role_code']] ?? $roles['OPERATOR'];

        // Get custom permission overrides or fallback to role defaults
        $isSuperAdmin = ($validated['role_code'] === 'SUPER_ADMIN');
        
        $canEditBills = $isSuperAdmin ? true : $request->boolean('can_edit_bills', $roleInfo['default_permissions']['can_edit_bills']);
        $canImportExcel = $isSuperAdmin ? true : $request->boolean('can_import_excel', $roleInfo['default_permissions']['can_import_excel']);
        $canRecordCorrections = $isSuperAdmin ? true : $request->boolean('can_record_corrections', $roleInfo['default_permissions']['can_record_corrections']);
        $canRecordCredit = $isSuperAdmin ? true : $request->boolean('can_record_credit', $roleInfo['default_permissions']['can_record_credit']);
        $canApproveSealing = $isSuperAdmin ? true : $request->boolean('can_approve_sealing', $roleInfo['default_permissions']['can_approve_sealing']);
        $canConfigurePso = $isSuperAdmin ? true : $request->boolean('can_configure_pso', $roleInfo['default_permissions']['can_configure_pso']);
        $canEditCutoff = $isSuperAdmin ? true : $request->boolean('can_edit_cutoff', $roleInfo['default_permissions']['can_edit_cutoff']);
        $canManageUsers = $isSuperAdmin ? true : $request->boolean('can_manage_users', $roleInfo['default_permissions']['can_manage_users']);

        // Generate avatar initials
        $words = explode(' ', trim($validated['name']));
        $avatar = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));

        $user = User::create([
            'code' => 'usr_' . strtolower(substr(uniqid(), -4)),
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_name' => $roleInfo['role_name'],
            'role_code' => $roleInfo['role_code'],
            'badge_color' => $roleInfo['badge_color'],
            'badge_class' => $roleInfo['badge_class'],
            'avatar' => $avatar ?: 'US',
            'icon' => $roleInfo['icon'],
            'title' => $roleInfo['title'],
            'tagline' => $roleInfo['tagline'],
            'can_edit_bills' => $canEditBills,
            'can_import_excel' => $canImportExcel,
            'can_record_corrections' => $canRecordCorrections,
            'can_record_credit' => $canRecordCredit,
            'can_approve_sealing' => $canApproveSealing,
            'can_configure_pso' => $canConfigurePso,
            'can_edit_cutoff' => $canEditCutoff,
            'can_manage_users' => $canManageUsers,
            'is_active' => true,
            'is_read_only' => false,
            'allowed_modules' => $roleInfo['allowed_modules'],
        ]);

        AuditLog::log('USER_CREATE', "Created user {$user->name} ({$user->email}) with role {$user->role_name}.");

        return redirect()->route('admin.users.index')->with('success', "User '{$user->name}' created successfully with role '{$user->role_name}'.");
    }

    /**
     * Update existing User details, role, and permissions
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role_code' => 'required|string|in:SUPER_ADMIN,OPERATOR,APPROVER',
        ]);

        $roles = User::getPredefinedRoles();
        $roleInfo = $roles[$validated['role_code']] ?? $roles['OPERATOR'];
        $isSuperAdmin = in_array($validated['role_code'], ['SUPER_ADMIN', 'ADMIN']);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role_name = $roleInfo['role_name'];
        $user->role_code = $roleInfo['role_code'];
        $user->badge_color = $roleInfo['badge_color'];
        $user->badge_class = $roleInfo['badge_class'];
        $user->icon = $roleInfo['icon'];
        $user->title = $roleInfo['title'];
        $user->tagline = $roleInfo['tagline'];

        // Assign permissions
        $user->can_edit_bills = $isSuperAdmin ? true : $request->boolean('can_edit_bills');
        $user->can_import_excel = $isSuperAdmin ? true : $request->boolean('can_import_excel');
        $user->can_record_corrections = $isSuperAdmin ? true : $request->boolean('can_record_corrections');
        $user->can_record_credit = $isSuperAdmin ? true : $request->boolean('can_record_credit');
        $user->can_approve_sealing = $isSuperAdmin ? true : $request->boolean('can_approve_sealing');
        $user->can_configure_pso = $isSuperAdmin ? true : $request->boolean('can_configure_pso');
        $user->can_edit_cutoff = $isSuperAdmin ? true : $request->boolean('can_edit_cutoff');
        $user->can_manage_users = $isSuperAdmin ? true : $request->boolean('can_manage_users');

        $user->save();

        AuditLog::log('USER_UPDATE', "Updated user {$user->name} ({$user->email}) and permissions.");

        return redirect()->route('admin.users.index')->with('success', "User '{$user->name}' updated successfully.");
    }

    /**
     * Admin password reset for a user
     */
    public function changePassword(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user->password = Hash::make($request->new_password);
        $user->save();

        AuditLog::log('USER_PASSWORD_RESET', "Password reset by admin for {$user->name}.");

        return redirect()->route('admin.users.index')->with('success', "Password for {$user->name} was reset successfully.");
    }

    /**
     * Toggle active status of a user
     */
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot deactivate your own active account.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $statusText = $user->is_active ? 'activated' : 'deactivated';
        AuditLog::log('USER_STATUS_TOGGLE', "User {$user->name} {$statusText}.");

        return redirect()->route('admin.users.index')->with('success', "User '{$user->name}' has been {$statusText}.");
    }

    /**
     * Delete user
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot delete your own account.');
        }

        if ($user->isSuperAdmin() && User::whereIn('role_code', ['SUPER_ADMIN', 'ADMIN'])->count() <= 1) {
            return redirect()->route('admin.users.index')->with('error', 'Cannot delete the only Super Administrator account.');
        }

        $userName = $user->name;
        $user->delete();

        AuditLog::log('USER_DELETE', "Deleted user account {$userName}.");

        return redirect()->route('admin.users.index')->with('success', "User '{$userName}' deleted successfully.");
    }
}
