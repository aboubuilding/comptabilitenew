<div class="modal-header">
    <h5 class="modal-title">
        <i class="fa fa-building-shield me-2"></i>{{ $titre }}
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
</div>

<form id="formAsurance" action="{{ $action }}" method="POST" autocomplete="off">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="modal-body">
        <div class="form-section">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nom de l'assureur <span class="text-danger">*</span></label>
                    <input type="text" name="nom_assureur" class="form-control"
                           value="{{ old('nom_assureur', $asurance->nom_assureur ?? '') }}"
                           maxlength="255" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">N° de police <span class="text-danger">*</span></label>
                    <input type="text" name="police_assurance" class="form-control"
                           value="{{ old('police_assurance', $asurance->police_assurance ?? '') }}"
                           maxlength="255" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Montant annuel (FCFA)</label>
                    <input type="number" step="0.01" min="0" name="montant_annuel" class="form-control"
                           value="{{ old('montant_annuel', $asurance->montant_annuel ?? '') }}">
                </div>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn-annuler" data-bs-dismiss="modal">Annuler</button>
        <button type="submit" class="btn-enregistrer">
            <i class="fa fa-save"></i> Enregistrer
        </button>
    </div>
</form>