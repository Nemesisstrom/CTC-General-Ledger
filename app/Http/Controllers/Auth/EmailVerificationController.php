<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * Tampilkan halaman pemberitahuan bahwa email belum diverifikasi
     */
    public function notice()
    {
        return view('auth.verify-email');
    }

    /**
     * Tangani proses verifikasi saat link email diklik
     */
    public function verify(EmailVerificationRequest $request)
    {
        $request->fulfill();

        return redirect()->route('dashboard')
            ->with('success', 'Email Anda berhasil diverifikasi!');
    }

    /**
     * Pengiriman ulang link verifikasi email
     */
    public function resend(Request $request)
    {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Link verifikasi baru telah dikirimkan ke email Anda!');
    }
}
