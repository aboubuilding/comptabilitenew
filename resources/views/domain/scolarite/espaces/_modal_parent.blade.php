<div class="modal-header">
    <h5 class="modal-title"><i class="fa-user-tie me-2"></i>{{ $titre }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<form id="formParent" action="{{ $action }}" method="POST">
    @csrf
    @if($method !== 'POST') @method($method) @endif

    <div class="modal-body">
        <div class="row g-3">
            <div class="col-md-6">
                <x-libelle requis>Nom</x-libelle>
                <input type="text" name="nom_parent" class="form-control" maxlength="150"
                       value="{{ old('nom_parent', $parent->nom_parent ?? '') }}" required>
            </div>
            <div class="col-md-6">
                <x-libelle requis>Prénom</x-libelle>
                <input type="text" name="prenom_parent" class="form-control" maxlength="150"
                       value="{{ old('prenom_parent', $parent->prenom_parent ?? '') }}" required>
            </div>
            <div class="col-md-4">
                <x-libelle>Rôle</x-libelle>
                <select name="role" class="form-select">
                    <option value="">— Sélectionner —</option>
                    <option value="1" @selected(old('role', $parent->role ?? '') == 1)>Père</option>
                    <option value="2" @selected(old('role', $parent->role ?? '') == 2)>Mère</option>
                    <option value="3" @selected(old('role', $parent->role ?? '') == 3)>Tuteur</option>
                    <option value="4" @selected(old('role', $parent->role ?? '') == 4)>Autre</option>
                </select>
            </div>
            <div class="col-md-4">
                <x-libelle>Téléphone</x-libelle>
                <input type="text" name="telephone" class="form-control" maxlength="50"
                       value="{{ old('telephone', $parent->telephone ?? '') }}">
            </div>
            <div class="col-md-4">
                <x-libelle>WhatsApp</x-libelle>
                <input type="text" name="whatsapp" class="form-control" maxlength="50"
                       value="{{ old('whatsapp', $parent->whatsapp ?? '') }}">
            </div>
            <div class="col-md-6">
                <x-libelle>Email</x-libelle>
                <input type="email" name="email" class="form-control" maxlength="150"
                       value="{{ old('email', $parent->email ?? '') }}">
            </div>
            <div class="col-md-6">
                <x-libelle>Profession</x-libelle>
                <input type="text" name="profession" class="form-control" maxlength="150"
                       value="{{ old('profession', $parent->profession ?? '') }}">
            </div>
            <div class="col-md-6">
                <x-libelle>Nationalité</x-libelle>
                <select name="nationalite_id" class="form-select">
                    <option value="">— Sélectionner —</option>
                    @foreach($nationalites as $n)
                        <option value="{{ $n->id }}"
                            @selected(old('nationalite_id', $parent->nationalite_id ?? '') == $n->id)>
                            {{ $n->libelle }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <x-libelle>Quartier</x-libelle>
                <input type="text" name="quartier" class="form-control" maxlength="150"
                       value="{{ old('quartier', $parent->quartier ?? '') }}">
            </div>
            <div class="col-md-12">
                <x-libelle>Adresse complète</x-libelle>
                <textarea name="adresse" rows="2" class="form-control">{{ old('adresse', $parent->adresse ?? '') }}</textarea>
            </div>
            <div class="col-md-12">
                <div class="form-check">
                    <input type="checkbox" name="is_principal" value="1" class="form-check-input"
                           id="is_principal" @checked(old('is_principal', $parent->is_principal ?? 0))>
                    <label class="form-check-label" for="is_principal">
                        <strong>Désigner comme parent principal</strong>
                        <small class="text-muted d-block">
                            Le parent principal recevra les communications et sera le titulaire du compte portail.
                        </small>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn-annuler" data-bs-dismiss="modal">Annuler</button>
        <button type="submit" class="btn-enregistrer"><i class="fa fa-save"></i> Enregistrer</button>
    </div>
</form>