<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $range = $request->query('range', 'week');

        $from = match ($range) {
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subWeek(),
        };

        $base = Document::query()->where('created_at', '>=', $from);

        $createdCount = (clone $base)->count();
        $inValidationCount = (clone $base)->whereIn('status', ['draft', 'in_validation', 'rejected', 'pending_codification'])->count();
        $finalizedCount = (clone $base)->where('status', 'finalized')->count();
        $rejectedCount = (clone $base)->where('status', 'rejected')->count();

        // Activity chart data - last 7 days
        $activityChart = $this->getActivityChartData();

        // Compteurs pour les badges dans le header
        $pendingCodification = Document::where('status', 'pending_codification')->count();
        $pendingUsers = User::where('is_admin_approved', false)->count();
        $awaitingVerification = User::where('is_admin_approved', true)->whereNull('email_verified_at')->count();
        $totalUsers = User::count();
        $usersByRole = User::query()
            ->selectRaw('role, COUNT(*) as total')
            ->whereNotNull('role')
            ->groupBy('role')
            ->pluck('total', 'role');
        $recentLogs = AuditLog::with('user')->latest()->limit(10)->get();

        $documents = (clone $base)
            ->with(['creator', 'signatures.user', 'transmissions.sender'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.dashboard', compact(
            'documents',
            'createdCount',
            'inValidationCount',
            'finalizedCount',
            'rejectedCount',
            'pendingCodification',
            'pendingUsers',
            'awaitingVerification',
            'totalUsers',
            'usersByRole',
            'recentLogs',
            'range',
            'activityChart'
        ));
    }

    private function getActivityChartData(): array
    {
        $labels = [];
        $created = [];
        $validated = [];
        $rejected = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('d/m');
            $created[] = Document::whereDate('created_at', $date)->count();
            $validated[] = Document::whereDate('created_at', $date)->where('status', 'finalized')->count();
            $rejected[] = Document::whereDate('created_at', $date)->where('status', 'rejected')->count();
        }

        return [
            'labels' => $labels,
            'created' => $created,
            'validated' => $validated,
            'rejected' => $rejected,
        ];
    }

    public function documents(Request $request)
    {
        $status = $request->query('status');
        $range = $request->query('range', 'week');

        $from = match ($range) {
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subWeek(),
        };

        $query = Document::query()->where('created_at', '>=', $from);

        if ($status) {
            switch ($status) {
                case 'created':
                    // Tous les documents créés dans la période
                    break;
                case 'in_validation':
                    $query->whereIn('status', ['draft', 'in_validation', 'pending_codification']);
                    break;
                case 'finalized':
                    $query->where('status', 'finalized');
                    break;
                case 'rejected':
                    $query->where('status', 'rejected');
                    break;
            }
        }

        $documents = $query
            ->with(['creator', 'signatures.user', 'transmissions.sender'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.documents', compact('documents', 'status', 'range'));
    }
}
