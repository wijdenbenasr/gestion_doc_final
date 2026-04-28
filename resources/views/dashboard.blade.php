@extends('layouts.app')

@section('title', 'Tableau de bord')

@section('content')
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Tableau de bord</div>
                <div class="card-sub">
                    Vue synthétique de vos documents selon votre rôle.
                </div>
            </div>
        </div>
        {{--<p style="font-size:.85rem;color:var(--muted);">
              Les tables métier (Créateur / Vérificateur / Approbateur / Admin) et le workflow
             strict seront branchés sur ce tableau.
         </p>--}}
    </div>
@endsection


