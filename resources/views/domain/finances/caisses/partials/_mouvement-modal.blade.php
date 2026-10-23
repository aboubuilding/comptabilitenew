{{-- resources/views/domain/finances/caisses/partials/_mouvement-modal.blade.php
     Saisie d'un mouvement de caisse hors encaissement (entrée / sortie manuelle).
     Variables : $caisse, $typesManuels (MouvementType[]) --}}
@can('caisses.mouvements')
<x-modale id="mouvementModal" data-bs-backdrop="static" aria-hidden="true" contenu-class="modal-caisses" entete-style="background: linear-gradient(135deg, var(--school-red-deep), var(--school-red-dark)); color:#fff; border-radius: 16px 16px 0 0;" corps-class="p-4" croix-blanche>
    <x-slot:formulaire method="POST" action="{{ route('finances.caisses.mouvements.store', $caisse) }}">@csrf</x-slot:formulaire>
    <x-slot:titre><i class="fas fa-exchange-alt me-2"></i>Nouveau mouvement de caisse</x-slot:titre>
    <div class="alert alert-light border py-2 small mb-3">
        <i class="fas fa-info-circle me-1"></i>
        Les encaissements et les règlements de dépenses s'inscrivent d'eux-mêmes au journal :
        ne saisissez ici que les entrées et sorties qui n'en relèvent pas.
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <x-libelle requis>Type</x-libelle>
            <select name="type_mouvement" class="form-select @error('type_mouvement') is-invalid @enderror" required>
                @foreach ($typesManuels as $type)
                    <option value="{{ $type->value }}" @selected((int) old('type_mouvement') === $type->value)>
                        {{ $type->label() }}
                    </option>
                @endforeach
            </select>
            @error('type_mouvement') <div class="text-danger text-xs mt-1">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <x-libelle requis>Date</x-libelle>
            <input type="date" name="date_mouvement" class="form-control @error('date_mouvement') is-invalid @enderror"
                   value="{{ old('date_mouvement', now()->toDateString()) }}" required>
            @error('date_mouvement') <div class="text-danger text-xs mt-1">{{ $message }}</div> @enderror
        </div>
        <div class="col-12">
            <x-libelle requis>Libellé</x-libelle>
            <input type="text" name="libelle" maxlength="150" class="form-control @error('libelle') is-invalid @enderror"
                   value="{{ old('libelle') }}" required>
            @error('libelle') <div class="text-danger text-xs mt-1">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <x-libelle requis>Montant</x-libelle>
            <input type="number" name="montant" step="0.01" min="0.01" class="form-control @error('montant') is-invalid @enderror"
                   value="{{ old('montant') }}" required>
            @error('montant') <div class="text-danger text-xs mt-1">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <x-libelle>Bénéficiaire / déposant</x-libelle>
            <input type="text" name="beneficiaire" maxlength="150" class="form-control @error('beneficiaire') is-invalid @enderror"
                   value="{{ old('beneficiaire') }}">
            @error('beneficiaire') <div class="text-danger text-xs mt-1">{{ $message }}</div> @enderror
        </div>
        <div class="col-12">
            <x-libelle>Motif</x-libelle>
            <textarea name="motif" rows="2" maxlength="1000" class="form-control @error('motif') is-invalid @enderror">{{ old('motif') }}</textarea>
            @error('motif') <div class="text-danger text-xs mt-1">{{ $message }}</div> @enderror
        </div>
    </div>
    <x-slot:pied class="bg-light px-4 py-3">
        <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
        <button type="submit" class="btn"
                style="background: var(--school-red-dark); color: #fff; border-radius: 8px; font-weight: 600; padding: .55rem 1.4rem; font-size: .88rem;">
            <i class="fas fa-check me-1"></i> Enregistrer
        </button>
    </x-slot:pied>
</x-modale>
@endcan

@if ($errors->hasAny(['type_mouvement', 'date_mouvement', 'libelle', 'montant', 'beneficiaire', 'motif']))
    @push('js')
    <script>
        // Le formulaire revient avec des erreurs : on rouvre la modale sur la saisie.
        document.addEventListener('DOMContentLoaded', function () {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('mouvementModal')).show();
        });
    </script>
    @endpush
@endif
