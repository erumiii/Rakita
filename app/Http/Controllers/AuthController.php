<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\NotificationService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        $user = User::whereRaw('LOWER(username) = ?', [strtolower($request->username)])->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return back()->withErrors(['username' => 'Incorrect username or password.'])->onlyInput('username');
        }

        if (! $user->is_active) {
            return back()->withErrors(['username' => 'This account has been deactivated.'])->onlyInput('username');
        }

        auth()->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect($user->role === 'employee' ? '/employee/books' : '/member/books');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'username' => strtolower($request->username),
            'password' => $request->password,
            'role' => 'member',
            'is_active' => true,
        ]);

        auth()->login($user);

        app(NotificationService::class)->notifyEmployees('member.registered', [
            'name' => $request->name,
            'link' => '/employee/members',
        ]);

        $request->session()->regenerate();

        return redirect('/member/books');
    }

    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
