{{-- resources/views/domain/finances/caisses/partials/_mouvement-modal.blade.php
     Saisie d'un mouvement de caisse hors encaissement (entrée / sortie manuelle).
     Variables : $caisse, $typesManuels (MouvementType[]) --}}
@can('caisses.mouvements')
<div class="modal fade" id="mouvementModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-caisses">
            <form method="POST" action="{{ route('finances.caisses.mouvements.store', $caisse) }}">
                @csrf
                <div class="modal-header"
                     style="background: linear-gradient(135deg, var(--school-red-deep), var(--school-red-dark)); color:#fff; border-radius: 16px 16px 0 0;">
                    <h5 class="modal-title">
                        <i class="fas fa-exchange-alt me-2"></i>Nouveau mouvement de caisse
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="alert alert-light border py-2 small mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Les encaissements et les règlements de dépenses s'inscrivent d'eux-mêmes au journal :
                        ne saisissez ici que les entrées et sorties qui n'en relèvent pas.
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
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
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date_mouvement" class="form-control @error('date_mouvement') is-invalid @enderror"
                                   value="{{ old('date_mouvement', now()->toDateString()) }}" required>
                            @error('date_mouvement') <div class="text-danger text-xs mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Libellé <span class="text-danger">*</span></label>
                            <input type="text" name="libelle" maxlength="150" class="form-control @error('libelle') is-invalid @enderror"
                                   value="{{ old('libelle') }}" required>
                            @error('libelle') <div class="text-danger text-xs mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Montant <span class="text-danger">*</span></label>
                            <input type="number" name="montant" step="0.01" min="0.01" class="form-control @error('montant') is-invalid @enderror"
                                   value="{{ old('montant') }}" required>
                            @error('montant') <div class="text-danger text-xs mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bénéficiaire / déposant</label>
                            <input type="text" name="beneficiaire" maxlength="150" class="form-control @error('beneficiaire') is-invalid @enderror"
                                   value="{{ old('beneficiaire') }}">
                            @error('beneficiaire') <div class="text-danger text-xs mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Motif</label>
                            <textarea name="motif" rows="2" maxlength="1000" class="form-control @error('motif') is-invalid @enderror">{{ old('motif') }}</textarea>
                            @error('motif') <div class="text-danger text-xs mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light px-4 py-3">
                    <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn"
                            style="background: var(--school-red-dark); color: #fff; border-radius: 8px; font-weight: 600; padding: .55rem 1.4rem; font-size: .88rem;">
                        <i class="fas fa-check me-1"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
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
