<div class="modal-header">
    <h5 class="modal-title"><i class="fa-object-group me-2"></i>Regrouper une fratrie</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<form id="formRegrouper" action="{{ $action }}" method="POST">
    @csrf

    <div class="modal-body">

        <div class="alert alert-info">
            <i class="fa fa-info-circle me-2"></i>
            Sélectionnez des élèves à regrouper dans un même espace familial.
            Vous pouvez créer un <strong>nouvel espace</strong> ou affecter à un <strong>espace existant</strong>.
        </div>

        {{-- Mode --}}
        <div class="form-section mb-3">
            <label class="form-label">Mode de regroupement</label>
            <div class="d-flex gap-3">
                <div class="form-check">
                    <input type="radio" name="mode" value="nouveau" class="form-check-input" id="mode-nouveau"
                           @checked(old('mode', 'nouveau') === 'nouveau')>
                    <label class="form-check-label" for="mode-nouveau">Créer un nouvel espace</label>
                </div>
                <div class="form-check">
                    <input type="radio" name="mode" value="existant" class="form-check-input" id="mode-existant"
                           @checked(old('mode') === 'existant')>
                    <label class="form-check-label" for="mode-existant">Affecter à un espace existant</label>
                </div>
            </div>
        </div>

        {{-- Nom famille (nouveau) --}}
        <div class="form-section mb-3" id="bloc-nouveau">
            <label class="form-label">Nom de la nouvelle famille <span class="text-danger">*</span></label>
            <input type="text" name="nom_famille" class="form-control" maxlength="150"
                   value="{{ old('nom_famille') }}" placeholder="Ex : Famille DIOP">
        </div>

        {{-- Espace existant --}}
        <div class="form-section mb-3 d-none" id="bloc-existant">
            <label class="form-label">Espace familial existant <span class="text-danger">*</span></label>
            <select name="espace_id" class="form-select">
                <option value="">— Sélectionner —</option>
                @foreach($espaces as $e)
                    <option value="{{ $e->id }}" @selected(old('espace_id') == $e->id)>{{ $e->nom_famille }}</option>
                @endforeach
            </select>
        </div>

        {{-- Liste des élèves --}}
        <div class="form-section">
            <label class="form-label">
                Élèves à regrouper
                <span class="badge bg-secondary" id="count-eleves">{{ $eleves->count() }}</span>
            </label>

            @if($eleves->isEmpty())
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle me-2"></i>
                    Aucun élève disponible (tous déjà affectés à un espace).
                </div>
            @else
                <div class="border rounded p-2" style="max-height: 300px; overflow-y: auto;">
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="check-all-eleves">
                        <label class="form-check-label fw-bold" for="check-all-eleves">Tout sélectionner</label>
                    </div>
                    <hr class="my-2">
                    @foreach($eleves as $el)
                        <div class="form-check">
                            <input type="checkbox" name="eleve_ids[]" value="{{ $el->id }}"
                                   class="form-check-input check-eleve" id="el-{{ $el->id }}"
                                   @checked(in_array($el->id, old('eleve_ids', [])))>
                            <label class="form-check-label d-flex justify-content-between w-100" for="el-{{ $el->id }}">
                                <span><strong>{{ $el->nom }}</strong> {{ $el->prenom }}</span>
                                <code class="small">{{ $el->matricule }}</code>
                            </label>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn-annuler" data-bs-dismiss="modal">Annuler</button>
        <button type="submit" class="btn-enregistrer" {{ $eleves->isEmpty() ? 'disabled' : '' }}>
            <i class="fa fa-object-group"></i> Regrouper
        </button>
    </div>
</form>

{{-- Script en ligne : ce fragment est injecté en AJAX, une pile @push n'y serait jamais rendue. --}}
<script>
$(function () {
    // Toggle mode
    function toggleMode() {
        const mode = $('input[name="mode"]:checked').val();
        if (mode === 'nouveau') {
            $('#bloc-nouveau').removeClass('d-none');
            $('#bloc-existant').addClass('d-none');
        } else {
            $('#bloc-nouveau').addClass('d-none');
            $('#bloc-existant').removeClass('d-none');
        }
    }
    $('input[name="mode"]').on('change', toggleMode);
    toggleMode();

    // Check all
    $('#check-all-eleves').on('change', function () {
        $('.check-eleve').prop('checked', this.checked);
        $('#count-eleves').text($('.check-eleve:checked').length);
    });

    $(document).on('change', '.check-eleve', function () {
        $('#count-eleves').text($('.check-eleve:checked').length);
    });
});
</script>
