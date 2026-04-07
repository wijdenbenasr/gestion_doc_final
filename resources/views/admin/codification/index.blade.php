@extends('layouts.app')

@section('title', 'Codification des documents')

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Codification des documents</div>
            <div class="card-sub">Attribuez un code unique avant de renvoyer le document au createur.</div>
        </div>
        <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
            <span class="badge badge-warning" style="font-size:.8rem;padding:.3rem .7rem;">{{ $documents->total() }} en attente</span>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost btn-sm">Dashboard</a>
        </div>
    </div>

    @if($documents->isEmpty())
        <div style="text-align:center;padding:2rem;color:var(--muted);">Aucun document en attente de codification.</div>
    @else
        <div style="overflow-x:auto;margin-top:.75rem;">
            <table>
                <thead>
                <tr>
                    <th>Nom</th>
                    <th>Type</th>
                    <th>AIO</th>
                    <th>Ligne</th>
                    <th>Phase</th>
                    <th>Createur</th>
                    <th>Date creation</th>
                    <th>Attribuer un code</th>
                </tr>
                </thead>
                <tbody>
                @foreach($documents as $doc)
                    <tr>
                        <td style="font-weight:500;max-width:150px;">
                            <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $doc->name }}">{{ $doc->name }}</div>
                        </td>
                        <td style="font-size:.72rem;max-width:130px;" title="{{ $doc->type_libelle }}">{{ \Illuminate\Support\Str::limit($doc->type_libelle, 24) }}</td>
                        <td><span class="badge badge-info">{{ \App\Models\Document::AIOS[$doc->aio] ?? $doc->aio }}</span></td>
                        <td>{{ $doc->ligne }}</td>
                        <td style="font-size:.72rem;">{{ $doc->phase_libelle }}</td>
                        <td>{{ $doc->creator->name ?? '-' }} {{ $doc->creator->prenom ?? '' }}</td>
                        <td style="font-size:.72rem;">{{ $doc->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.documents.codify', $doc) }}" style="display:flex;flex-direction:column;gap:.35rem;">
                                @csrf
                                <div style="display:flex;gap:.35rem;align-items:center;flex-wrap:wrap;">
                                    <input type="text" name="code" placeholder="Ex: QMS-SOP-AIO1-001" required style="font-size:.78rem;padding:.35rem .5rem;width:190px;font-family:monospace;">
                                    <button type="submit" class="btn btn-sm">Valider</button>
                                    <a href="{{ route('documents.download', $doc) }}" class="btn btn-ghost btn-sm">Source</a>
                                </div>
                                <div style="font-size:.67rem;color:var(--muted);">Suggestion : QMS-{{ strtoupper(substr($doc->type, 0, 3)) }}-{{ strtoupper($doc->aio) }}-{{ str_pad($doc->id, 3, '0', STR_PAD_LEFT) }}</div>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $documents->links() }}</div>
    @endif
</div>
@endsection
