<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\AuditLog;\nuse App\Models\Employee;
use App\Models\Title;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminEmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $employees = Employee::query()->with(['user', 'title'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = $request->string('q')->toString();
                $query->where(fn ($q) => $q
                    ->where('employee_number', 'like', "%{$term}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")));
            })
            ->when($request->filled('status'), fn ($query) => $query->whereHas('user', fn ($u) => $u->where('is_active', $request->status === 'active')))
            ->orderBy('employee_number')
            ->paginate(20)->withQueryString();

        return view('admin.employees.index', compact('employees'));
    }

    public function create(): View
    {
        return view('admin.employees.create', ['titles' => Title::query()->where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'employee_number' => ['nullable', 'string', 'max:50', 'unique:employees,employee_number'],
            'phone' => ['nullable', 'string', 'max:30'],
            'title_id' => ['nullable', 'integer', 'exists:titles,id'],
            'joined_at' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($data): void {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Str::random(40),
                'role' => UserRole::Employee,
                'is_active' => true,
                'must_change_password' => true,
            ]);
            Employee::create([
                'user_id' => $user->id,
                'employee_number' => $data['employee_number'] ?: null,
                'phone' => $data['phone'] ?: null,
                'title_id' => $data['title_id'] ?: null,
                'joined_at' => $data['joined_at'] ?: null,
            ]);
        });

        return redirect()->route('admin.employees.index')
            ->with('status', 'Data pegawai dibuat. Atur atau kirim kredensial awal melalui prosedur admin yang aman.');
    }

    public function edit(Employee $employee): View
    {
        $employee->load(['user', 'title']);
        return view('admin.employees.edit', [
            'employee' => $employee,
            'titles' => Title::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
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

        DB::transaction(function () use ($data, $employee): void {
            $employee->user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'is_active' => $data['is_active'],
            ]);
            $employee->update([
                'employee_number' => $data['employee_number'] ?: null,
                'phone' => $data['phone'] ?: null,
                'title_id' => $data['title_id'] ?: null,
                'joined_at' => $data['joined_at'] ?: null,
            ]);
        });

        return redirect()->route('admin.employees.index')->with('status', 'Data pegawai diperbarui.');
    }
}
