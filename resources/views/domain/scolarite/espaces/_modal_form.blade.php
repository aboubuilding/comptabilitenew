<div class="modal-header">
    <h5 class="modal-title"><i class="fa-people-roof me-2"></i>{{ $titre }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<form id="formEspace" action="{{ $action }}" method="POST">
    @csrf
    @if($method !== 'POST') @method($method) @endif

    <div class="modal-body">
        <x-section>
            <x-libelle requis>Nom de la famille</x-libelle>
            <input type="text" name="nom_famille" class="form-control" maxlength="150"
                   value="{{ old('nom_famille', $espace->nom_famille ?? '') }}"
                   placeholder="Ex : Famille DIOP" required>
            <small class="text-muted">Nom usuel du foyer (apparaît dans les listes et communications).</small>
        </x-section>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn-annuler" data-bs-dismiss="modal">Annuler</button>
        <button type="submit" class="btn-enregistrer"><i class="fa fa-save"></i> Enregistrer</button>
    </div>
</form>