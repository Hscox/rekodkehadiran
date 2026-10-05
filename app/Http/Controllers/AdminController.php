<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function createStaffForm()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses tidak dibenarkan.');
        }

        $staffs = User::where('role', 'staff')->orderBy('created_at', 'desc')->get();

        return view('admin.create-staff', compact('staffs'));
    }

    public function storeStaff(Request $request)
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

        return back()->with('success', 'Akaun staf berjaya didaftarkan.');
    }

    public function deleteStaff(int $id)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses tidak dibenarkan.');
        }

        $staff = User::where('role', 'staff')->findOrFail($id);

        $staff->delete();

        return back()->with('success', 'Akaun staf berjaya dipadam.');
    }
}
