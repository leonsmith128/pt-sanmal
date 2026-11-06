<?php

namespace App\Http\Controllers;

use App\Actions\CompleteEmployeeActivation;
use App\Models\EmployeeAccountActivation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class EmployeeAccountActivationController extends Controller
{
    public function show(string $token): View
    {
        $activation = EmployeeAccountActivation::query()
            ->with('user')
            ->where('token_hash', hash('sha256', $token))
            ->first();

        abort_unless(
            $activation && $activation->isUsable() && $activation->user->is_active
                && $activation->user->must_change_password,
            404,
        );

        return view('pages::auth.activate-account', compact('token'));
    }

    public function store(Request $request, string $token, CompleteEmployeeActivation $completeActivation): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $completeActivation->handle($token, $data['password']);

        return redirect()->route('login')->with('status', 'Akun berhasil diaktifkan. Silakan masuk dengan password baru.');
    }
}
