@extends('admin.layouts.app')

@section('title', 'Nouvel encaissement')
@section('page_icon', 'fa-hand-holding-usd')
@section('page_title', 'Nouvel encaissement')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances &amp; Comptabilité</li>
    <li class="breadcrumb-item"><a href="{{ route('finances.encaissements.index', ['onglet' => 'jour']) }}">Encaissements</a></li>
    <li class="breadcrumb-item active">Nouvel encaissement</li>
@endsection

@section('page_actions')
    <a href="{{ route('finances.encaissements.index', ['onglet' => 'jour']) }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Mes encaissements du jour
    </a>
@endsection

@section('css')
<style>
    .ne { --en-soft:#faf6ee; --en-border:#ece4d6; max-width:1000px; }
    .ne .carte { background:#fff; border:1px solid var(--en-border); border-radius:14px; box-shadow:0 6px 20px rgba(122,15,28,.05); margin-bottom:1.25rem; }
    .ne .carte-titre { padding:.85rem 1.15rem; border-bottom:1px solid var(--en-border); display:flex; align-items:center; justify-content:space-between; gap:.5rem; flex-wrap:wrap; }
    .ne .carte-titre h6 { margin:0; font-weight:700; font-size:.92rem; color:var(--school-ink); }
    .ne .carte-titre h6 i { color:var(--school-red); margin-right:.4rem; }
    .ne .carte-corps { padding:1rem 1.15rem; }
    .ne .etape { display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; border-radius:50%; background:var(--school-red); color:#fff; font-size:.72rem; margin-right:.4rem; }
    .badge-etat { display:inline-flex; align-items:center; gap:6px; border-radius:999px; font-size:.8rem; font-weight:600; padding:.45em .9em; }
    .badge-etat-ouverte { background:#e3f7ec; color:#1c7a4d; }
    .badge-etat-fermee  { background:#fdecef; color:var(--school-red); }
    .badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }
    .reference { font-family:monospace; font-size:.8rem; background:var(--en-soft); color:var(--school-red); padding:2px 8px; border-radius:6px; font-weight:700; }
    .nature { display:inline-block; background:var(--en-soft); color:var(--school-red); font-weight:600; font-size:.66rem; padding:.12rem .5rem; border-radius:999px; border:1px solid var(--en-border); }
    .badge-statut { display:inline-flex; align-items:center; gap:6px; padding:.3em .8em; border-radius:999px; font-size:.7rem; font-weight:600; }
    .badge-statut.attente  { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-statut.encaisse { background:#e3f7ec; color:#1c7a4d; }
    .badge-statut.annule   { background:#eceff2; color:#5b6b7a; }
    .resume-bloc { background:var(--en-soft); border:1px solid var(--en-border); border-radius:10px; padding:.55rem .8rem; height:100%; }
    .resume-titre { font-size:.68rem; text-transform:uppercase; font-weight:700; color:var(--school-muted); }
    .resume-lignes thead th { font-size:.7rem; text-transform:uppercase; color:var(--school-red); background:var(--en-soft); }
    .resume-totaux { display:flex; flex-direction:column; gap:.25rem; align-items:flex-end; font-size:.88rem; }
    .resume-totaux > div { display:flex; gap:1rem; }
    .resume-totaux .a-encaisser strong { color:var(--school-red); font-size:1.05rem; }
    .saisie-montant { background:#f2fbf6; border:1px solid #bfe6d0; border-radius:12px; padding:.9rem 1rem; }
    .saisie-montant .monnaie { font-weight:700; color:#1c7a4d; }
    .invite { text-align:center; color:#9aa7b2; padding:2rem 1rem; }
    .invite i { font-size:2rem; display:block; margin-bottom:.5rem; color:#d8c8ae; }
</style>
@endsection

@section('contenu')
<div class="ne" id="ecran-encaissement">

    <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
        <span class="badge-etat badge-etat-ouverte"><i class="fas fa-calendar-alt"></i> Année scolaire : {{ $annee->libelle }}</span>
        @if ($caisseOuverte)
            <span class="badge-etat badge-etat-ouverte" id="caisse-ouverte"><span class="badge-dot"></span> Caisse « {{ $caisseOuverte->libelle }} » ouverte</span>
        @else
            <span class="badge-etat badge-etat-fermee" id="caisse-fermee"><span class="badge-dot"></span> Aucune caisse ouverte : ouvrez votre caisse (menu Caisses) pour encaisser</span>
        @endif
    </div>

    <div class="carte" style="overflow:visible;">
        <div class="carte-titre">
            <h6><span class="etape">1</span><i class="fas fa-search"></i>Paiement en attente</h6>
            <span class="text-muted small">Par le nom de l'élève, son matricule, le payeur ou la référence</span>
        </div>
        <div class="carte-corps">
            <select id="enc-paiement" style="width:100%;" @disabled(! $caisseOuverte)></select>
        </div>
    </div>

    <div class="carte">
        <div class="carte-titre">
            <h6><span class="etape">2</span><i class="fas fa-file-invoice-dollar"></i>Détail du paiement et montant encaissé</h6>
        </div>
        <div class="carte-corps">
            <div class="invite" id="invite-paiement"><i class="fas fa-hand-pointer"></i>Choisissez un paiement en attente pour afficher son détail.</div>
            @include('domain.finances.encaissements.partials.saisie-encaissement')
        </div>
        <div class="carte-titre justify-content-end" style="border-top:1px solid var(--en-border);border-bottom:none;">
            <a href="{{ route('finances.encaissements.index', ['onglet' => 'jour']) }}" class="btn btn-annuler">Annuler</a>
            <button type="button" class="btn btn-enregistrer" id="enc-valider" disabled><i class="fas fa-check me-1"></i>Valider l'encaissement</button>
        </div>
    </div>
</div>
@endsection

@push('js')
@include('domain.finances.encaissements.partials.script-encaissement')
<script>
$(function () {
    const $select = $('#enc-paiement');
    const saisie = window.brancherEncaissement({
        $racine: $('#ecran-encaissement'),
        $valider: $('#enc-valider'),
        // Succès : retour sur « Mes encaissements du jour », où la notification s'affiche
        onSucces: message => {
            sessionStorage.setItem('pendingToast', JSON.stringify({ type: 'success', message }));
            window.location.href = '{{ route('finances.encaissements.index', ['onglet' => 'jour']) }}';
        },
    });

    $select.select2({
        placeholder: 'Rechercher par nom de l\'élève...',
        allowClear: true,
        minimumInputLength: 2,
        width: '100%',
        ajax: {
            url: '{{ route('finances.encaissements.paiements-en-attente') }}',
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term }),
            processResults: data => ({ results: data.results || [] }),
        },
        language: {
            inputTooShort: () => 'Tapez au moins 2 caractères',
            searching: () => 'Recherche...',
            noResults: () => 'Aucun paiement en attente ne correspond',
        },
    });

    $select.on('select2:select', e => { $('#invite-paiement').hide(); saisie.charger(e.params.data.id); });
    $select.on('select2:clear', () => { saisie.reinitialiser(); $('#invite-paiement').show(); });

    @if ($caisseOuverte)
        $select.select2('open');
    @endif
});
</script>
@endpush
