<?php

namespace App\\Http\\Controllers;

use App\\Models\\AuditLog;
use Illuminate\\Http\\Request;
use Illuminate\\View\\View;

class AdminAuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::query()->with('user')
            ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', '%'.$request->string('action')->toString().'%'))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->latest('created_at')->paginate(30)->withQueryString();

        return view('admin.audit-logs.index', compact('logs'));
    }
}
