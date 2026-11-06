<?php

namespace App\Http\Controllers;

use App\Actions\CompleteFirstPasswordChange;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class FirstPasswordChangeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->user()->must_change_password) {
            return redirect()->route('dashboard');
        }

        return view('pages::auth.first-password-change');
    }

    public function store(Request $request, CompleteFirstPasswordChange $completePasswordChange): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $completePasswordChange->handle($request->user(), $data['password']);

        // Re-authenticate with the new password hash so auth.session keeps this device
        // signed in while rejecting sessions that still carry the previous hash.
        Auth::login($request->user()->fresh());

        return redirect()->route('dashboard')->with('status', 'Password berhasil diperbarui.');
    }
}
