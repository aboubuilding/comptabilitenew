{{--
    Fenêtres de souscription de l'écran de saisie d'un paiement.
    Chaque souscription crée l'engagement (le montant dû) ; l'écran recharge
    aussitôt les engagements de l'élève pour que l'on puisse le régler.
--}}
@php
    $mensualite = fn ($v) => number_format((float) $v, 0, ',', ' ');
@endphp

@can('cantine.inscrire')
<div class="modal fade modal-souscription" id="modal-cantine" tabindex="-1" aria-labelledby="titre-cantine" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content form-souscription" id="form-cantine" action="{{ route('services-eleves.cantine.inscriptions.store') }}" method="POST" autocomplete="off">
            @csrf
            <input type="hidden" name="inscription_id" value="{{ $inscription->id }}">
            <div class="modal-header">
                <h5 class="modal-title" id="titre-cantine"><i class="fas fa-utensils me-2"></i>Souscrire à la cantine — {{ $eleve->nom }} {{ $eleve->prenom }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small fw-semibold" for="cantine-frais">Formule</label>
                        <select name="frais_ecole_id" id="cantine-frais" class="form-select" data-mensualite>
                            <option value="">— Montant libre —</option>
                            @foreach ($souscrire['frais_cantine'] as $f)
                                <option value="{{ $f->id }}" data-montant="{{ (float) $f->montant }}" @selected($loop->first)>{{ $f->libelle }} — {{ $mensualite($f->montant) }} / mois</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="cantine-mensuel">Montant mensuel <span class="text-danger">*</span></label>
                        <input type="number" name="montant_mensuel" id="cantine-mensuel" class="form-control" min="1" step="1" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="cantine-mois">Nombre de mois <span class="text-danger">*</span></label>
                        <input type="number" name="nombre_mois" id="cantine-mois" class="form-control" min="1" max="12" value="{{ $souscrire['mois'] }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="cantine-debut">Date de début <span class="text-danger">*</span></label>
                        <input type="date" name="date_debut" id="cantine-debut" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-12">
                        <div class="resume-du"><span>Montant dû</span><span class="montant"><span class="du-calcule">0</span> FCFA</span></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-enregistrer"><i class="fas fa-check me-1"></i>Souscrire</button>
            </div>
        </form>
    </div>
</div>
@endcan

@can('transport.abonner')
<div class="modal fade modal-souscription" id="modal-transport" tabindex="-1" aria-labelledby="titre-transport" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content form-souscription" id="form-transport" action="{{ route('services-eleves.transport.abonnements.store') }}" method="POST" autocomplete="off">
            @csrf
            <input type="hidden" name="inscription_id" value="{{ $inscription->id }}">
            <div class="modal-header">
                <h5 class="modal-title" id="titre-transport"><i class="fas fa-bus me-2"></i>Souscrire au transport scolaire — {{ $eleve->nom }} {{ $eleve->prenom }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="transport-zone">Zone</label>
                        <select name="zone_id" id="transport-zone" class="form-select" data-mensualite>
                            <option value="">— Non précisée —</option>
                            @foreach ($souscrire['zones'] as $z)
                                <option value="{{ $z->id }}" data-montant="{{ (float) $z->tarif_base ?: '' }}">
                                    {{ $z->libelle }}@if ((float) $z->tarif_base) — {{ $mensualite($z->tarif_base) }} / mois @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="transport-ligne">Ligne</label>
                        <select name="ligne_id" id="transport-ligne" class="form-select" data-mensualite>
                            <option value="">— Non précisée —</option>
                            @foreach ($souscrire['lignes'] as $l)
                                <option value="{{ $l->id }}" data-montant="{{ (float) $l->prix_minimal ?: '' }}">
                                    {{ $l->libelle }}@if ($l->prix_minimal) — à partir de {{ $mensualite($l->prix_minimal) }} / mois @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="transport-sens">Trajet <span class="text-danger">*</span></label>
                        <select name="sens" id="transport-sens" class="form-select" required>
                            @foreach ($souscrire['sens'] as $valeur => $libelle)
                                <option value="{{ $valeur }}" @selected($loop->last)>{{ $libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="transport-arret">Point d'arrêt</label>
                        <input type="text" name="point_arret" id="transport-arret" class="form-control" maxlength="200" placeholder="Ex : Carrefour Marché">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="transport-mensuel">Montant mensuel <span class="text-danger">*</span></label>
                        <input type="number" name="montant_mensuel" id="transport-mensuel" class="form-control" min="1" step="1" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="transport-mois">Nombre de mois <span class="text-danger">*</span></label>
                        <input type="number" name="nombre_mois" id="transport-mois" class="form-control" min="1" max="12" value="{{ $souscrire['mois'] }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="transport-debut">Date de début <span class="text-danger">*</span></label>
                        <input type="date" name="date_debut" id="transport-debut" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-12">
                        <div class="resume-du"><span>Montant dû</span><span class="montant"><span class="du-calcule">0</span> FCFA</span></div>
                        <div class="form-text small">Le véhicule sera affecté par le responsable du transport.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-enregistrer"><i class="fas fa-check me-1"></i>Souscrire</button>
            </div>
        </form>
    </div>
</div>
@endcan

@can('activites.inscrire')
<div class="modal fade modal-souscription" id="modal-activite" tabindex="-1" aria-labelledby="titre-activite" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content form-souscription" id="form-activite" action="{{ route('services-eleves.activites-evenements.activites.souscrire') }}" method="POST" autocomplete="off">
            @csrf
            <input type="hidden" name="eleve_id" value="{{ $eleve->id }}">
            <input type="hidden" name="date_inscription" value="{{ now()->toDateString() }}">
            <div class="modal-header">
                <h5 class="modal-title" id="titre-activite"><i class="fas fa-futbol me-2"></i>Inscrire à une activité — {{ $eleve->nom }} {{ $eleve->prenom }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                @if ($souscrire['activites']->isEmpty())
                    <div class="text-muted small"><i class="fas fa-info-circle me-1"></i>Aucune activité ouverte pour cet élève sur l'année (ou il est déjà inscrit à toutes).</div>
                @endif
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small fw-semibold" for="souscrire-activite">Activité <span class="text-danger">*</span></label>
                        <select name="activite_id" id="souscrire-activite" class="form-select" required>
                            <option value="">— Choisir —</option>
                            @foreach ($souscrire['activites'] as $a)
                                <option value="{{ $a->id }}" data-montant="{{ (float) $a->montant }}">
                                    {{ $a->libelle }} — {{ $mensualite($a->montant) }}@if ($a->encadreur) · {{ $a->encadreur }}@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="activite-montant">Montant dû</label>
                        <input type="number" name="montant_du" id="activite-montant" class="form-control" min="0" step="1" placeholder="Tarif de l'activité">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-enregistrer"><i class="fas fa-check me-1"></i>Inscrire</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade modal-souscription" id="modal-evenement" tabindex="-1" aria-labelledby="titre-evenement" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content form-souscription" id="form-evenement" action="{{ route('services-eleves.activites-evenements.evenements.preInscrire') }}" method="POST" autocomplete="off">
            @csrf
            <input type="hidden" name="inscription_id" value="{{ $inscription->id }}">
            <div class="modal-header">
                <h5 class="modal-title" id="titre-evenement"><i class="fas fa-calendar-check me-2"></i>Inscrire à un événement — {{ $eleve->nom }} {{ $eleve->prenom }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                @if ($souscrire['evenements']->isEmpty())
                    <div class="text-muted small"><i class="fas fa-info-circle me-1"></i>Aucun événement à venir sur l'année.</div>
                @endif
                <label class="form-label small fw-semibold" for="souscrire-evenement">Événement <span class="text-danger">*</span></label>
                <select name="evenement_id" id="souscrire-evenement" class="form-select" required>
                    <option value="">— Choisir —</option>
                    @foreach ($souscrire['evenements'] as $e)
                        @php
                            $complet = $e->isCapaciteLimitee() && $e->places_restantes !== null && $e->places_restantes <= 0;
                            $clos = $e->isCapaciteLimitee() && $e->isDateLimitePassee();
                        @endphp
                        <option value="{{ $e->id }}" data-montant="{{ (float) $e->participation }}" data-libelle="{{ $e->nom }}"
                                data-limitee="{{ $e->isCapaciteLimitee() ? 1 : 0 }}" @disabled($complet || $clos)>
                            {{ $e->nom }}@if ($e->date_evenement) ({{ $e->date_evenement->format('d/m/Y') }})@endif — {{ $mensualite($e->participation) }}
                            @if ($complet) · complet @elseif ($clos) · inscriptions closes @elseif ($e->places_restantes !== null) · {{ $e->places_restantes }} place(s) @endif
                        </option>
                    @endforeach
                </select>
                <div class="resume-du mt-3" id="evenement-info" style="display:none;"><span>Participation</span><span class="montant"><span class="valeur-participation">0</span> FCFA</span></div>
                <div class="form-text small mt-2" id="evenement-mode"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-enregistrer"><i class="fas fa-check me-1"></i>Inscrire</button>
            </div>
        </form>
    </div>
</div>
@endcan
