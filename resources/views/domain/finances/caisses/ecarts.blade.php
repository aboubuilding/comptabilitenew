@extends('admin.layouts.app')

@section('title', 'Écarts de caisse')
@section('page_icon', 'fa-balance-scale')
@section('page_title', 'Validation des écarts de caisse')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('finances.caisses.index') }}">Caisses</a></li>
    <li class="breadcrumb-item active">Écarts</li>
@endsection

@section('css')
<style>
    .ecarts-page { --ec-bg-soft:#faf6ee; --ec-border:#ece4d6; }
    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--ec-border) !important; }
    .filtre-input { border-radius:10px; border:1px solid var(--ec-border); padding:.55rem .9rem; font-size:.88rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-outline-scolarite { border:1px solid var(--ec-border); color:var(--school-ink); background:#fff; font-size:.82rem; }

    .stat-box { background:#fff; border:1px solid var(--ec-border); border-radius:14px; padding:1rem 1.1rem; }
    .stat-box .stat-value { font-size:1.5rem; font-weight:700; }
    .stat-box .stat-label { font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:var(--school-muted); }

    .table-ecarts thead tr { background:var(--ec-bg-soft); }
    .table-ecarts thead th { color:var(--school-red); font-size:.74rem; font-weight:700; text-transform:uppercase; padding:.85rem .9rem; border-bottom:2px solid var(--ec-border); white-space:nowrap; }
    .table-ecarts tbody td { padding:.85rem .9rem; border-bottom:1px solid var(--ec-border); font-size:.88rem; color:var(--school-ink); vertical-align:top; }
    .num { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
    .ecart-montant { font-weight:700; }
    .ecart-montant.manquant { color:#b13b3b; }
    .ecart-montant.excedent { color:#1c7a4d; }
    .motif { max-width:260px; color:#5b6b7a; font-size:.84rem; }

    .badge-ecart { display:inline-flex; align-items:center; font-size:.72rem; font-weight:600; padding:.35em .75em; border-radius:999px; white-space:nowrap; }
    .badge-ecart-attente { background:#fdf3e2; color:#b8720b; }
    .badge-ecart-enquete { background:#e0ecfc; color:#2563eb; }
    .badge-ecart-valide  { background:#e3f7ec; color:#1c7a4d; }
    .badge-ecart-refuse  { background:#fbeaea; color:#b13b3b; }

    .btn-statuer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; font-size:.8rem; padding:.4rem .9rem; white-space:nowrap; }
    .btn-statuer:hover { color:#fff; }
    .empty-state { padding:3.5rem 1rem; text-align:center; color:#9aa7b2; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }
    .btn-annuler { background:#fff; border:1px solid var(--ec-border); color:#5b6b7a; border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .resume-ecart { background:var(--ec-bg-soft); border:1px solid var(--ec-border); border-radius:12px; padding:.9rem 1rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="ecarts-page">

    <div class="alert alert-light border-0 small mb-3" style="background:#f0f6ff;color:#1e3a8a;border-left:3px solid #2563eb;">
        <i class="fas fa-info-circle me-1"></i>
        Tout écart constaté à la clôture d'une caisse est motivé par le caissier puis soumis à la Direction.
        Statuer sur un écart ne modifie aucun solde : le journal de la caisse reste figé.
    </div>

    @if ($errors->any())
        <div class="alert alert-danger py-2 small">
            <i class="fas fa-exclamation-triangle me-1"></i> {{ $errors->first() }}
        </div>
    @endif

    {{-- Compteurs --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-box">
                <div class="stat-value" style="color:#b8720b;">{{ $compteurs['en_attente'] }}</div>
                <div class="stat-label">En attente de décision</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-box">
                <div class="stat-value" style="color:#2563eb;">{{ $compteurs['enquete'] }}</div>
                <div class="stat-label">En cours de vérification</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-box">
                <div class="stat-value" style="color:#1c7a4d;">{{ $compteurs['valide'] }}</div>
                <div class="stat-label">Validés</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-box">
                <div class="stat-value" style="color:#b13b3b;">{{ $compteurs['refuse'] }}</div>
                <div class="stat-label">Refusés</div>
            </div>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <select name="statut_ecart" class="form-select filtre-input">
                        <option value="">À traiter (en attente ou en vérification)</option>
                        <option value="tous" @selected(($filters['statut_ecart'] ?? '') === 'tous')>Tous les écarts</option>
                        @foreach ($statuts as $valeur => $libelle)
                            <option value="{{ $valeur }}" @selected(($filters['statut_ecart'] ?? '') === $valeur)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <select name="responsable_id" class="form-select filtre-input">
                        <option value="">— Tous les caissiers —</option>
                        @foreach ($caissiers as $caissier)
                            <option value="{{ $caissier->id }}" @selected(($filters['responsable_id'] ?? '') == $caissier->id)>
                                {{ $caissier->nom }} {{ $caissier->prenom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-filtrer flex-fill"><i class="fas fa-filter me-1"></i> Filtrer</button>
                    <a href="{{ route('finances.caisses.ecarts.index') }}" class="btn btn-outline-scolarite"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-ecarts align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Caisse</th>
                            <th>Caissier</th>
                            <th>Clôture</th>
                            <th class="text-end">Solde théorique</th>
                            <th class="text-end">Solde compté</th>
                            <th class="text-end">Écart</th>
                            <th>Motif du caissier</th>
                            <th>Statut</th>
                            <th class="text-center pe-4">Décision</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($ecarts as $caisse)
                        @php $ecart = (float) $caisse->ecart; @endphp
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('finances.caisses.show', $caisse) }}" class="fw-semibold" style="color:var(--school-ink);">
                                    {{ $caisse->libelle }}
                                </a>
                                <div class="text-muted small">#{{ $caisse->id }}</div>
                            </td>
                            <td>{{ $caisse->caissier?->nom }} {{ $caisse->caissier?->prenom }}</td>
                            <td>{{ optional($caisse->date_cloture)->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="num">{{ number_format((float) $caisse->solde_final, 2, ',', ' ') }}</td>
                            <td class="num">{{ number_format((float) $caisse->solde_compte, 2, ',', ' ') }}</td>
                            <td class="num">
                                <span class="ecart-montant {{ $ecart < 0 ? 'manquant' : 'excedent' }}">
                                    {{ $ecart > 0 ? '+' : '' }}{{ number_format($ecart, 2, ',', ' ') }}
                                </span>
                                <div class="text-muted small">{{ $ecart < 0 ? 'Manquant' : 'Excédent' }}</div>
                            </td>
                            <td><div class="motif">{{ $caisse->motif_ecart ?? '—' }}</div></td>
                            <td>
                                <span class="badge-ecart badge-ecart-{{ $caisse->statut_ecart?->css() }}">
                                    {{ $caisse->statut_ecart?->label() }}
                                </span>
                                @if ($caisse->valide_par)
                                    <div class="text-muted small mt-1">
                                        par {{ $caisse->valideur?->nom }} {{ $caisse->valideur?->prenom }}
                                        @if ($caisse->date_validation_ecart)
                                            le {{ $caisse->date_validation_ecart->format('d/m/Y') }}
                                        @endif
                                    </div>
                                @endif
                                @if ($caisse->commentaire_validation_ecart)
                                    <div class="motif mt-1"><i class="fas fa-comment-dots me-1"></i>{{ $caisse->commentaire_validation_ecart }}</div>
                                @endif
                            </td>
                            <td class="text-center pe-4">
                                @if ($caisse->ecartEnInstance())
                                    <button type="button" class="btn btn-statuer"
                                            data-bs-toggle="modal" data-bs-target="#statuerModal"
                                            data-url="{{ route('finances.caisses.ecarts.statuer', $caisse) }}"
                                            data-libelle="{{ $caisse->libelle }}"
                                            data-caissier="{{ $caisse->caissier?->nom }} {{ $caisse->caissier?->prenom }}"
                                            data-ecart="{{ ($ecart > 0 ? '+' : '') . number_format($ecart, 2, ',', ' ') }}"
                                            data-motif="{{ $caisse->motif_ecart }}">
                                        <i class="fas fa-gavel me-1"></i> Statuer
                                    </button>
                                @else
                                    <span class="text-muted small">Décision rendue</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <i class="fas fa-check-circle"></i>
                                    Aucun écart de caisse à traiter.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $ecarts->links() }}</div>

    {{-- Modale de décision (une seule, réutilisée) --}}
    @can('caisses.valider_ecart')
    <div class="modal fade" id="statuerModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;">
                <form method="POST" action="" id="statuer-form">
                    @csrf
                    <div class="modal-header" style="background:linear-gradient(135deg,var(--school-red-dark),var(--school-red));color:#fff;border-bottom:none;">
                        <h5 class="modal-title"><i class="fas fa-gavel me-2"></i>Statuer sur l'écart</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="resume-ecart mb-3">
                            <div class="d-flex justify-content-between">
                                <span><strong id="statuer-libelle">—</strong><br><span class="text-muted" id="statuer-caissier">—</span></span>
                                <span class="text-end"><span class="text-muted small">Écart</span><br><strong id="statuer-ecart">—</strong></span>
                            </div>
                            <div class="mt-2 text-muted small"><i class="fas fa-quote-left me-1"></i><span id="statuer-motif">—</span></div>
                        </div>

                        <label class="form-label fw-semibold">Décision <span class="text-danger">*</span></label>
                        @foreach ($decisions as $decision)
                            <div class="form-check mb-1">
                                <input class="form-check-input decision-radio" type="radio" name="decision" required
                                       id="decision-{{ $decision->value }}" value="{{ $decision->value }}"
                                       @checked($loop->first)>
                                <label class="form-check-label" for="decision-{{ $decision->value }}">
                                    @switch($decision)
                                        @case(\App\Domain\Finances\Types\StatutEcartCaisse::VALIDE)
                                            <strong>Valider</strong> — la justification du caissier est acceptée
                                            @break
                                        @case(\App\Domain\Finances\Types\StatutEcartCaisse::REFUSE)
                                            <strong>Refuser</strong> — la justification n'est pas acceptée
                                            @break
                                        @default
                                            <strong>Demander une vérification</strong> — l'écart reste en instance
                                    @endswitch
                                </label>
                            </div>
                        @endforeach

                        <div class="mt-3">
                            <label class="form-label fw-semibold">
                                Commentaire <span class="text-danger d-none" id="commentaire-requis">*</span>
                            </label>
                            <textarea name="commentaire" id="statuer-commentaire" rows="3" maxlength="1000" class="form-control"
                                      placeholder="Obligatoire pour un refus ou une demande de vérification."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-statuer" style="padding:.55rem 1.4rem;font-size:.88rem;">
                            <i class="fas fa-check me-1"></i> Enregistrer la décision
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endcan
</div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modale = document.getElementById('statuerModal');
    const form = document.getElementById('statuer-form');
    const commentaire = document.getElementById('statuer-commentaire');
    const etoile = document.getElementById('commentaire-requis');
    const VALIDE = @json(\App\Domain\Finances\Types\StatutEcartCaisse::VALIDE->value);

    // Le commentaire n'est facultatif que pour une validation pure et simple
    function majCommentaire() {
        const decision = form.querySelector('.decision-radio:checked');
        const requis = decision && decision.value !== VALIDE;
        commentaire.required = requis;
        etoile.classList.toggle('d-none', !requis);
    }

    modale.addEventListener('show.bs.modal', function (event) {
        const bouton = event.relatedTarget;
        form.action = bouton.dataset.url;
        document.getElementById('statuer-libelle').textContent = bouton.dataset.libelle;
        document.getElementById('statuer-caissier').textContent = bouton.dataset.caissier;
        document.getElementById('statuer-ecart').textContent = bouton.dataset.ecart;
        document.getElementById('statuer-motif').textContent = bouton.dataset.motif || 'Aucun motif saisi';
        commentaire.value = '';
        majCommentaire();
    });

    form.querySelectorAll('.decision-radio').forEach(r => r.addEventListener('change', majCommentaire));
});
</script>
@endpush
