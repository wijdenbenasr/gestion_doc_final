<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $document->code ?? 'QMS' }} — {{ $document->name }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1a1a2e; margin: 0; }
        .watermark {
            position: fixed; top: 50%; left: 50%;
            transform: translate(-50%,-50%) rotate(-45deg);
            font-size: 90px; color: rgba(220,38,38,0.08);
            font-weight: 900; text-transform: uppercase; white-space: nowrap; z-index: -1;
        }
        .header {
            border-bottom: 3px solid #0ea5e9; padding-bottom: 10px;
            margin-bottom: 16px; position: relative; display: flex; align-items: center;
        }
        .logo-box {
            width: 55px; height: 55px; border: 2px solid #0ea5e9;
            border-radius: 8px; display: flex; align-items: center; justify-content: center;
            font-weight: 900; font-size: 10px; color: #0ea5e9; margin-right: 14px;
            text-align: center; line-height: 1.2;
        }
        .header-info { flex: 1; }
        .header-title { font-size: 16px; font-weight: 900; color: #0f172a; margin-bottom: 2px; }
        .header-sub   { font-size: 10px; color: #64748b; }
        .qr           { width: 70px; height: 70px; }
        .section      { margin-bottom: 14px; }
        .section-title {
            font-weight: 700; font-size: 9px; text-transform: uppercase;
            letter-spacing: .08em; background: #f1f5f9; padding: 4px 8px;
            border-left: 3px solid #0ea5e9; margin-bottom: 7px; color: #334155;
        }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #e2e8f0; padding: 5px 7px; font-size: 10px; text-align: left; }
        th { background: #f8fafc; font-weight: 700; color: #475569; width: 25%; }
        .sig-table td { text-align: center; vertical-align: top; padding: 10px 6px; }
        .sig-role { font-weight: 700; font-size: 9px; text-transform: uppercase; color: #64748b; margin-bottom: 4px; }
        .sig-name { font-size: 10px; margin-bottom: 3px; font-weight: 600; }
        .sig-date { font-size: 8px; color: #94a3b8; }
        .sig-hash { font-family: monospace; font-size: 7px; color: #94a3b8; word-break: break-all; margin-top: 3px; }
        .sig-pending { color: #cbd5e1; font-style: italic; }
        .badge-ok  { background: #dcfce7; color: #166534; padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: 700; }
        .badge-ko  { background: #fee2e2; color: #991b1b; padding: 2px 6px; border-radius: 4px; font-size: 9px; }
        .footer {
            position: fixed; bottom: 0; width: 100%;
            font-size: 8px; color: #94a3b8; border-top: 1px solid #e2e8f0;
            padding-top: 4px; text-align: center;
        }
        .hash-block { font-family: monospace; font-size: 7.5px; color: #64748b; word-break: break-all; }
    </style>
</head>
<body>

@if($document->status === 'rejected')
    <div class="watermark">REJETÉ</div>
@elseif($document->status !== 'finalized')
    <div class="watermark">BROUILLON</div>
@endif

<div class="header">
    <div class="logo-box">QMS<br>DOC</div>
    <div class="header-info">
        <div class="header-title">{{ $document->name }}</div>
        <div class="header-sub">
            Code : <strong>{{ $document->code ?? 'Non codifié' }}</strong> &nbsp;|&nbsp;
            Révision : <strong>v{{ $document->revision }}</strong> &nbsp;|&nbsp;
            Statut : <strong>{{ \App\Models\Document::STATUSES[$document->status] ?? $document->status }}</strong>
        </div>
    </div>
    @if(isset($qrCodeUrl))
        <img src="{{ $qrCodeUrl }}" class="qr" alt="QR">
    @endif
</div>

{{-- Informations générales --}}
<div class="section">
    <div class="section-title">Informations générales</div>
    <table>
        <tr>
            <th>Code document</th>
            <td><strong>{{ $document->code ?? 'En attente de codification' }}</strong></td>
            <th>Type</th>
            <td>{{ \App\Models\Document::TYPES[$document->type] ?? $document->type }}</td>
        </tr>
        <tr>
            <th>AIO</th>
            <td>{{ \App\Models\Document::AIOS[$document->aio] ?? strtoupper($document->aio) }}</td>
            <th>Ligne de production</th>
            <td>{{ $document->ligne }}</td>
        </tr>
        <tr>
            <th>Phase</th>
            <td>{{ $document->phase === 'projet' ? 'Projet' : 'Série' }}</td>
            <th>{{ $document->phase === 'projet' ? 'Nom de la phase' : 'Numéro de série' }}</th>
            <td>{{ $document->phase === 'projet' ? ($document->nom_phase ?? '—') : ($document->nom_serie ?? '—') }}</td>
        </tr>
        <tr>
            <th>Créé par</th>
            <td>{{ $document->creator->name ?? '—' }} {{ $document->creator->prenom ?? '' }}</td>
            <th>Date création</th>
            <td>{{ $document->created_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <th>Deadline</th>
            <td>{{ $document->deadline ? $document->deadline->format('d/m/Y H:i') : '—' }}</td>
            <th>Fichier original</th>
            <td>{{ $document->file_original_name }}</td>
        </tr>
    </table>
</div>

{{-- Signatures --}}
<div class="section">
    <div class="section-title">Workflow de validation & signatures</div>
    <table class="sig-table">
        <tr>
            @foreach(['creator' => 'Créateur', 'validator' => 'Validateur', 'approver' => 'Approbateur', 'admin' => 'Administrateur'] as $role => $label)
                @php $sig = $document->signatures->where('role', $role)->first(); @endphp
                <td style="width:25%;">
                    <div class="sig-role">{{ $label }}</div>
                    @if($sig && $sig->signed_at)
                        <div class="sig-name">{{ $sig->user->name ?? '—' }}</div>
                        <div class="sig-date">{{ $sig->signed_at->format('d/m/Y H:i') }}</div>
                        <div><span class="badge-ok">✓ Signé</span></div>
                        <div class="sig-hash">{{ substr($sig->hash ?? '', 0, 24) }}...</div>
                    @else
                        <div class="sig-pending">— En attente —</div>
                    @endif
                </td>
            @endforeach
        </tr>
    </table>
</div>

{{-- Historique versions --}}
@if($document->versions->count())
<div class="section">
    <div class="section-title">Historique des versions</div>
    <table>
        <thead>
        <tr>
            <th style="width:15%;">Version</th>
            <th style="width:25%;">Date</th>
            <th style="width:25%;">Auteur</th>
            <th>Hash SHA-256 (partiel)</th>
        </tr>
        </thead>
        <tbody>
        @foreach($document->versions as $v)
            <tr>
                <td>{{ $v->revision }}</td>
                <td>{{ $v->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $v->creator->name ?? '—' }}</td>
                <td class="hash-block">{{ substr($v->hash, 0, 20) }}...</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Audit trail (admin only) --}}
@if(auth()->user()->role === 'admin' && $document->auditLogs->count())
<div class="section">
    <div class="section-title">Journal d'audit (10 dernières actions)</div>
    <table>
        <thead>
        <tr><th style="width:35%;">Action</th><th style="width:30%;">Utilisateur</th><th>Date</th></tr>
        </thead>
        <tbody>
        @foreach($document->auditLogs->take(10) as $log)
            <tr>
                <td>{{ str_replace('_', ' ', ucfirst($log->action)) }}</td>
                <td>{{ $log->user->name ?? 'Système' }}</td>
                <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="footer">
    Généré le {{ $generatedAt->format('d/m/Y à H:i:s') }} — Gestion documentaire qualité — Conformité ISO 9001
    <br>
    <span class="hash-block">Intégrité fichier (SHA-256) : {{ $document->hash ?? 'Non calculé' }}</span>
</div>

</body>
</html>
