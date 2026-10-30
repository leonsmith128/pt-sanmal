<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $logs = AuditLog::query()
            ->with('user')
            ->when(
                $filters['action'] ?? null,
                fn ($query, string $action) => $query->where('action', 'like', '%' . $action . '%'),
            )
            ->when(
                $filters['from'] ?? null,
                fn ($query, string $from) => $query->whereDate('created_at', '>=', $from),
            )
            ->when(
                $filters['to'] ?? null,
                fn ($query, string $to) => $query->whereDate('created_at', '<=', $to),
            )
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('pages::admin.audit-logs.index', compact('logs'));
    }
}
