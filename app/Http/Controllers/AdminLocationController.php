<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Location;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\View\View;

class AdminLocationController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('admin.clients.index', $request->query());
    }

    public function create(Request $request): View
    {
        $selectedClient = $request->filled('client_id')
            ? Client::query()->findOrFail($request->integer('client_id'))
            : null;
        $officeMode = $request->input('type') === 'office';

        abort_if($selectedClient !== null && $officeMode, 422, 'Site milik Client tidak dapat dibuat sebagai Office.');

        return view('pages::admin.locations.create', [
            'clients' => Client::query()->orderBy('name')->get(),
            'selectedClient' => $selectedClient,
            'officeMode' => $officeMode,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $location = Location::create($data);

        $destination = $location->client_id !== null
            ? route('admin.clients.show', $location->client_id)
            : route('admin.clients.index');

        return redirect($destination)->with('status', 'Location berhasil ditambahkan.');
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

        $location->update($data);

        $destination = $location->client_id !== null
            ? route('admin.clients.show', $location->client_id)
            : route('admin.clients.index');

        return redirect($destination)->with('status', 'Data Location berhasil diperbarui.');
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
