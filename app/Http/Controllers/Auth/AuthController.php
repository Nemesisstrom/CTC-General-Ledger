<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // --- WEB UI METHODS ---
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function loginWeb(LoginRequest $request)
    {
        $credentials = $request->only(['email', 'password']);
        $selectedRole = strtolower($request->input('role', ''));

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            // Cek apakah user memiliki role Spatie yang dipilih
            if (!empty($selectedRole) && !$user->hasRole($selectedRole)) {
                Auth::logout();
                $request->session()->invalidate();
                
                throw ValidationException::withMessages([
                    'role' => ['Akun Anda tidak memiliki hak akses sebagai ' . ucfirst($selectedRole) . '.'],
                ]);
            }

            // Simpan role aktif di session
            if (!empty($selectedRole)) {
                session(['active_role' => $selectedRole]);
            } else {
                session(['active_role' => $user->getRoleNames()->first() ?? $user->role]);
            }

            $request->session()->regenerate();

            return redirect()->route('dashboard')->with('success', 'Selamat datang kembali!');
        }

        throw ValidationException::withMessages([
            'email' => __('Email atau password salah.'),
        ]);
    }

    public function registerWeb(RegisterRequest $request)
    {
        // 1. Buat User Baru
        $user = User::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'role'        => strtolower($request->role),
            'base_salary' => $request->base_salary ?? 0,
            'is_active'   => true,
        ]);

        // 2. Assign Role Spatie
        $user->assignRole(strtolower($request->role));

        // 3. Trigger event pengiriman email verifikasi
        event(new Registered($user));

        // 4. Otomatis Login User & Regenerasi Session
        Auth::login($user);
        $request->session()->regenerate();

        // 5. Redirect ke Halaman Pemberitahuan Verifikasi Email
        return redirect()->route('verification.notice')
            ->with('success', 'Registrasi berhasil! Silakan cek email Anda untuk verifikasi akun.');
    }

    public function logoutWeb(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Berhasil logout.');
    }

    // --- API METHODS ---
    public function loginApi(LoginRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Email atau password salah'], 401);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'role'        => $user->getRoleNames()->first() ?? $user->role,
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ]
        ]);
    }

    public function registerApi(RegisterRequest $request)
    {
        $user = User::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'role'        => strtolower($request->role),
            'base_salary' => $request->base_salary ?? 0,
            'is_active'   => true,
        ]);

        $user->assignRole(strtolower($request->role));

        // Send Email Verification via API
        event(new Registered($user));

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'Registrasi berhasil. Silakan cek email untuk verifikasi.',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user
        ], 201);
    }

    public function logoutApi(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Berhasil logout dan menghapus token API.'
        ]);
    }
}