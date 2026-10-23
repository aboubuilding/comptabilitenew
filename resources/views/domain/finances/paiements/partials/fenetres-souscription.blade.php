{{--
    Fenêtres de souscription de l'écran de saisie d'un paiement.
    Chaque souscription crée l'engagement (le montant dû) ; l'écran recharge
    aussitôt les engagements de l'élève pour que l'on puisse le régler.
--}}
@php
    $mensualite = fn ($v) => number_format((float) $v, 0, ',', ' ');
@endphp

@can('cantine.inscrire')
<x-modale class="modal-souscription" id="modal-cantine" aria-labelledby="titre-cantine" aria-hidden="true" form-contenu croix-blanche>
    <x-slot:formulaire class="form-souscription" id="form-cantine" action="{{ route('services-eleves.cantine.inscriptions.store') }}" method="POST" autocomplete="off">@csrf
            <input type="hidden" name="inscription_id" value="{{ $inscription->id }}"></x-slot:formulaire>
    <x-slot:titre id="titre-cantine"><i class="fas fa-utensils me-2"></i>Souscrire à la cantine — {{ $eleve->nom }} {{ $eleve->prenom }}</x-slot:titre>
    <div class="row g-3">
        <div class="col-12">
            <x-libelle class="small fw-semibold" for="cantine-frais">Formule</x-libelle>
            <select name="frais_ecole_id" id="cantine-frais" class="form-select" data-mensualite>
                <option value="">— Montant libre —</option>
                @foreach ($souscrire['frais_cantine'] as $f)
                    <option value="{{ $f->id }}" data-montant="{{ (float) $f->montant }}" @selected($loop->first)>{{ $f->libelle }} — {{ $mensualite($f->montant) }} / mois</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <x-libelle class="small fw-semibold" for="cantine-mensuel" requis>Montant mensuel</x-libelle>
            <input type="number" name="montant_mensuel" id="cantine-mensuel" class="form-control" min="1" step="1" required>
        </div>
        <div class="col-md-6">
            <x-libelle class="small fw-semibold" for="cantine-mois" requis>Nombre de mois</x-libelle>
            <input type="number" name="nombre_mois" id="cantine-mois" class="form-control" min="1" max="12" value="{{ $souscrire['mois'] }}" data-fin-annee="{{ $souscrire['fin_annee'] }}" required>
            @if ($souscrire['fin_annee'])<div class="form-text small">Calculé jusqu'à la fin de l'année scolaire ({{ \Illuminate\Support\Carbon::parse($souscrire['fin_annee'])->format('d/m/Y') }}).</div>@endif
        </div>
        <div class="col-md-6">
            <x-libelle class="small fw-semibold" for="cantine-debut" requis>Date de début</x-libelle>
            <input type="date" name="date_debut" id="cantine-debut" class="form-control" value="{{ now()->toDateString() }}" required>
        </div>
        <div class="col-12">
            <div class="resume-du"><span>Montant dû</span><span class="montant"><span class="du-calcule">0</span> FCFA</span></div>
        </div>
    </div>
    <x-slot:pied>
        <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
        <x-bouton type="submit"><i class="fas fa-check me-1"></i>Souscrire</x-bouton>
    </x-slot:pied>
</x-modale>
@endcan

