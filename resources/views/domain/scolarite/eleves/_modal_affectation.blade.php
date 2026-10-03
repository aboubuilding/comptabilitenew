<div class="modal-header">
    <h5 class="modal-title">
        <i class="fa fa-chalkboard me-2"></i>
        Affecter une classe
        @if($eleves->count() > 1)
            <span class="badge bg-primary ms-2">{{ $eleves->count() }} élèves</span>
        @endif
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<form id="formAffectation" action="{{ $action }}" method="POST">
    @csrf

    <div class="modal-body">

        @if($niveau)
            <div class="alert alert-info d-flex align-items-center">
                <i class="fa fa-info-circle me-2"></i>
                <span>
                    Niveau concerné : <strong>{{ $niveau->libelle }}</strong>.
                    Seules les classes de ce niveau sont proposées.
                </span>
            </div>
        @else
            <div class="alert alert-warning d-flex align-items-center">
                <i class="fa fa-exclamation-triangle me-2"></i>
                <span>Les élèves sélectionnés n'ont pas tous le même niveau. Impossible d'affecter une classe groupée.</span>
            </div>
        @endif

        <div class="mb-3">
            <label class="form-label small text-muted">Élèves concernés</label>
            <div class="border rounded p-2" style="max-height: 120px; overflow-y: auto;">
                @foreach($eleves as $e)
                    <div class="d-flex justify-content-between border-bottom py-1">
                        <span><strong>{{ $e->nom }}</strong> {{ $e->prenom }}</span>
                        <code class="small">{{ $e->matricule }}</code>
                    </div>
                @endforeach
            </div>
            @foreach($eleves as $e)
                <input type="hidden" name="eleve_ids[]" value="{{ $e->id }}">
            @endforeach
        </div>

        <div class="form-section">
            <label class="form-label">Classe à affecter</label>
            <select name="classe_id" class="form-select" id="select-classe">
                <option value="">— Aucune classe (retirer l'affectation) —</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}">{{ $c->libelle }}</option>
                @endforeach
            </select>
            <small class="text-muted">
                Choisir « Aucune classe » retirera l'affectation actuelle des élèves sélectionnés.
            </small>
        </div>

    </div>

    <div class="modal-footer">
        <button type="button" class="btn-annuler" data-bs-dismiss="modal">Annuler</button>
        <button type="submit" class="btn-enregistrer" {{ !$niveau && $eleves->count() > 1 ? 'disabled' : '' }}>
            <i class="fa fa-check"></i> Valider
        </button>
    </div>
</form>