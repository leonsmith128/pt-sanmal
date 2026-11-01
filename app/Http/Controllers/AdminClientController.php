<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminClientController extends Controller
{
    public function index(Request $request): View
    {
        $clients = Client::query()
            ->withCount(['locations', 'projects'])
            ->when($request->filled('q'), fn ($query) => $query->where(function ($q) use ($request): void {
                $term = $request->string('q')->toString();
                $q->where('code', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            }))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('pages::admin.clients.index', compact('clients'));
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

        Client::create($data);

        return redirect()->route('admin.clients.index')->with('status', 'Client berhasil ditambahkan.');
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

        return redirect()->route('admin.clients.index')->with('status', 'Data client berhasil diperbarui.');
    }
}
