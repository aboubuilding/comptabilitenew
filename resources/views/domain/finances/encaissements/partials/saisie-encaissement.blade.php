{{-- Détail du paiement choisi et saisie du montant réellement encaissé (écran « Nouvel encaissement » et fenêtre « Encaisser ») --}}
<div class="enc-resume" id="enc-resume"></div>
<div class="enc-saisie saisie-montant mt-3" style="display:none;">
    <div class="row g-2 align-items-end">
        <div class="col-md-6">
            <label class="form-label small fw-semibold" for="enc-montant-recu">Montant réellement encaissé <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="number" id="enc-montant-recu" class="form-control enc-montant-recu" min="1" step="1">
                <span class="input-group-text">F</span>
            </div>
        </div>
        <div class="col-md-6 small">
            <div>À encaisser : <strong class="enc-du" id="enc-du">0</strong> F</div>
            <div class="enc-ecart" id="enc-ecart"></div>
        </div>
    </div>
</div>
