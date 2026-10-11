<?php

namespace App\Http\Controllers\Coordinator;

use App\Exports\AuditLogExport;
use App\Http\Controllers\Controller;
use App\Models\FieldChangeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAuditLog', User::class);

        $logs = $this->filtered($request)
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('coordinator.audit-log.index', [
            'logs' => $logs,
            'actionLabels' => FieldChangeLog::ACTION_LABELS,
        ]);
    }

    /**
     * Streams an .xlsx of whatever is currently filtered on the index page.
     * Coordinator-only. Only ever reads via filtered()->get(); never touches
     * update()/delete() anywhere in this method.
     */
    public function export(Request $request)
    {
        Gate::authorize('exportAuditLog', User::class);

        $logs = $this->filtered($request)->latest()->get();

        return Excel::download(new AuditLogExport($logs), 'audit-log-'.now()->format('Y-m-d-His').'.xlsx');
    }

    /**
     * Shared by index() and export() so they can never drift apart.
     */
    private function filtered(Request $request): Builder
    {
        // withTrashed() on user/jobPosting/editor: a soft-deleted Student/
        // Alumni, removed job posting, or removed Company account (or a
        // a legacy account) should still display correctly in historical
        // log entries rather than silently returning null.
        return FieldChangeLog::query()
            ->with([
                'user' => fn ($q) => $q->withTrashed(),
                'editor' => fn ($q) => $q->withTrashed(),
                'jobPosting' => fn ($q) => $q->withTrashed(),
            ])
            ->when($request->action, fn ($q, $action) => $q->where('action', $action))
            ->when($request->date_range === 'today', fn ($q) => $q->whereDate('created_at', now()->toDateString()))
            ->when($request->date_range === 'last_7_days', fn ($q) => $q->where('created_at', '>=', now()->subDays(7)->startOfDay()))
            ->when($request->date_range === 'last_30_days', fn ($q) => $q->where('created_at', '>=', now()->subDays(30)->startOfDay()))
            ->when($request->date_range === 'last_100_days', fn ($q) => $q->where('created_at', '>=', now()->subDays(100)->startOfDay()))
            ->when($request->date_range === 'this_month', fn ($q) => $q->where('created_at', '>=', now()->startOfMonth()))
            ->when($request->date_range === 'custom' && $request->date_from, fn ($q) => $q->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay()))
            ->when($request->date_range === 'custom' && $request->date_to, fn ($q) => $q->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay()));
    }
}