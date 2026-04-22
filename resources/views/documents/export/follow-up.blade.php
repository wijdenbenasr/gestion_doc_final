<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Suivi des documents - QMS Doc Control</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10pt; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0ea5e9; padding-bottom: 15px; }
        .header h1 { font-size: 18pt; color: #0f172a; margin-bottom: 5px; }
        .date { color: #666; font-size: 9pt; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; font-size: 9pt; }
        th { background: #0ea5e9; color: white; text-transform: uppercase; }
        .footer { margin-top: 20px; text-align: center; font-size: 8pt; color: #666; }
    </style>
</head>
<body>
@php use App\Models\Document; @endphp

<div class="header">
    <h1>Suivi des documents - QMS Doc Control</h1>
    <div class="date">Genere le {{ $generatedAt->format('d/m/Y a H:i') }}</div>
</div>

<table>
    <thead>
        <tr>
            <th style="background:#0ea5e9;color:white;">#</th>
            <th style="background:#0ea5e9;color:white;">Nom</th>
            <th style="background:#0ea5e9;color:white;">Code</th>
            <th style="background:#0ea5e9;color:white;">Type</th>
            <th style="background:#0ea5e9;color:white;">AIO</th>
            <th style="background:#0ea5e9;color:white;">Ligne</th>
            <th style="background:#0ea5e9;color:white;">Phase</th>
            <th style="background:#0ea5e9;color:white;">Rev.</th>
            <th style="background:#0ea5e9;color:white;">Statut</th>
            <th style="background:#0ea5e9;color:white;">Role actuel</th>
            <th style="background:#0ea5e9;color:white;">Cree par</th>
            <th style="background:#0ea5e9;color:white;">Cree le</th>
        </tr>
    </thead>
    <tbody>
        @foreach($documents as $index => $doc)
            <tr style="{{ $index % 2 == 1 ? 'background:#f8f9fa;' : '' }}">
                <td>{{ $index + 1 }}</td>
                <td>{{ $doc->name }}</td>
                <td>{{ $doc->code ?? '-' }}</td>
                <td>{{ Document::TYPES[$doc->type] ?? $doc->type }}</td>
                <td>{{ Document::AIOS[$doc->aio] ?? strtoupper($doc->aio) }}</td>
                <td>{{ $doc->ligne }}</td>
                <td>{{ $doc->phase === 'projet' ? 'Projet' : 'Serie' }}</td>
                <td>v{{ $doc->revision }}</td>
                <td>{{ Document::STATUSES[$doc->status] ?? $doc->status }}</td>
                <td>{{ $doc->current_role ?? '-' }}</td>
                <td>{{ $doc->creator->name ?? '-' }}</td>
                <td>{{ $doc->created_at->format('d/m/Y') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    Total : {{ $documents->count() }} document(s)
</div>

</body>
</html>