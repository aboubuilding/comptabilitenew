{{-- resources/views/domain/finances/caisses/partials/_cloture-session-modal.blade.php
     Clôture de la session de caisse affichée : solde compté, écart calculé, motif si écart.
     Variables : $caisse ; le solde théorique vient du journal de la page ($journal). --}}
@php
    $soldeTheoriqueCloture = (float) ($journal['soldeTheorique'] ?? $caisse->solde_initial ?? 0);
@endphp
@can('caisses.cloturer')
<x-modale id="clotureSessionModal" data-bs-backdrop="static" aria-hidden="true" contenu-class="modal-caisses" entete-style="background: linear-gradient(135deg, var(--school-red-deep), var(--school-red-dark)); color:#fff; border-radius: 16px 16px 0 0;" corps-class="p-4" croix-blanche>
    <x-slot:formulaire method="POST" action="{{ route('finances.caisses.cloturer', $caisse) }}" id="cloture-session-form">@csrf</x-slot:formulaire>
    <x-slot:titre><i class="fas fa-lock me-2"></i>Clôturer la caisse</x-slot:titre>
    <div class="alert alert-warning py-2 small mb-3">
        <i class="fas fa-exclamation-triangle me-1"></i>
        La clôture est <strong>définitive</strong> : le journal sera figé et aucune écriture ne
        pourra plus y être ajoutée.
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 p-3"
         style="background: var(--eim-cream); border-radius: 10px;">
        <div>
            <div class="text-muted text-xs">Caisse</div>
            <div class="fw-semibold">{{ $caisse->libelle }}</div>
        </div>
        <div class="text-end">
            <div class="text-muted text-xs">Solde théorique</div>
            <div class="fw-bold fs-5">{{ number_format($soldeTheoriqueCloture, 2, ',', ' ') }}</div>
        </div>
    </div>

    <div class="mb-3">
        <x-libelle requis>Solde compté (espèces physiquement en caisse)</x-libelle>
        <input type="number" name="solde_compte" step="0.01" min="0" required
               class="form-control @error('solde_compte') is-invalid @enderror"
               id="cloture-session-solde" value="{{ old('solde_compte') }}">
        @error('solde_compte') <div class="text-danger text-xs mt-1">{{ $message }}</div> @enderror
    </div>

    <div id="cloture-session-ecart" class="d-none">
        <div class="d-flex justify-content-between align-items-center p-2 rounded mb-2"
             style="background:#fdf2f2; border-left:3px solid var(--school-red);">
            <span class="fw-semibold" style="color: var(--school-red);">Écart constaté</span>
            <span class="fw-bold" style="color: var(--school-red);" id="cloture-session-ecart-valeur">0,00</span>
        </div>
        <x-libelle requis>Motif de l'écart</x-libelle>
        <textarea name="motif_ecart" rows="2" maxlength="1000"
                  class="form-control @error('motif_ecart') is-invalid @enderror"
                  id="cloture-session-motif">{{ old('motif_ecart') }}</textarea>
        @error('motif_ecart') <div class="text-danger text-xs mt-1">{{ $message }}</div> @enderror
    </div>
    <x-slot:pied class="bg-light px-4 py-3">
        <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
        <button type="submit" class="btn"
                style="background: var(--school-red-dark); color: #fff; border-radius: 8px; font-weight: 600; padding: .55rem 1.4rem; font-size: .88rem;">
            <i class="fas fa-check me-1"></i> Clôturer définitivement
        </button>
    </x-slot:pied>
</x-modale>
@endcan

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const soldeTheorique = {{ json_encode($soldeTheoriqueCloture) }};
    const champSolde = document.getElementById('cloture-session-solde');
    const blocEcart  = document.getElementById('cloture-session-ecart');
    const valeur     = document.getElementById('cloture-session-ecart-valeur');
    const motif      = document.getElementById('cloture-session-motif');

    // Un écart entre le compté et le théorique rend le motif obligatoire (règle du service).
    function majEcart() {
        const compte = parseFloat(champSolde.value);
        const ecart  = isNaN(compte) ? 0 : Math.round((compte - soldeTheorique) * 100) / 100;
        const aEcart = !isNaN(compte) && Math.abs(ecart) > 0.01;

        blocEcart.classList.toggle('d-none', !aEcart);
        motif.required = aEcart;
        valeur.textContent = ecart.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    champSolde.addEventListener('input', majEcart);
    majEcart();

    @if ($errors->hasAny(['solde_compte', 'motif_ecart']))
        bootstrap.Modal.getOrCreateInstance(document.getElementById('clotureSessionModal')).show();
    @endif
});
</script>
@endpush
