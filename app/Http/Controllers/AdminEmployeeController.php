<?php

namespace App\Http\Controllers;

use App\Actions\Admin\Employees\CreateEmployee;
use App\Actions\Admin\Employees\IssueEmployeeActivation;
use App\Actions\Admin\Employees\RevealTemporaryPassword;
use App\Actions\Admin\Employees\UpdateEmployee;
use App\Models\Employee;
use App\Models\Title;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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
            ->when($request->filled('status'), fn ($query) => $query->whereHas('user', fn ($u) => $u->where('is_active', $request->status === 'active')))
            ->orderBy('employee_number')
            ->paginate(20)->withQueryString();

        return view('pages::admin.employees.index', compact('employees'));
    }

    public function create(): View
    {
        return view('pages::admin.employees.create', [
            'titles' => Title::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, CreateEmployee $createEmployee, IssueEmployeeActivation $issueActivation): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'employee_number' => ['nullable', 'string', 'max:50', 'unique:employees,employee_number'],
            'phone' => ['nullable', 'string', 'max:30'],
            'title_id' => ['nullable', 'integer', 'exists:titles,id'],
            'joined_at' => ['nullable', 'date'],
        ]);

        $employee = $createEmployee->handle($data);
        $sent = $issueActivation->handle($employee->user);

        return redirect()->route('admin.employees.edit', $employee)
            ->with($sent ? 'status' : 'warning', $sent
                ? 'Pegawai dibuat dan tautan aktivasi telah dikirim. Password sementara tersedia melalui tindakan khusus admin.'
                : 'Pegawai dibuat, tetapi email aktivasi gagal dikirim. Salin password sementara dan sampaikan melalui saluran aman.');
    }

    public function edit(Employee $employee): View
    {
        $employee->load(['user.accountCredential', 'user.accountActivation', 'title']);

        return view('pages::admin.employees.edit', [
            'employee' => $employee,
            'titles' => Title::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Employee $employee, UpdateEmployee $updateEmployee): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employee->user_id)],
            'employee_number' => ['nullable', 'string', 'max:50', Rule::unique('employees', 'employee_number')->ignore($employee->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'title_id' => ['nullable', 'integer', 'exists:titles,id'],
            'joined_at' => ['nullable', 'date'],
            'is_active' => ['required', 'boolean'],
        ]);

        $updateEmployee->handle($employee, $data);

        return redirect()->route('admin.employees.edit', $employee)->with('status', 'Data pegawai diperbarui.');
    }

    public function revealTemporaryPassword(Employee $employee, RevealTemporaryPassword $revealPassword): JsonResponse
    {
        $password = $revealPassword->handle($employee);

        return response()->json(['password' => $password])
            ->header('Cache-Control', 'no-store, private')
            ->header('Pragma', 'no-cache');
    }

    public function resendActivation(Employee $employee, IssueEmployeeActivation $issueActivation): RedirectResponse
    {
        $sent = $issueActivation->handle($employee->user);

        return back()->with($sent ? 'status' : 'warning', $sent
            ? 'Tautan aktivasi baru telah dikirim. Tautan sebelumnya tidak lagi berlaku.'
            : 'Email aktivasi gagal dikirim. Password sementara tetap dapat disalin oleh admin.');
    }
}
