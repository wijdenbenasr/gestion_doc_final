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
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="empty-icon">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
            <div class="empty-title">Aucun document en attente</div>
            <div class="empty-desc">Les documents soumis apparaitront ici</div>
        </div>
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
                                    <input type="text" id="code-input-{{ $doc->id }}" name="code" placeholder="Ex: QMS-SOP-AIO1-001" required style="font-size:.78rem;padding:.35rem .5rem;width:190px;font-family:monospace;">
                                    <button type="submit" class="btn btn-sm">Valider</button>
                                    <a href="{{ route('documents.download', $doc) }}" class="btn btn-ghost btn-sm">Télécharger</a>
                                </div>
                                <small style="color:var(--muted);font-size:.68rem;cursor:pointer;" onclick="document.getElementById('code-input-{{ $doc->id }}').value='QMS-{{ strtoupper(substr($doc->type, 0, 3)) }}-{{ strtoupper($doc->aio) }}-{{ str_pad($doc->id, 3, '0', STR_PAD_LEFT) }}'"><i class="fas fa-lightbulb"></i> Suggestion : QMS-{{ strtoupper(substr($doc->type, 0, 3)) }}-{{ strtoupper($doc->aio) }}-{{ str_pad($doc->id, 3, '0', STR_PAD_LEFT) }}</small>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">
        {{ $documents->appends(request()->query())->links() }}
    </div>
    @endif
</div>
<style>
.empty-state {
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    padding:4rem 2rem;
    text-align:center;
}
.empty-icon {
    width:4rem;
    height:4rem;
    color:var(--muted);
    margin-bottom:1rem;
    opacity:.5;
}
.empty-title {
    font-size:1.1rem;
    font-weight:600;
    color:#e5e7eb;
    margin-bottom:.35rem;
}
.empty-desc {
    font-size:.9rem;
    color:var(--muted);
}
</style>
@endsection




