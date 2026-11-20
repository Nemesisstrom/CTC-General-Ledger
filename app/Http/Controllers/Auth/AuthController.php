<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const REGISTRATION_ROLE = 'staff';
    private const REGISTRATION_BASE_SALARY = 0;

    // --- WEB UI METHODS ---
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function showRegisterForm()
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('auth.register', compact('branches'));
    }

    public function loginWeb(LoginRequest $request)
    {
        $credentials = $request->only(['email', 'password']);
        $selectedRole = strtolower($request->input('role', ''));

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            // 1. Cek status akun aktif
            if (! $user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw ValidationException::withMessages([
                    'email' => ['Akun Anda telah dinonaktifkan oleh Administrator.'],
                ]);
            }

            // 2. Blokir total jika email belum diverifikasi
            if (! $user->hasVerifiedEmail()) {
                $user->sendEmailVerificationNotification();

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw ValidationException::withMessages([
                    'email' => ['Email Anda belum diverifikasi. Link verifikasi baru telah dikirim ke email Anda, silakan klik link tersebut sebelum login.'],
                ]);
            }

            // 3. Cek apakah user memiliki role Spatie yang dipilih
            if (! empty($selectedRole) && ! $user->hasRole($selectedRole)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw ValidationException::withMessages([
                    'role' => ['Akun Anda tidak memiliki hak akses sebagai ' . ucfirst($selectedRole) . '.'],
                ]);
            }

            // 4. Sinkronkan cabang aktif otomatis dari profil user
            if ($user->branch_id) {
                session(['active_branch_id' => $user->branch_id]);
            } else {
                session()->forget('active_branch_id');
            }

            // Simpan role aktif di session
            if (! empty($selectedRole)) {
                session(['active_role' => $selectedRole]);
            } else {
                session(['active_role' => $user->getRoleNames()->first() ?? $user->role]);
            }

            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'))->with('success', 'Selamat datang kembali!');
        }

        throw ValidationException::withMessages([
            'email' => __('Email atau password salah.'),
        ]);
    }

    public function registerWeb(RegisterRequest $request)
    {
        $user = User::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'branch_id'   => $request->branch_id ?: null,
            'password'    => Hash::make($request->password),
            'role'        => self::REGISTRATION_ROLE,
            'base_salary' => self::REGISTRATION_BASE_SALARY,
            'is_active'   => true,
        ]);

        $user->assignRole(self::REGISTRATION_ROLE);

        // Kirim email verifikasi tanpa membuat sesi login
        event(new Registered($user));

        return redirect()->route('login')
            ->with('status', 'Registrasi berhasil! Silakan buka email Anda dan klik link verifikasi sebelum login.');
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

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Email atau password salah'], 401);
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email belum diverifikasi.'], 403);
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
                'branch_id'   => $user->branch_id,
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
            'branch_id'   => $request->branch_id ?: null,
            'password'    => Hash::make($request->password),
            'role'        => self::REGISTRATION_ROLE,
            'base_salary' => self::REGISTRATION_BASE_SALARY,
            'is_active'   => true,
        ]);

        $user->assignRole(self::REGISTRATION_ROLE);

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
