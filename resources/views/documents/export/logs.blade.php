<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Journaux et Traabilit QMS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #fff;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0ea5e9;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0 0 10px 0;
            color: #1f2937;
            font-size: 24px;
        }
        .header .meta {
            color: #6b7280;
            font-size: 12px;
        }
        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .section-title {
            background-color: #f3f4f6;
            border-left: 4px solid #0ea5e9;
            padding: 12px 15px;
            margin-bottom: 15px;
            font-weight: bold;
            font-size: 16px;
            color: #1f2937;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        table thead {
            background-color: #e5e7eb;
        }
        table thead th {
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #d1d5db;
            color: #1f2937;
        }
        table tbody td {
            padding: 8px 10px;
            border: 1px solid #e5e7eb;
            color: #374151;
        }
        table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .entry {
            background-color: #f9fafb;
            border-left: 3px solid #0ea5e9;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .entry-header {
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 5px;
            font-size: 12px;
        }
        .entry-detail {
            color: #6b7280;
            font-size: 11px;
            margin: 3px 0;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 10px;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            white-space: nowrap;
        }
        .badge-info { background-color: #dbeafe; color: #0c4a6e; }
        .badge-success { background-color: #dcfce7; color: #166534; }
        .badge-warning { background-color: #fef3c7; color: #92400e; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Journaux et Traabilit QMS</h1>
        <div class="meta">Systme de Gestion Documentaire Qualit</div>
        <div class="meta">Gnr le {{ $generatedAt->format('d/m/Y  H:i:s') }}</div>
    </div>

    <!-- Section des Logs d'Audit -->
    <div class="section">
        <div class="section-title">Journaux d'Audit</div>
        @if($auditLogs->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th>Date/Heure</th>
                        <th>Utilisateur</th>
                        <th>Document</th>
                        <th>Action</th>
                        <th>Dtails</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($auditLogs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $log->user->name ?? 'Système' }} {{ $log->user->prenom ?? '' }}</td>
                            <td>
                                @if($log->auditable && $log->auditable_type === 'App\Models\Document')
                                    {{ $log->auditable->code ?? $log->auditable->name ?? 'DOC-' . $log->auditable->id }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $actionLabels[strtolower($log->action)]  ucfirst(str_replace('_', ' ', $log->action)) }}</td>
                            <td>
                                @if($log->payload && count($log->payload) > 0)
                                    {{ Illuminate\Support\Str::limit(json_encode($log->payload, JSON_UNESCAPED_UNICODE), 50) }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div style="text-align: center; color: #9ca3af; padding: 20px;">
                Aucun journal d'audit disponible.
            </div>
        @endif
    </div>

    <!-- Section des Transmissions (Traabilit) -->
    <div class="section">
        <div class="section-title">Traabilit des Transmissions</div>
        @if($transmissions->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th>Date/Heure</th>
                        <th>Document</th>
                        <th>De (Rle)</th>
                        <th>Utilisateur</th>
                        <th>Vers</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transmissions as $trans)
                        <tr>
                            <td>{{ $trans->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>
                                {{ $trans->document?->code ?? ($trans->document?->id ? 'DOC-' . $trans->document->id : 'Document supprimé') }}
                            </td>
                            <td>
                                <span class="badge badge-info">
                                    @switch($trans->from_role)
                                        @case('creator') Crateur @break
                                        @case('validator') Validateur @break
                                        @case('approver') Approbateur @break
                                        @case('admin') Admin @break
                                        @default {{ ucfirst($trans->from_role) }}
                                    @endswitch
                                </span>
                            </td>
                            <td>{{ $trans->user->name ?? 'Système' }} {{ $trans->user->prenom ?? '' }}</td>
                            <td>
                                <span class="badge badge-success">
                                    @switch($trans->to_role)
                                        @case('creator') Crateur @break
                                        @case('validator') Validateur @break
                                        @case('approver') Approbateur @break
                                        @case('admin') Admin @break
                                        @default {{ ucfirst($trans->to_role) }}
                                    @endswitch
                                </span>
                            </td>
                            <td>
                                @php
                                    $statusBadge = match($trans->status) {
                                        'sent' => 'badge-success',
                                        'pending' => 'badge-warning',
                                        'rejected' => 'badge-danger',
                                        'approved' => 'badge-success',
                                        default => 'badge-info'
                                    };
                                    $statusLabel = ucfirst(str_replace('_', ' ', $trans->status));
                                @endphp
                                <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div style="text-align: center; color: #9ca3af; padding: 20px;">
                Aucune transmission enregistre.
            </div>
        @endif
    </div>

    <!-- Statistiques Rcapitulatives -->
    <div class="section">
        <div class="section-title">Statistiques</div>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
            <div class="entry">
                <div class="entry-header">Total des Journaux d'Audit</div>
                <div class="entry-detail">{{ $auditLogs->count() }} enregistrements</div>
            </div>
            <div class="entry">
                <div class="entry-header">Total des Transmissions</div>
                <div class="entry-detail">{{ $transmissions->count() }} enregistrements</div>
            </div>
            <div class="entry">
                <div class="entry-header">Priode Couverte</div>
                <div class="entry-detail">
                    @php
                        $allDates = collect()
                            ->merge($auditLogs->pluck('created_at'))
                            ->merge($transmissions->pluck('created_at'));
                        $minDate = $allDates->min();
                        $maxDate = $allDates->max();
                    @endphp
                    @if($minDate && $maxDate)
                        Du {{ $minDate->format('d/m/Y') }} au {{ $maxDate->format('d/m/Y') }}
                    @else
                        Aucune donne
                    @endif
                </div>
            </div>
            <div class="entry">
                <div class="entry-header">Nombre d'Utilisateurs Impliqus</div>
                <div class="entry-detail">
                    @php
                        $users = collect()
                            ->merge($auditLogs->pluck('user_id'))
                            ->merge($transmissions->pluck('sent_by'))
                            ->unique()
                            ->count();
                    @endphp
                    {{ $users }} utilisateurs
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Ce document contient les journaux d'audit et la traçabilité des transmissions du système QMS Doc Control.</p>
        <p>Document confidentiel - Rserv  l'usage interne</p>
    </div>
</body>
</html>




