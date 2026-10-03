@extends('admin.layouts.app')

@section('page_title', 'Historique des communications')
@section('page_icon', 'fa-clock-rotate-left')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('administration.messages.index') }}">Communications</a></li>
    <li class="breadcrumb-item active">Historique</li>
@endsection

@section('contenu')
    <div class="main-card p-3 mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label small">Email du destinataire</label>
                <input type="email" name="email" class="form-control filtre-input"
                       value="{{ $email ?? '' }}" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn-filtrer w-100"><i class="fa fa-search"></i> Rechercher</button>
            </div>
        </form>
    </div>

    @if($email)
        <div class="main-card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Date</th><th>Sujet</th><th>Statut</th></tr>
                    </thead>
                    <tbody>
                        @forelse($lignes as $l)
                            <tr>
                                <td>{{ $l->date_envoi?->format('d/m/Y H:i') ?? $l->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('administration.messages.show', $l->message) }}">
                                        {{ $l->message->sujet ?? '—' }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge-statut bg-{{ $l->statut_envoi?->couleur() ?? 'secondary' }}">
                                        {{ $l->statut_envoi?->libelle() ?? '—' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center py-4 text-muted">Aucune communication pour cet email.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection