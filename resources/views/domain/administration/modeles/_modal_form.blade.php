<div class="modal-header">
    <h5 class="modal-title"><i class="fa fa-file-lines me-2"></i>{{ $titre }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<form id="formModele" action="{{ $action }}" method="POST">
    @csrf
    @if($method !== 'POST') @method($method) @endif

    <div class="modal-body">
        <div class="row g-3">
            <div class="col-md-4">
                <x-libelle requis>Code</x-libelle>
                <input type="text" name="code" class="form-control" maxlength="50"
                       value="{{ old('code', $modele->code ?? '') }}" required>
            </div>
            <div class="col-md-8">
                <x-libelle requis>Libellé</x-libelle>
                <input type="text" name="libelle" class="form-control" maxlength="150"
                       value="{{ old('libelle', $modele->libelle ?? '') }}" required>
            </div>
            <div class="col-md-6">
                <x-libelle>Type</x-libelle>
                <input type="text" name="type" class="form-control" maxlength="50"
                       value="{{ old('type', $modele->type ?? '') }}"
                       placeholder="relance, information, convocation...">
            </div>
            <div class="col-md-12">
                <x-libelle requis>Sujet</x-libelle>
                <input type="text" name="sujet" class="form-control" maxlength="255"
                       value="{{ old('sujet', $modele->sujet ?? '') }}" required>
            </div>
            <div class="col-md-12">
                <x-libelle requis>Corps</x-libelle>
                <textarea name="corps" rows="8" class="form-control" required>{{ old('corps', $modele->corps ?? '') }}</textarea>
                <small class="text-muted">
                    Variables : {{ implode(', ', array_keys(\App\Domain\Administration\Models\ModeleMessage::variablesParDefaut())) }}
                </small>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn-annuler" data-bs-dismiss="modal">Annuler</button>
        <button type="submit" class="btn-enregistrer"><i class="fa fa-save"></i> Enregistrer</button>
    </div>
</form>