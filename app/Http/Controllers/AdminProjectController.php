<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Models\Client;
use App\Models\Location;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\View\View;

class AdminProjectController extends Controller
{
    public function index(Request $request): View
    {
        $projects = Project::query()
            ->with(['client', 'location'])
            ->withCount('attendanceRecords')
            ->when($request->filled('q'), fn ($query) => $query->where(function ($nested) use ($request): void {
                $term = $request->string('q')->toString();
                $nested->where('code', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%");
            }))
            ->when($request->filled('client_id'), fn ($query) => $query->where('client_id', $request->integer('client_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('project_type'), fn ($query) => $query->where('project_type', $request->string('project_type')->toString()))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('pages::admin.projects.index', [
            'projects' => $projects,
            'clients' => Client::query()->orderBy('name')->get(),
            'statuses' => ProjectStatus::cases(),
            'projectTypes' => ProjectType::cases(),
        ]);
    }

    public function create(): View
    {
        return view('pages::admin.projects.create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        Project::query()->create($this->validatedData($request));

        return redirect()->route('admin.projects.index')->with('status', 'Proyek berhasil ditambahkan.');
    }

    public function edit(Project $project): View
    {
        return view('pages::admin.projects.edit', $this->formOptions($project) + [
            'project' => $project->load(['client', 'location']),
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $project->update($this->validatedData($request, $project));

        return redirect()->route('admin.projects.index')->with('status', 'Data proyek berhasil diperbarui.');
    }

    /**
     * @return array{
     *     clients: \Illuminate\Database\Eloquent\Collection<int, Client>,
     *     locations: \Illuminate\Database\Eloquent\Collection<int, Location>,
     *     statuses: list<ProjectStatus>,
     *     projectTypes: list<ProjectType>
     * }
     */
    private function formOptions(?Project $project = null): array
    {
        $locations = Location::query()
            ->with('client')
            ->where('type', 'site')
            ->where(function ($query) use ($project): void {
                $query->where('is_active', true);

                if ($project !== null) {
                    $query->orWhereKey($project->location_id);
                }
            })
            ->orderBy('name')
            ->get();

        return [
            'clients' => Client::query()->orderBy('name')->get(),
            'locations' => $locations,
            'statuses' => ProjectStatus::cases(),
            'projectTypes' => ProjectType::cases(),
        ];
    }

    /**
     * @return array{client_id:int,code:string,name:string,project_type:ProjectType,location_id:int,description:?string,start_date:?string,end_date:?string,status:ProjectStatus}
     */
    private function validatedData(Request $request, ?Project $project = null): array
    {
        $validator = validator($request->all(), [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'code' => ['required', 'string', 'max:50', Rule::unique('projects', 'code')->ignore($project?->id)],
            'name' => ['required', 'string', 'max:255'],
            'project_type' => ['required', Rule::enum(ProjectType::class)],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', Rule::when($request->filled('start_date'), ['after_or_equal:start_date'])],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
        ]);

        $validator->after(function (Validator $validator) use ($request, $project): void {
            $location = Location::query()->find($request->integer('location_id'));

            if ($location === null) {
                return;
            }

            $clientId = $request->integer('client_id');
            $sameLocation = $project !== null && $project->location_id === $location->id;

            if ($location->type !== 'site' || $location->client_id !== $clientId) {
                $validator->errors()->add('location_id', 'Lokasi harus berupa Site yang dimiliki Client yang dipilih.');
            }

            if (! $location->is_active && ! $sameLocation) {
                $validator->errors()->add('location_id', 'Lokasi Site nonaktif tidak dapat dipilih untuk proyek baru atau pemindahan proyek.');
            }

            if (
                $request->input('status') === ProjectStatus::Active->value
                && ! $location->is_active
                && ($project === null || $project->status !== ProjectStatus::Active)
            ) {
                $validator->errors()->add('status', 'Proyek hanya dapat diaktifkan jika lokasi Site aktif.');
            }

            if (
                $project !== null
                && $project->attendanceRecords()->exists()
                && ($project->client_id !== $clientId || $project->location_id !== $location->id)
            ) {
                $validator->errors()->add('location_id', 'Client dan lokasi tidak dapat diubah setelah proyek memiliki riwayat absensi.');
            }
        });

        $data = $validator->validate();

        return [
            'client_id' => (int) $data['client_id'],
            'code' => $data['code'],
            'name' => $data['name'],
            'project_type' => ProjectType::from($data['project_type']),
            'location_id' => (int) $data['location_id'],
            'description' => $data['description'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'status' => ProjectStatus::from($data['status']),
        ];
    }
}
