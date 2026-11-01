<?php

namespace App\\Http\\Controllers;

use App\\Models\\Client;
use App\\Models\\Location;
use Illuminate\\Http\\RedirectResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Validation\\Rule;
use Illuminate\\View\\View;

class AdminClientController extends Controller
{
    public function index(Request $request): View
    {
        $term = trim($request->string('q')->toString());

        $clients = Client::query()
            ->with(['locations' => fn ($query) => $query
                ->withCount(['projects', 'attendanceRecords'])
                ->orderBy('name')])
            ->withCount(['locations', 'projects'])
            ->when($term !== '', fn ($query) => $query->where(function ($nested) use ($term): void {
                $nested->where('code', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhereHas('locations', fn ($locations) => $locations
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('address', 'like', "%{$term}%"));
            }))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $offices = Location::query()
            ->where('type', 'office')
            ->withCount(['projects', 'attendanceRecords'])
            ->when($term !== '', fn ($query) => $query->where(function ($nested) use ($term): void {
                $nested->where('name', 'like', "%{$term}%")
                    ->orWhere('address', 'like', "%{$term}%");
            }))
            ->orderBy('name')
            ->get();

        return view('pages::admin.clients.index', compact('clients', 'offices', 'term'));
    }

    public function show(Client $client): View
    {
        $client->load(['locations' => fn ($query) => $query
            ->withCount(['projects', 'attendanceRecords'])
            ->orderBy('name')])->loadCount('projects');

        return view('pages::admin.clients.show', compact('client'));
    }

    public function create(): View
    {
        return view('pages::admin.clients.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:clients,code'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $client = Client::create($data);

        return redirect()->route('admin.clients.show', $client)->with('status', 'Client berhasil ditambahkan.');
    }

    public function edit(Client $client): View
    {
        return view('pages::admin.clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('clients', 'code')->ignore($client->id)],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $client->update($data);

        return redirect()->route('admin.clients.show', $client)->with('status', 'Data client berhasil diperbarui.');
    }
}
