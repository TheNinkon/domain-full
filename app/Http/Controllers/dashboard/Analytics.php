<?php

namespace App\Http\Controllers\dashboard;

use App\Enums\DomainStatus;
use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\DomainLog;
use Illuminate\View\View;

class Analytics extends Controller
{
    public function index(): View
    {
        $statusDistribution = Domain::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => ['status' => $row->status, 'total' => $row->total]);

        $categoryDistribution = Domain::query()
            ->selectRaw('domain_category_id, count(*) as total')
            ->groupBy('domain_category_id')
            ->with('category')
            ->get()
            ->map(fn ($row) => ['name' => $row->category?->name ?? 'Sin categoría', 'total' => $row->total]);

        return view('content.dashboard.dashboards-analytics', [
            'totalDomains' => Domain::count(),
            'expiringSoonCount' => Domain::whereBetween('expiration_date', [now(), now()->addDays(30)])->count(),
            'forSaleCount' => Domain::where('status', DomainStatus::ForSale->value)->count(),
            'totalInvested' => (float) Domain::sum('purchase_cost') + (float) Domain::sum('renewal_cost'),
            'statusDistribution' => $statusDistribution,
            'categoryDistribution' => $categoryDistribution,
            'recentLogs' => DomainLog::with(['domain', 'user'])->latest('created_at')->limit(8)->get(),
        ]);
    }
}
