<?php

namespace App\Http\Controllers;

use App\Actions\Admin\Employees\CreateEmployee;
use App\Actions\Admin\Employees\IssueEmployeeActivation;
use App\Actions\Admin\Employees\RevealTemporaryPassword;
use App\Actions\Admin\Employees\RotateTemporaryPassword;
use App\Actions\Admin\Employees\UpdateEmployee;
use App\Models\Employee;
use App\Models\Title;
use App\Services\EmployeeNumberGenerator;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class AdminEmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $employees = Employee::query()->with(['user.accountCredential', 'user.accountActivation', 'title'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = $request->string('q')->toString();
                $query->where(fn ($q) => $q
                    ->where('employee_number', 'like', "%{$term}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")));
            })
            ->when($request->status === 'active', fn ($query) => $query->whereHas('user', fn ($u) => $u->where('is_active', true)->where('must_change_password', false)))
            ->when($request->status === 'pending', fn ($query) => $query->whereHas('user', fn ($u) => $u->where('is_active', true)->where('must_change_password', true)))
            ->when($request->status === 'inactive', fn ($query) => $query->whereHas('user', fn ($u) => $u->where('is_active', false)))
            ->orderBy('employee_number')
            ->paginate(20)->withQueryString();

        return view('pages::admin.employees.index', compact('employees'));
    }

    public function create(EmployeeNumberGenerator $employeeNumberGenerator): View
    {
        return view('pages::admin.employees.create', [
            'titles' => Title::query()->where('is_active', true)->orderBy('name')->get(),
            'employeeNumberPreview' => $employeeNumberGenerator->preview(),
        ]);
    }

    public function store(Request $request, CreateEmployee $createEmployee, IssueEmployeeActivation $issueActivation): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'title_id' => ['nullable', 'integer', 'exists:titles,id'],
            'joined_at' => ['nullable', 'date'],
        ]);

        $employee = $createEmployee->handle($data);
        $sent = $issueActivation->handle($employee->user);

        if (! $sent) {
            return redirect()->route('admin.employees.edit', $employee)
                ->withErrors(['email' => 'Pegawai dibuat, tetapi email aktivasi gagal dikirim. Salin password sementara dan sampaikan melalui saluran aman.']);
        }

        return redirect()->route('admin.employees.edit', $employee)
            ->with('status', 'Pegawai dibuat dan tautan aktivasi telah dikirim. Password sementara tersedia melalui tindakan khusus admin.');
    }

    public function edit(Employee $employee): View
    {
        $employee->load(['user.accountCredential', 'user.accountActivation', 'title']);

        return view('pages::admin.employees.edit', [
            'employee' => $employee,
            'titles' => Title::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(
        Request $request,
        Employee $employee,
        UpdateEmployee $updateEmployee,
        IssueEmployeeActivation $issueActivation,
    ): RedirectResponse {

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employee->user_id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'title_id' => ['nullable', 'integer', 'exists:titles,id'],
            'joined_at' => ['nullable', 'date'],
            'is_active' => ['required', 'boolean'],
        ]);

        $emailChanged = $employee->user->email !== $data['email'];
        $employee = $updateEmployee->handle($employee, $data);

        if ($emailChanged && $employee->user->is_active && $employee->user->must_change_password) {
            $sent = $issueActivation->handle($employee->user);

            if (! $sent) {
                return redirect()->route('admin.employees.edit', $employee)
                    ->withErrors(['email' => 'Data diperbarui, tetapi email aktivasi gagal dikirim. Salin password sementara untuk onboarding manual.']);
            }

            return redirect()->route('admin.employees.edit', $employee)
                ->with('status', 'Data pegawai diperbarui dan tautan aktivasi baru dikirim ke email terbaru.');
        }

        if ($emailChanged && $employee->user->is_active) {
            try {
                $employee->user->notify(new VerifyEmail);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return redirect()->route('admin.employees.edit', $employee)->with('status', 'Data pegawai diperbarui.');
    }

    public function revealTemporaryPassword(Employee $employee, RevealTemporaryPassword $revealPassword): JsonResponse
    {
        $password = $revealPassword->handle($employee);

        return response()->json(['password' => $password])
            ->header('Cache-Control', 'no-store, private')
            ->header('Pragma', 'no-cache');
    }

    public function rotateTemporaryPassword(Employee $employee, RotateTemporaryPassword $rotatePassword, IssueEmployeeActivation $issueActivation): RedirectResponse
    {
        $rotatePassword->handle($employee);
        $sent = $issueActivation->handle($employee->user);

        if (! $sent) {
            return back()->withErrors(['email' => 'Password sementara dirotasi, tetapi email aktivasi gagal dikirim. Salin password baru melalui tindakan admin.']);
        }

        return back()->with('status', 'Password sementara dirotasi dan tautan aktivasi baru dikirim.');
    }

    public function resendActivation(Employee $employee, IssueEmployeeActivation $issueActivation): RedirectResponse
    {
        $sent = $issueActivation->handle($employee->user);

        if (! $sent) {
            return back()->withErrors(['email' => 'Email aktivasi gagal dikirim. Password sementara tetap dapat disalin oleh admin.']);
        }

        return back()->with('status', 'Tautan aktivasi baru telah dikirim. Tautan sebelumnya tidak lagi berlaku.');
    }
}
