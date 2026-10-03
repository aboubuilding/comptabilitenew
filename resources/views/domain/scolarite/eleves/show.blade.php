@extends('admin.layouts.app')

@section('page_title', $eleve->nom_complet)
@section('page_icon', 'fa-user-graduate')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('scolarite.eleves.index') }}">Élèves</a></li>
    <li class="breadcrumb-item active">{{ $eleve->matricule }}</li>
@endsection

@section('page_actions')
    <a href="{{ route('scolarite.eleves.fiche-pdf', $eleve) }}" class="btn-outline-scolarite">
        <i class="fa fa-file-pdf"></i> Fiche PDF
    </a>
    <a href="{{ route('scolarite.eleves.index') }}" class="btn-annuler ms-2">Retour</a>
@endsection

@section('contenu')
    <div class="row g-3">
        {{-- Carte identité --}}
        <div class="col-md-4">
            <div class="main-card p-4 text-center">
                @if($eleve->photo)
                    <img src="{{ Storage::url($eleve->photo) }}" class="rounded-circle mb-3"
                         style="width:120px;height:120px;object-fit:cover;">
                @else
                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto mb-3"
                         style="width:120px;height:120px;font-size:48px;">
                        {{ strtoupper(substr($eleve->nom ?? '?', 0, 1)) }}
                    </div>
                @endif
                <h5 class="mb-1">{{ $eleve->nom_complet }}</h5>
                <code>{{ $eleve->matricule }}</code>
                <div class="mt-3">
                    <span class="badge-statut bg-{{ $eleve->etat ? 'success' : 'secondary' }}">
                        {{ $eleve->etat ? 'Actif' : 'Inactif' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Détails --}}
        <div class="col-md-8">
            <div class="main-card p-4">

                <h6 class="mb-3"><i class="fa fa-school"></i> Scolarité (année active)</h6>
                @if($eleve->inscriptionActive)
                    <div class="row g-2 mb-4">
                        <div class="col-md-4">
                            <small class="text-muted">Classe</small><br>
                            @if($eleve->inscriptionActive->classe)
                                <span class="badge-statut bg-primary">{{ $eleve->inscriptionActive->classe->libelle }}</span>
                            @else
                                <span class="badge-statut bg-warning text-dark">Non affecté</span>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Niveau</small><br>
                            <strong>{{ $eleve->inscriptionActive->niveau->libelle ?? '—' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Cycle</small><br>
                            <strong>{{ $eleve->inscriptionActive->cycle->libelle ?? '—' }}</strong>
                        </div>
                    </div>
                @else
                    <p class="text-muted">Aucune inscription validée pour l'année active.</p>
                @endif

                <h6 class="mb-3"><i class="fa fa-coins"></i> Situation financière</h6>
                <div class="row g-2 mb-4">
                    <div class="col-md-6">
                        <div class="border rounded p-3 {{ ($eleve->montant_du ?? 0) > 0 ? 'border-danger' : 'border-success' }}">
                            <small class="text-muted">Montant dû</small>
                            <div class="h5 mb-0 {{ ($eleve->montant_du ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($eleve->montant_du ?? 0, 0, ',', ' ') }} FCFA
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3">
                            <small class="text-muted">Total encaissé</small>
                            <div class="h5 mb-0 text-success">
                                {{ number_format($eleve->montant_encaisse ?? 0, 0, ',', ' ') }} FCFA
                            </div>
                        </div>
                    </div>
                </div>

                <h6 class="mb-3"><i class="fa fa-id-card"></i> État civil</h6>
                <div class="row g-2 mb-4">
                    <div class="col-md-6"><small class="text-muted">Sexe</small><br><strong>{{ $eleve->sexe?->libelle() ?? '—' }}</strong></div>
                    <div class="col-md-6"><small class="text-muted">Date de naissance</small><br><strong>{{ $eleve->date_naissance?->format('d/m/Y') ?? '—' }} ({{ $eleve->age }} ans)</strong></div>
                    <div class="col-md-6"><small class="text-muted">Lieu de naissance</small><br><strong>{{ $eleve->lieu_naissance ?? '—' }}</strong></div>
                    <div class="col-md-6"><small class="text-muted">Nationalité</small><br><strong>{{ $eleve->nationalite->libelle ?? '—' }}</strong></div>
                    <div class="col-md-6"><small class="text-muted">École de provenance</small><br><strong>{{ $eleve->ecole_provenance ?? '—' }}</strong></div>
                </div>

                <h6 class="mb-3"><i class="fa fa-phone"></i> Personne à prévenir</h6>
                <div class="row g-2 mb-4">
                    <div class="col-md-4"><small class="text-muted">Nom</small><br><strong>{{ $eleve->personne_prevenir ?? '—' }}</strong></div>
                    <div class="col-md-4"><small class="text-muted">Téléphone</small><br><strong>{{ $eleve->numero_personne_prevenir ?? '—' }}</strong></div>
                    <div class="col-md-4">
                        <small class="text-muted">Lien</small><br>
                        <strong>
                            {{ $eleve->lien_parente_personne
                                ? (\App\Domain\Scolarite\Types\LienParente::tryFrom($eleve->lien_parente_personne)?->libelle() ?? '—')
                                : '—' }}
                        </strong>
                    </div>
                </div>

                @if($eleve->allergie || $eleve->nom_medecin)
                    <h6 class="mb-3"><i class="fa fa-stethoscope"></i> Informations médicales</h6>
                    <div class="row g-2">
                        <div class="col-md-6"><small class="text-muted">Médecin</small><br><strong>{{ $eleve->nom_medecin ?? '—' }} {{ $eleve->numero_medecin ? '('.$eleve->numero_medecin.')' : '' }}</strong></div>
                        <div class="col-md-6"><small class="text-muted">Allergies</small><br><strong>{{ $eleve->allergie ?? '—' }}</strong></div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection