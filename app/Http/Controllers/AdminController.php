<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function staffList(Request $request): View
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses tidak dibenarkan.');
        }

        $status = $request->get('status', 'active');
        $query = User::where('role', 'staff');

        if ($status === 'trashed') {
            $staffs = User::onlyTrashed()->where('role', 'staff')->orderBy('deleted_at', 'desc')->paginate(10);
        } else {
            $staffs = User::where('role', 'staff')->orderBy('created_at', 'desc')->paginate(10);
        }

        $activeCount = User::where('role', 'staff')->count();
        $trashedCount = User::onlyTrashed()->where('role', 'staff')->count();

        return view('admin.staff-list', compact('staffs', 'status', 'activeCount', 'trashedCount'));
    }

    public function createStaffForm(): View
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses tidak dibenarkan.');
        }

        return view('admin.create-staff');
    }

    public function storeStaff(Request $request): RedirectResponse
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses tidak dibenarkan.');
        }

        $request->validate([
            'staff_id' => 'required|string|max:50|unique:users,staff_id',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ]);

        User::create([
            'staff_id' => $request->staff_id,
            'name' => $request->name,
            'password' => Hash::make($request->password),
            'role' => 'staff',
        ]);

        return redirect()->route('admin.staffList')->with('success', 'Akaun staf berjaya didaftarkan.');
    }

    public function editStaffForm(int $id): View
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses tidak dibenarkan.');
        }

        $staff = User::where('role', 'staff')->findOrFail($id);

        return view('admin.edit-staff', compact('staff'));
    }

    public function updateStaff(Request $request, int $id): RedirectResponse
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses tidak dibenarkan.');
        }

        $staff = User::where('role', 'staff')->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'nullable|string|min:6',
        ]);

        $updateData = [
            'name' => $request->name,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $staff->update($updateData);

        return redirect()->route('admin.staffList')->with('success', 'Maklumat staf berjaya dikemaskini.');
    }

    public function deleteStaff(int $id): RedirectResponse
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses tidak dibenarkan.');
        }

        $staff = User::where('role', 'staff')->findOrFail($id);

        $staff->delete();

        return redirect()->route('admin.staffList')->with('success', 'Akaun staf berjaya dipadam.');
    }

    public function restoreStaff(int $id): RedirectResponse
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses tidak dibenarkan.');
        }

        $staff = User::onlyTrashed()->where('role', 'staff')->findOrFail($id);
        $staff->restore();

        return redirect()->route('admin.staffList', ['status' => 'trashed'])->with('success', 'Akaun staf berjaya dipulihkan.');
    }

    public function forceDeleteStaff(int $id): RedirectResponse
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses tidak dibenarkan.');
        }

        $staff = User::onlyTrashed()->where('role', 'staff')->findOrFail($id);
        $staff->forceDelete();

        return redirect()->route('admin.staffList', ['status' => 'trashed'])->with('success', 'Akaun staf berjaya dipadam secara kekal.');
    }

    public function appealsList(): View
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses tidak dibenarkan.');
        }

        $appeals = Attendance::with('user')
            ->where('status', 'pending_approval')
            ->orderBy('date', 'desc')
            ->paginate(10, ['*'], 'pending_page');

        $pastAppeals = Attendance::with(['user', 'approver'])
            ->whereIn('status', ['approved', 'rejected'])
            ->orderBy('updated_at', 'desc')
            ->paginate(10, ['*'], 'history_page');

        return view('admin.appeals', compact('appeals', 'pastAppeals'));
    }

    // admin approve alasan tubik awal {#508,2}
    public function approveAppeal(int $id): RedirectResponse
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses tidak dibenarkan.');
        }

        $attendance = Attendance::where('status', 'pending_approval')->findOrFail($id);

        $proposedTime = $attendance->proposed_clock_out ?? '16:00:00';

        $attendance->update([
            'clock_out' => $proposedTime,
            'status' => 'approved',
            'approved_by' => Auth::id(),
        ]);

        return back()->with('success', 'Permohonan pengesahan keluar berjaya diluluskan.');
    }

    public function rejectAppeal(int $id): RedirectResponse
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses tidak dibenarkan.');
        }

        $attendance = Attendance::where('status', 'pending_approval')->findOrFail($id);

        $attendance->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
        ]);

        return back()->with('success', 'Permohonan pengesahan keluar telah ditolak.');
    }
}
