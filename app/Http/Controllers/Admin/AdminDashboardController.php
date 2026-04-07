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
            'year'  => now()->subYear(),
            default => now()->subWeek(),
        };

        $base = Document::query()->where('created_at', '>=', $from);

        $createdCount      = (clone $base)->count();
        $inValidationCount = (clone $base)->whereIn('status', ['draft', 'in_validation', 'rejected', 'pending_codification'])->count();
        $finalizedCount    = (clone $base)->where('status', 'finalized')->count();   // BUG CORRIGÉ : cohérent avec SignatureService
        $rejectedCount     = (clone $base)->where('status', 'rejected')->count();

        // Compteurs pour les badges dans le header
        $pendingCodification = Document::where('status', 'pending_codification')->count();
        $pendingUsers        = User::where('is_admin_approved', false)->count();
        $awaitingVerification = User::where('is_admin_approved', true)->whereNull('email_verified_at')->count();
        $totalUsers = User::count();
        $usersByRole = User::query()
            ->selectRaw('role, COUNT(*) as total')
            ->whereNotNull('role')
            ->groupBy('role')
            ->pluck('total', 'role');
        $recentLogs = AuditLog::with('user')->latest()->limit(10)->get();

        $documents = (clone $base)
            ->with(['creator'])
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
            'range'
        ));
    }
}
