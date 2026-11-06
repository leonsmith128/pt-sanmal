<?php

namespace App\Http\Controllers;

use App\Actions\Admin\Employees\CreateEmployee;
use App\Actions\Admin\Employees\IssueEmployeeActivation;
use App\Actions\Admin\Employees\RevealTemporaryPassword;
use App\Actions\Admin\Employees\RotateTemporaryPassword;
use App\Actions\Admin\Employees\UpdateEmployee;
use App\Enums\AdminPosition;
use App\Enums\EmployeePosition;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Notifications\AccountSecurityNotification;
use App\Services\EmployeeNumberGenerator;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class AdminEmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $employees = Employee::query()->with(['user.accountCredential', 'user.accountActivation'])
            ->when(auth()->user()?->isAdmin(), fn ($query) => $query->whereHas('user', fn ($user) => $user->where('role', UserRole::Employee->value)))
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
        abort_unless(auth()->user()?->isSuperadmin(), 403);

        return view('pages::admin.employees.create', [
            'employeePositions' => EmployeePosition::cases(),
            'adminPositions' => AdminPosition::cases(),
            'employeeNumberPreview' => $employeeNumberGenerator->preview(),
        ]);
    }

    public function store(Request $request, CreateEmployee $createEmployee, IssueEmployeeActivation $issueActivation): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperadmin(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'position' => ['required', 'string', Rule::enum($this->positionEnumForRole(UserRole::tryFrom((string) $request->input('role'))))],
            'role' => ['required', Rule::in([UserRole::Employee->value, UserRole::Admin->value])],
            'joined_at' => ['nullable', 'date'],
        ]);

        $data['role'] = UserRole::from($data['role']);
        $employee = $createEmployee->handle($data);
        $sent = $issueActivation->handle($employee->user);

        if (! $sent) {
            return redirect()->route('admin.employees.edit', $employee)
                ->withErrors(['email' => 'Akun dibuat, tetapi email aktivasi gagal dikirim. Salin password sementara dan sampaikan melalui saluran aman.']);
        }

        return redirect()->route('admin.employees.edit', $employee)
            ->with('status', 'Akun dibuat dan tautan aktivasi telah dikirim. Password sementara tersedia melalui tindakan khusus admin.');
    }

    public function edit(Employee $employee): View
    {
        $this->authorizeAccountManagement($employee);
        $employee->load(['user.accountCredential', 'user.accountActivation']);

        $positionEnum = $this->positionEnumForRole($employee->user->role);

        return view('pages::admin.employees.edit', [
            'employee' => $employee,
            'positions' => $positionEnum::cases(),
        ]);
    }

    public function update(
        Request $request,
        Employee $employee,
        UpdateEmployee $updateEmployee,
        IssueEmployeeActivation $issueActivation,
    ): RedirectResponse {
        $this->authorizeAccountManagement($employee);
        $oldEmail = $employee->user->email;
        $positionEnum = $this->positionEnumForRole($employee->user->role);
        $positionRules = ['required', 'string'];

        if ($employee->position !== null && $positionEnum::tryFrom($employee->position) === null) {
            $positionRules[] = function (string $attribute, mixed $value, \Closure $fail) use ($employee, $positionEnum): void {
                if ($value !== $employee->position && $positionEnum::tryFrom((string) $value) === null) {
                    $fail('Jabatan tidak valid untuk jenis akun ini.');
                }
            };
        } else {
            $positionRules[] = Rule::enum($positionEnum);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employee->user_id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'position' => $positionRules,
            'joined_at' => ['nullable', 'date'],
            'is_active' => ['required', 'boolean'],
        ]);

        $emailChanged = $oldEmail !== $data['email'];
        $employee = $updateEmployee->handle($employee, $data);

        if ($emailChanged) {
            $this->notifyOldEmail($oldEmail, $employee->user->email, $employee->user_id);
        }

        if ($emailChanged && $employee->user->is_active && $employee->user->must_change_password) {
            $sent = $issueActivation->handle($employee->user);

            if (! $sent) {
                return redirect()->route('admin.employees.edit', $employee)
                    ->withErrors(['email' => 'Akun diperbarui, tetapi email aktivasi gagal dikirim. Salin password sementara untuk onboarding manual.']);
            }

            return redirect()->route('admin.employees.edit', $employee)
                ->with('status', 'Akun diperbarui dan tautan aktivasi baru dikirim ke email terbaru.');
        }

        if ($emailChanged && $employee->user->is_active) {
            try {
                $employee->user->notify(new VerifyEmail);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return redirect()->route('admin.employees.edit', $employee)->with('status', 'Data akun diperbarui.');
    }

    private function authorizeAccountManagement(Employee $employee): void
    {
        $actor = auth()->user();

        abort_unless(
            $actor?->isSuperadmin()
                || ($actor?->isAdmin() && $employee->user->role === UserRole::Employee),
            403,
        );
    }

    private function notifyOldEmail(string $oldEmail, string $newEmail, int $userId): void
    {
        try {
            Notification::route('mail', $oldEmail)->notify(new AccountSecurityNotification(
                'Email akun SanMal diubah',
                'Alamat email login akun SanMal Anda baru saja diubah menjadi '.$newEmail.'. Jika Anda tidak melakukan perubahan ini, segera hubungi administrator.',
            ));
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('Admin account email-change security notification could not be sent.', [
                'user_id' => $userId,
                'exception' => $exception::class,
            ]);
        }
    }

    /**
     * @return class-string<EmployeePosition|AdminPosition>
     */
    private function positionEnumForRole(?UserRole $role): string
    {
        return $role === UserRole::Admin ? AdminPosition::class : EmployeePosition::class;
    }

    public function revealTemporaryPassword(Employee $employee, RevealTemporaryPassword $revealPassword): JsonResponse
    {
        $this->authorizeAccountManagement($employee);
        $password = $revealPassword->handle($employee);

        return response()->json(['password' => $password])
            ->header('Cache-Control', 'no-store, private')
            ->header('Pragma', 'no-cache');
    }

    public function rotateTemporaryPassword(Employee $employee, RotateTemporaryPassword $rotatePassword, IssueEmployeeActivation $issueActivation): RedirectResponse
    {
        $this->authorizeAccountManagement($employee);
        $rotatePassword->handle($employee);
        $sent = $issueActivation->handle($employee->user);

        if (! $sent) {
            return back()->withErrors(['email' => 'Password sementara dirotasi, tetapi email aktivasi gagal dikirim. Salin password baru melalui tindakan admin.']);
        }

        return back()->with('status', 'Password sementara dirotasi dan tautan aktivasi baru dikirim.');
    }

    public function resendActivation(Employee $employee, IssueEmployeeActivation $issueActivation): RedirectResponse
    {
        $this->authorizeAccountManagement($employee);
        $sent = $issueActivation->handle($employee->user);

        if (! $sent) {
            return back()->withErrors(['email' => 'Email aktivasi gagal dikirim. Password sementara tetap dapat disalin oleh admin.']);
        }

        return back()->with('status', 'Tautan aktivasi baru telah dikirim. Tautan sebelumnya tidak lagi berlaku.');
    }
}
