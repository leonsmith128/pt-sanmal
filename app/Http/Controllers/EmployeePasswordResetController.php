<?php

namespace App\Http\Controllers;

use App\Actions\CompleteEmployeePasswordReset;
use App\Actions\RequestEmployeePasswordReset;
use App\Models\PasswordResetRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class EmployeePasswordResetController extends Controller
{
    public function createRequest(): View
    {
        return view('pages::auth.request-password-reset');
    }

    public function storeRequest(Request $request, RequestEmployeePasswordReset $requestPasswordReset): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $requestPasswordReset->handle($data['email']);

        return back()->with('status', 'Jika email terdaftar sebagai akun pegawai aktif, permintaan reset akan diteruskan kepada administrator.');
    }

    public function show(string $token): View
    {
        $resetRequest = PasswordResetRequest::query()
            ->where('token_hash', hash('sha256', $token))
            ->first();

        abort_unless(
            $resetRequest
                && $resetRequest->status === 'approved'
                && $resetRequest->used_at === null
                && $resetRequest->expires_at?->isFuture()
                && $resetRequest->user()->where('is_active', true)->where('must_change_password', false)->exists(),
            404,
        );

        return view('pages::auth.reset-approved-password', compact('token'));
    }

    public function store(string $token, Request $request, CompleteEmployeePasswordReset $completePasswordReset): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $completePasswordReset->handle($token, $data['password']);

        return redirect()->route('login')->with('status', 'Password berhasil diperbarui. Silakan masuk kembali.');
    }
}
