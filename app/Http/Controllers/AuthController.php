<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return Auth::user()->role === 'admin'
                ? redirect()->route('report')
                : redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'staff_id' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = [
            'staff_id' => strtoupper(trim((string) $request->staff_id)),
            'password' => (string) $request->password,
        ];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $targetRoute = Auth::user()->role === 'admin' ? route('report') : route('dashboard');

            return redirect()->intended($targetRoute);
        }

        return back()->withErrors([
            'staff_id' => 'Staff ID atau kata laluan tidak sah',
        ])->onlyInput('staff_id');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