@can('transport.abonner')
<x-modale class="modal-souscription" id="modal-transport" aria-labelledby="titre-transport" aria-hidden="true" dialogue="modal-dialog-centered modal-lg" form-contenu croix-blanche>
    <x-slot:formulaire class="form-souscription" id="form-transport" action="{{ route('services-eleves.transport.abonnements.store') }}" method="POST" autocomplete="off">@csrf
            <input type="hidden" name="inscription_id" value="{{ $inscription->id }}"></x-slot:formulaire>
    <x-slot:titre id="titre-transport"><i class="fas fa-bus me-2"></i>Souscrire au transport scolaire — {{ $eleve->nom }} {{ $eleve->prenom }}</x-slot:titre>
    <div class="row g-3">
        <div class="col-md-8">
            <x-libelle class="small fw-semibold" for="transport-zone">Zone</x-libelle>
            <select name="zone_id" id="transport-zone" class="form-select" data-mensualite>
                <option value="">— Non précisée —</option>
                @foreach ($souscrire['zones'] as $z)
                    <option value="{{ $z->id }}" data-montant="{{ (float) $z->tarif_base ?: '' }}">
                        {{ $z->libelle }}@if ((float) $z->tarif_base) — {{ $mensualite($z->tarif_base) }} / mois (indicatif)@endif
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <x-libelle class="small fw-semibold" for="transport-sens" requis>Trajet</x-libelle>
            <select name="sens" id="transport-sens" class="form-select" required>
                @foreach ($souscrire['sens'] as $valeur => $libelle)
                    <option value="{{ $valeur }}" @selected($loop->last)>{{ $libelle }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <x-libelle class="small fw-semibold" for="transport-mensuel" requis>Montant mensuel</x-libelle>
            <input type="number" name="montant_mensuel" id="transport-mensuel" class="form-control" min="1" step="1" required>
            <div class="form-text small">Proposé d'après la zone, modifiable.</div>
        </div>
        <div class="col-md-4">
            <x-libelle class="small fw-semibold" for="transport-mois" requis>Nombre de mois</x-libelle>
            <input type="number" name="nombre_mois" id="transport-mois" class="form-control" min="1" max="12" value="{{ $souscrire['mois'] }}" data-fin-annee="{{ $souscrire['fin_annee'] }}" required>
            @if ($souscrire['fin_annee'])<div class="form-text small">Calculé jusqu'à la fin de l'année scolaire ({{ \Illuminate\Support\Carbon::parse($souscrire['fin_annee'])->format('d/m/Y') }}).</div>@endif
        </div>
        <div class="col-md-4">
            <x-libelle class="small fw-semibold" for="transport-debut" requis>Date de début</x-libelle>
            <input type="date" name="date_debut" id="transport-debut" class="form-control" value="{{ now()->toDateString() }}" required>
        </div>
        <div class="col-12">
            <div class="resume-du"><span>Montant dû</span><span class="montant"><span class="du-calcule">0</span> FCFA</span></div>
            <div class="form-text small">La ligne et le véhicule sont ceux de la zone ; la position GPS du domicile se renseigne ensuite, dans les abonnements bus.</div>
        </div>
    </div>
    <x-slot:pied>
        <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
        <x-bouton type="submit"><i class="fas fa-check me-1"></i>Souscrire</x-bouton>
    </x-slot:pied>
</x-modale>
@endcan

@can('activites.inscrire')
<x-modale class="modal-souscription" id="modal-activite" aria-labelledby="titre-activite" aria-hidden="true" form-contenu croix-blanche>
    <x-slot:formulaire class="form-souscription" id="form-activite" action="{{ route('services-eleves.activites-evenements.activites.souscrire') }}" method="POST" autocomplete="off">@csrf
            <input type="hidden" name="eleve_id" value="{{ $eleve->id }}">
            <input type="hidden" name="date_inscription" value="{{ now()->toDateString() }}"></x-slot:formulaire>
    <x-slot:titre id="titre-activite"><i class="fas fa-futbol me-2"></i>Inscrire à une activité — {{ $eleve->nom }} {{ $eleve->prenom }}</x-slot:titre>
    @if ($souscrire['activites']->isEmpty())
        <div class="text-muted small"><i class="fas fa-info-circle me-1"></i>Aucune activité ouverte pour cet élève sur l'année (ou il est déjà inscrit à toutes).</div>
    @endif
    <div class="row g-3">
        <div class="col-12">
            <x-libelle class="small fw-semibold" for="souscrire-activite" requis>Activité</x-libelle>
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
            <x-libelle class="small fw-semibold" for="activite-montant">Montant dû</x-libelle>
            <input type="number" name="montant_du" id="activite-montant" class="form-control" min="0" step="1" placeholder="Tarif de l'activité">
        </div>
    </div>
    <x-slot:pied>
        <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
        <x-bouton type="submit"><i class="fas fa-check me-1"></i>Inscrire</x-bouton>
    </x-slot:pied>
</x-modale>

<x-modale class="modal-souscription" id="modal-evenement" aria-labelledby="titre-evenement" aria-hidden="true" form-contenu croix-blanche>
    <x-slot:formulaire class="form-souscription" id="form-evenement" action="{{ route('services-eleves.activites-evenements.evenements.preInscrire') }}" method="POST" autocomplete="off">@csrf
            <input type="hidden" name="inscription_id" value="{{ $inscription->id }}"></x-slot:formulaire>
    <x-slot:titre id="titre-evenement"><i class="fas fa-calendar-check me-2"></i>Inscrire à un événement — {{ $eleve->nom }} {{ $eleve->prenom }}</x-slot:titre>
    @if ($souscrire['evenements']->isEmpty())
        <div class="text-muted small"><i class="fas fa-info-circle me-1"></i>Aucun événement à venir sur l'année.</div>
    @endif
    <x-libelle class="small fw-semibold" for="souscrire-evenement" requis>Événement</x-libelle>
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
    <x-slot:pied>
        <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
        <x-bouton type="submit"><i class="fas fa-check me-1"></i>Inscrire</x-bouton>
    </x-slot:pied>
</x-modale>
@endcan
