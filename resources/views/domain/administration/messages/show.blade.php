@extends('admin.layouts.app')

@section('page_title', $message->sujet)
@section('page_icon', 'fa-envelope-open')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('administration.messages.index') }}">Communications</a></li>
    <li class="breadcrumb-item active">Détail</li>
@endsection

@section('contenu')
    <div class="main-card p-4 mb-3">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h5 class="mb-1">{{ $message->sujet }}</h5>
                <small class="text-muted">
                    Expéditeur : {{ $message->expediteur_nom ?? '—' }} —
                    {{ $message->created_at?->format('d/m/Y H:i') }}
                </small>
            </div>
            <span class="badge-statut bg-{{ $message->statut?->couleur() ?? 'secondary' }}">
                {{ $message->statut?->libelle() }}
            </span>
        </div>

        <div class="border rounded p-3 bg-light mb-3" style="white-space:pre-wrap;">{{ $message->corps }}</div>

        <div class="row text-center">
            <div class="col"><strong>{{ $message->nb_destinataires }}</strong><br><small>Destinataires</small></div>
            <div class="col"><strong class="text-success">{{ $message->nb_envoyes }}</strong><br><small>Envoyés</small></div>
            <div class="col"><strong class="text-danger">{{ $message->nb_echecs }}</strong><br><small>Échecs</small></div>
            <div class="col"><strong>{{ $message->taux_livraison }}%</strong><br><small>Taux de livraison</small></div>
        </div>
    </div>

    <div class="main-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Destinataire</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th>Date d'envoi</th>
                        <th>Erreur</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($message->destinataires as $d)
                        <tr>
                            <td>{{ $d->nom ?? '—' }}</td>
                            <td>{{ $d->email }}</td>
                            <td>{{ $d->destinataire_type }}</td>
                            <td>
                                <span class="badge-statut bg-{{ $d->statut_envoi?->couleur() ?? 'secondary' }}">
                                    {{ $d->statut_envoi?->libelle() ?? '—' }}
                                </span>
                            </td>
                            <td>{{ $d->date_envoi?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td><small class="text-danger">{{ $d->erreur }}</small></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection