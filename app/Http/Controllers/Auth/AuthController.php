<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function loginWeb(LoginRequest $request): RedirectResponse
    {
        $credentials = [
            'email'    => strtolower(trim($request->input('email'))),
            'password' => $request->input('password'),
        ];

        $remember = $request->boolean('remember');

        if (!Auth::attempt($credentials, $remember)) {
            return back()
                ->withErrors(['email' => 'Email atau password yang Anda masukkan salah.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['email' => 'Akun Anda sedang dinonaktifkan. Hubungi Administrator.'])
                ->onlyInput('email');
        }

        // Arahkan ke halaman pemberitahuan verifikasi TANPA mengirim ulang email otomatis
        if (!$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function showRegisterForm(): View
    {
        $branches = Branch::where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('auth.register', compact('branches'));
    }

    public function registerWeb(RegisterRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            $selectedBranch = Branch::where('is_active', true)
                ->where('id', $request->input('branch_id'))
                ->first()
                ?? Branch::where('is_active', true)->orderBy('id')->first();

            $newUser = User::create([
                'name'     => $request->input('name'),
                'email'    => strtolower(trim($request->input('email'))),
                'password' => Hash::make($request->input('password')),
            ]);

            $newUser->forceFill([
                'branch_id'   => $selectedBranch?->id,
                'is_active'   => true,
                'base_salary' => 0,
            ])->save();

            $newUser->assignSystemRole('staff');

            return $newUser;
        });

        // Panggil event Registered SATU kali saja agar tidak mengirim email verifikasi ganda
        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice')
            ->with('status', 'Registrasi berhasil! Silakan cek kotak masuk email Anda untuk tautan verifikasi.');
    }

    public function logoutWeb(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('status', 'Anda telah berhasil keluar dari sistem.');
    }
}
