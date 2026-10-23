@extends('admin.layouts.app')

@section('title', 'Prêts en cours')
@section('page_icon', 'fa-exchange-alt')
@section('page_title', 'Prêts de la bibliothèque')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.bibliotheque.livres.index') }}">Bibliothèque</a></li>
    <li class="breadcrumb-item active">Prêts</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('services-eleves.bibliotheque.prets.export', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('services-eleves.bibliotheque.livres.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-book me-1"></i> Catalogue
    </x-bouton>
    @can('bibliotheque.gerer')
        <form action="{{ route('services-eleves.bibliotheque.prets.recalculerRetards') }}" method="POST" class="d-inline">
            @csrf
            <x-bouton variante="contour" class="btn-sm rounded-pill px-3" type="submit">
                <i class="fas fa-sync-alt me-1"></i> Recalculer retards
            </x-bouton>
        </form>
    @endcan
@endsection

@section('css')
<style>
    /* Réutilise les classes biblio-page */

    .alerte-retards {
        background:#fdecea; border-left:3px solid var(--school-red);
        border-radius:8px; padding:.85rem 1rem; margin-bottom:1rem;
        font-size:.85rem; color:var(--school-red-dark);
    }
</style>
@endsection

@section('contenu')
<div class="biblio-page">

    @if ($retards->isNotEmpty())
        <div class="alerte-retards">
            <i class="fas fa-exclamation-triangle me-1"></i>
            <strong>{{ $retards->count() }} prêt(s)</strong> en retard :
            @foreach ($retards->take(5) as $r)
                <x-badge class="retard ms-1" style="background:#fff;border:1px solid var(--school-red);">
                    {{ $r->livre?->titre }} — {{ $r->inscription?->eleve?->nom }} ({{ $r->jours_retard }} j)
                </x-badge>
            @endforeach
            @if ($retards->count() > 5)
                <span class="ms-1">+{{ $retards->count() - 5 }} autre(s)</span>
            @endif
        </div>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-exchange-alt" couleur="blue" libelle="Prêts au total">{{ $stats['total'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-hand-holding" couleur="gold" libelle="En cours">{{ $stats['en_cours'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-clock" couleur="red" libelle="En retard">{{ $stats['en_retard'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-check" couleur="green" libelle="Rendus">{{ $stats['rendus'] }}</x-stat>
        </div>
    </div>

    {{-- Tableau --}}
    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <x-recherche name="search" placeholder="Livre, élève, matricule..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-3">
                    <select name="statut_pret" class="form-select filtre-input">
                        <option value="">— Statut —</option>
                        @foreach ($statuts as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['statut_pret'] ?? '') == $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="en_retard" value="1" id="enRetard" @checked($filters['en_retard'] ?? false)>
                        <label class="form-check-label small" for="enRetard">En retard uniquement</label>
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <x-bouton variante="filtrer" type="submit" class="flex-fill"><i class="fas fa-filter me-1"></i> Filtrer</x-bouton>
                    <x-bouton variante="contour" href="{{ route('services-eleves.bibliotheque.prets.index') }}"><i class="fas fa-undo"></i></x-bouton>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-livres align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Livre</th>
                            <th>Élève</th>
                            <th>Date prêt</th>
                            <th>Retour prévu</th>
                            <th>Retour réel</th>
                            <th>Statut</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($prets as $pret)
                        <tr>
                            <td class="ps-4">
                                <span class="titre-livre">{{ $pret->livre?->titre ?? '—' }}</span>
                                <span class="sub">{{ $pret->livre?->auteur?->nom_prenom }}</span>
                            </td>
                            <td class="text-sm">
                                <div class="fw-semibold">
                                    {{ trim(($pret->inscription?->eleve?->nom ?? '') . ' ' . ($pret->inscription?->eleve?->prenom ?? '')) }}
                                </div>
                                <div class="text-xs text-muted">
                                    {{ $pret->inscription?->eleve?->matricule }}
                                    @if ($pret->inscription?->classe) · {{ $pret->inscription->classe->libelle }} @endif
                                </div>
                            </td>
                            <td class="text-sm">{{ optional($pret->date_pret)->format('d/m/Y') }}</td>
                            <td class="text-sm">
                                {{ optional($pret->date_retour_pret)->format('d/m/Y') }}
                                @if ($pret->isEnRetard())
                                    <div class="text-xs" style="color:var(--school-red);font-weight:700;">
                                        {{ $pret->jours_retard }} j de retard
                                    </div>
                                @endif
                            </td>
                            <td class="text-sm">{{ optional($pret->date_retour_reel)->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                <x-badge class="{{ $pret->statut_pret?->css() }}" point>{{ $pret->statut_pret?->label() }}
                                </x-badge>
                            </td>
                            <td class="text-center pe-4">
                                <x-actions>
                                        @if ($pret->isEnCours())
                                            @can('bibliotheque.gerer')
                                                <li>
                                                    <button class="dropdown-item btn-retour"
                                                            data-id="{{ $pret->id }}"
                                                            data-livre="{{ $pret->livre?->titre }}"
                                                            data-bs-toggle="modal" data-bs-target="#retourModal">
                                                        <i class="fas fa-check"></i> Retour
                                                    </button>
                                                </li>
                                                <li>
                                                    <button class="dropdown-item text-danger btn-perdu"
                                                            data-id="{{ $pret->id }}"
                                                            data-livre="{{ $pret->livre?->titre }}"
                                                            data-bs-toggle="modal" data-bs-target="#perduModal">
                                                        <i class="fas fa-times"></i> Marquer perdu
                                                    </button>
                                                </li>
                                            @endcan
                                        @else
                                            <li>
                                                <span class="dropdown-item text-muted small" style="cursor:default;">
                                                    <i class="fas fa-info-circle"></i> Prêt clôturé
                                                </span>
                                            </li>
                                        @endif
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="7" icone="fas fa-exchange-alt">Aucun prêt.</x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$prets->appends(request()->query())" /></div>

    {{-- Modal retour --}}
    @can('bibliotheque.gerer')
    <x-modale id="retourModal" aria-hidden="true" contenu-class="modal-livres" entete-class="modal-header-livres" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="retour-form" method="POST" action="">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-check me-2"></i>Enregistrer le retour</x-slot:titre>
        <p class="mb-2">Retour du livre : <strong id="retour-livre">—</strong></p>
        <x-section>
            <div class="mb-3">
                <x-libelle>Date de retour réel</x-libelle>
                <input type="date" name="date_retour_reel" class="form-control" value="{{ now()->toDateString() }}">
            </div>
            <div class="mb-0">
                <x-libelle>Observations</x-libelle>
                <textarea name="observations" rows="2" class="form-control" maxlength="2000"></textarea>
            </div>
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <x-bouton type="submit"><i class="fas fa-check me-1"></i> Confirmer</x-bouton>
        </x-slot:pied>
    </x-modale>
    @endcan

    {{-- Modal perdu --}}
    @can('bibliotheque.gerer')
    <x-modale id="perduModal" aria-hidden="true" contenu-class="modal-livres" entete-style="background:linear-gradient(135deg,var(--school-red-deep),var(--school-red-dark));color:#fff;" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="perdu-form" method="POST" action="">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-times me-2"></i>Marquer comme perdu</x-slot:titre>
        <p>Marquer le livre <strong id="perdu-livre">—</strong> comme perdu ?</p>
        <p class="text-muted small">L'exemplaire sera définitivement retiré du stock.</p>
        <x-section>
            <x-libelle requis>Motif / observations</x-libelle>
            <textarea name="observations" rows="3" class="form-control" required minlength="5"></textarea>
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <button type="submit" class="btn" style="background:var(--school-red-dark);color:#fff;">Confirmer</button>
        </x-slot:pied>
    </x-modale>
    @endcan
</div>
@endsection

@push('js')
<script>
$(function () {
    $('.btn-retour').on('click', function () {
        $('#retour-livre').text($(this).data('livre'));
        $('#retour-form').attr('action', `{{ url('services-eleves/bibliotheque/prets') }}/${$(this).data('id')}/retour`);
    });

    $('#retour-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'), method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#retourModal').modal('hide');
                window.showToastThenReload(res.message || 'Retour enregistré.', 'success');
            },
            error: function (xhr) {
                window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error');
            }
        });
    });

    $('.btn-perdu').on('click', function () {
        $('#perdu-livre').text($(this).data('livre'));
        $('#perdu-form').attr('action', `{{ url('services-eleves/bibliotheque/prets') }}/${$(this).data('id')}/perdu`);
    });

    $('#perdu-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'), method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#perduModal').modal('hide');
                window.showToastThenReload(res.message || 'Prêt marqué perdu.', 'success');
            },
            error: function (xhr) {
                window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error');
            }
        });
    });
});
</script>
@endpush