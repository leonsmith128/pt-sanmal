<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Location;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class AdminLocationController extends Controller
{
    public function index(Request $request): View
    {
        $locations = Location::query()
            ->with('client')
            ->withCount(['projects', 'attendanceRecords'])
            ->when($request->filled('q'), fn ($query) => $query->where(function ($q) use ($request): void {
                $term = $request->string('q')->toString();
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('address', 'like', "%{$term}%");
            }))
            ->when($request->filled('client_id'), fn ($query) => $request->integer('client_id') === 0
                ? $query->whereNull('client_id')
                : $query->where('client_id', $request->integer('client_id')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->status === 'active'))
            ->orderByRaw('client_id IS NULL DESC')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('pages::admin.locations.index', [
            'locations' => $locations,
            'clients' => Client::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('pages::admin.locations.create', [
            'clients' => Client::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Location::create($this->validatedData($request));

        return redirect()->route('admin.locations.index')->with('status', 'Lokasi berhasil ditambahkan.');
    }

    public function edit(Location $location): View
    {
        return view('pages::admin.locations.edit', [
            'location' => $location,
            'clients' => Client::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $data = $this->validatedData($request);

        if (! $data['is_active'] && $location->projects()->where('status', ProjectStatus::Active)->exists()) {
            throw ValidationException::withMessages([
                'is_active' => 'Lokasi tidak dapat dinonaktifkan selama masih memiliki Project aktif.',
            ]);
        }

        if ($location->projects()->exists() && (
            $data['type'] !== $location->type
            || $data['client_id'] !== $location->client_id
        )) {
            throw ValidationException::withMessages([
                'type' => 'Jenis dan pemilik lokasi tidak dapat diubah setelah lokasi digunakan oleh Project.',
            ]);
        }

        $location->update($data);

        return redirect()->route('admin.locations.index')->with('status', 'Data lokasi berhasil diperbarui.');
    }

    /**
     * @return array{client_id:int|null,name:string,type:string,address:?string,latitude:?string,longitude:?string,timezone:string,is_active:bool}
     */
    private function validatedData(Request $request): array
    {
        $validator = validator($request->all(), [
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['office', 'site'])],
            'address' => ['nullable', 'string', 'max:2000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'timezone' => ['required', 'string', 'max:50', Rule::in(DateTimeZone::listIdentifiers())],
            'is_active' => ['required', 'boolean'],
        ]);

        $validator->after(function (Validator $validator) use ($request): void {
            if ($request->filled('latitude') xor $request->filled('longitude')) {
                $validator->errors()->add('latitude', 'Koordinat latitude dan longitude harus diisi berpasangan.');
            }

            $type = $request->input('type');
            $clientId = $request->input('client_id');

            if ($type === 'office' && filled($clientId)) {
                $validator->errors()->add('client_id', 'Lokasi Office internal tidak boleh memiliki Client.');
            }

            if ($type === 'site' && blank($clientId)) {
                $validator->errors()->add('client_id', 'Lokasi Site wajib dimiliki oleh Client.');
            }
        });

        $data = $validator->validate();
        $data['client_id'] = filled($data['client_id'] ?? null) ? (int) $data['client_id'] : null;
        $data['latitude'] = $data['latitude'] ?? null;
        $data['longitude'] = $data['longitude'] ?? null;

        return $data;
    }
}
