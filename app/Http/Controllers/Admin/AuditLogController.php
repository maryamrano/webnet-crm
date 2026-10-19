<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only audit viewer. Administrator only (route group enforces role:admin;
 * the check is repeated here so the controller is safe wherever it is mounted).
 * There is deliberately NO create/update/delete endpoint for audit entries.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $logs = AuditLog::query()
            ->with('actor')
            ->when($request->filled('actor_id'), fn ($q) => $q->where('user_id', $request->integer('actor_id')))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')))
            ->when($request->filled('entity'), fn ($q) => $q->where('auditable_type', $request->string('entity')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->string('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->string('to')))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'actors' => User::orderBy('name')->get(),
            'actions' => AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
            'entities' => AuditLog::query()->select('auditable_type')->distinct()->orderBy('auditable_type')->pluck('auditable_type')
                ->map(fn ($t) => ['value' => $t, 'label' => class_basename($t)]),
            'filters' => $request->only(['actor_id', 'action', 'entity', 'from', 'to']),
        ]);
    }
}
