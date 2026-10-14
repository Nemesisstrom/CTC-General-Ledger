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
        // Gunakan $request->validated() atau $request->only()
        $credentials = $request->validated();

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended('/dashboard')->with('success', 'Selamat datang kembali!');
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
            'role'        => $request->role,
            'base_salary' => $request->base_salary ?? 0,
        ]);

        // 2. Assign Role Spatie
        $user->assignRole($request->role);

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
                'role'        => $user->getRoleNames()->first() ?? null,
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
            'role'        => $request->role,
            'base_salary' => $request->base_salary ?? 0,
        ]);

        $user->assignRole($request->role);

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
}
