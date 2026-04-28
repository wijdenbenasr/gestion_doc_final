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
        $inValidationCount = (clone $base)->whereIn('status', ['draft', 'in_validation', 'rejected', 'pending_codification', 'approbation', 'validation_admin'])->count();
        $finalizedCount = (clone $base)->where('status', 'archived')->count();
        $rejectedCount = (clone $base)->where('status', 'rejected')->count();

        // Compteur pour documents en attente de validation admin
        $pendingAdminValidation = Document::where('status', 'validation_admin')
            ->where('current_role', 'admin')
            ->count();

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
            ->with(['creator', 'signatures.user', 'transmissions.sender', 'validatedBy', 'approvedBy'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Alertes prioritaires: prets pour signature finale (signing_admin)
        $alertes = Document::where('status', 'signing_admin')
            ->where('current_role', 'admin')
            ->orderByRaw('CASE
                WHEN deadline IS NULL THEN 2
                WHEN deadline < NOW() THEN 0
                WHEN deadline < DATE_ADD(NOW(), INTERVAL 2 DAY) THEN 1
                ELSE 2
            END')
            ->limit(5)
            ->get();

        // Documents en supervision (tous statuts en attente)
        $documentsSupervision = Document::whereIn('status', ['draft', 'pending_codification', 'in_validation', 'approbation', 'validation_admin', 'signing_admin', 'ready_for_pdf', 'pdf_converted'])
            ->whereNotNull('deadline')
            ->orderByRaw('CASE
                WHEN deadline < NOW() THEN 0
                WHEN deadline < DATE_ADD(NOW(), INTERVAL 2 DAY) THEN 1
                ELSE 2
            END')
            ->limit(10)
            ->get();

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
            'activityChart',
            'alertes',
            'documentsSupervision',
            'pendingAdminValidation'
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
            $validated[] = Document::whereDate('created_at', $date)->where('status', 'archived')->count();
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
                    $query->whereIn('status', ['in_validation', 'approbation', 'validation_admin', 'signing_admin', 'pending_codification']);
                    break;
                case 'archived':
                    $query->where('status', 'archived');
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
