<div class="modal-header">
    <h5 class="modal-title"><i class="fa fa-user-graduate me-2"></i>{{ $titre }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<form id="formEleve" action="{{ $action }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if($method !== 'POST') @method($method) @endif

    <div class="modal-body">

        {{-- État civil --}}
        <x-section class="mb-3">
            <h6 class="form-section-title mb-3"><i class="fa fa-id-card"></i> État civil</h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <x-libelle requis>Nom</x-libelle>
                    <input type="text" name="nom" class="form-control" maxlength="150"
                           value="{{ old('nom', $eleve->nom ?? '') }}" required>
                </div>
                <div class="col-md-4">
                    <x-libelle requis>Prénom</x-libelle>
                    <input type="text" name="prenom" class="form-control" maxlength="150"
                           value="{{ old('prenom', $eleve->prenom ?? '') }}" required>
                </div>
                <div class="col-md-4">
                    <x-libelle>Prénom usuel</x-libelle>
                    <input type="text" name="prenom_usuel" class="form-control" maxlength="150"
                           value="{{ old('prenom_usuel', $eleve->prenom_usuel ?? '') }}">
                </div>
                <div class="col-md-4">
                    <x-libelle requis>Sexe</x-libelle>
                    <select name="sexe" class="form-select" required>
                        <option value="">— Sélectionner —</option>
                        <option value="1" @selected(old('sexe', $eleve->sexe?->value ?? '') == 1)>Masculin</option>
                        <option value="2" @selected(old('sexe', $eleve->sexe?->value ?? '') == 2)>Féminin</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <x-libelle requis>Date de naissance</x-libelle>
                    <input type="date" name="date_naissance" class="form-control"
                           value="{{ old('date_naissance', optional($eleve->date_naissance ?? null)->format('Y-m-d') ?? '') }}"
                           max="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-md-4">
                    <x-libelle>Lieu de naissance</x-libelle>
                    <input type="text" name="lieu_naissance" class="form-control" maxlength="150"
                           value="{{ old('lieu_naissance', $eleve->lieu_naissance ?? '') }}">
                </div>
                <div class="col-md-4">
                    <x-libelle>Nationalité</x-libelle>
                    <select name="nationalite_id" class="form-select">
                        <option value="">— Sélectionner —</option>
                        @foreach($nationalites as $n)
                            <option value="{{ $n->id }}"
                                @selected(old('nationalite_id', $eleve->nationalite_id ?? '') == $n->id)>
                                {{ $n->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <x-libelle>École de provenance</x-libelle>
                    <input type="text" name="ecole_provenance" class="form-control" maxlength="150"
                           value="{{ old('ecole_provenance', $eleve->ecole_provenance ?? '') }}">
                </div>
                <div class="col-md-2">
                    <x-libelle>Photo</x-libelle>
                    <input type="file" name="photo" class="form-control" accept="image/*">
                </div>
                <div class="col-md-2">
                    <x-libelle>Carte identité</x-libelle>
                    <input type="file" name="carte_identite" class="form-control" accept="image/*,application/pdf">
                </div>
            </div>
        </x-section>

        {{-- Personne à prévenir --}}
        <x-section class="mb-3">
            <h6 class="form-section-title mb-3"><i class="fa fa-phone"></i> Personne à prévenir</h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <x-libelle>Nom</x-libelle>
                    <input type="text" name="personne_prevenir" class="form-control" maxlength="150"
                           value="{{ old('personne_prevenir', $eleve->personne_prevenir ?? '') }}">
                </div>
                <div class="col-md-4">
                    <x-libelle>Téléphone</x-libelle>
                    <input type="text" name="numero_personne_prevenir" class="form-control" maxlength="50"
                           value="{{ old('numero_personne_prevenir', $eleve->numero_personne_prevenir ?? '') }}">
                </div>
                <div class="col-md-4">
                    <x-libelle>Lien de parenté</x-libelle>
                    <select name="lien_parente_personne" class="form-select">
                        <option value="">— Sélectionner —</option>
                        <option value="1" @selected(old('lien_parente_personne', $eleve->lien_parente_personne ?? '') == 1)>Père</option>
                        <option value="2" @selected(old('lien_parente_personne', $eleve->lien_parente_personne ?? '') == 2)>Mère</option>
                        <option value="3" @selected(old('lien_parente_personne', $eleve->lien_parente_personne ?? '') == 3)>Tuteur</option>
                        <option value="4" @selected(old('lien_parente_personne', $eleve->lien_parente_personne ?? '') == 4)>Autre</option>
                    </select>
                </div>
            </div>
        </x-section>

        {{-- Informations médicales --}}
        <x-section class="mb-3">
            <h6 class="form-section-title mb-3"><i class="fa fa-stethoscope"></i> Informations médicales</h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <x-libelle>Nom du médecin</x-libelle>
                    <input type="text" name="nom_medecin" class="form-control" maxlength="150"
                           value="{{ old('nom_medecin', $eleve->nom_medecin ?? '') }}">
                </div>
                <div class="col-md-4">
                    <x-libelle>Téléphone du médecin</x-libelle>
                    <input type="text" name="numero_medecin" class="form-control" maxlength="50"
                           value="{{ old('numero_medecin', $eleve->numero_medecin ?? '') }}">
                </div>
                <div class="col-md-4">
                    <x-libelle>Groupe sanguin (ID)</x-libelle>
                    <input type="number" name="groupe_id" class="form-control"
                           value="{{ old('groupe_id', $eleve->groupe_id ?? '') }}">
                </div>
                <div class="col-md-12">
                    <x-libelle>Allergies</x-libelle>
                    <textarea name="allergie" rows="2" class="form-control">{{ old('allergie', $eleve->allergie ?? '') }}</textarea>
                </div>
                <div class="col-md-2">
                    <x-libelle>Vaccin 1</x-libelle>
                    <input type="text" name="vaccin_1" class="form-control" maxlength="150"
                           value="{{ old('vaccin_1', $eleve->vaccin_1 ?? '') }}">
                </div>
                <div class="col-md-2">
                    <x-libelle>Vaccin 2</x-libelle>
                    <input type="text" name="vaccin_2" class="form-control" maxlength="150"
                           value="{{ old('vaccin_2', $eleve->vaccin_2 ?? '') }}">
                </div>
                <div class="col-md-2">
                    <x-libelle>Vaccin 3</x-libelle>
                    <input type="text" name="vaccin_3" class="form-control" maxlength="150"
                           value="{{ old('vaccin_3', $eleve->vaccin_3 ?? '') }}">
                </div>
                <div class="col-md-2">
                    <x-libelle>Vaccin 4</x-libelle>
                    <input type="text" name="vaccin_4" class="form-control" maxlength="150"
                           value="{{ old('vaccin_4', $eleve->vaccin_4 ?? '') }}">
                </div>
                <div class="col-md-2">
                    <x-libelle>Vaccin 5</x-libelle>
                    <input type="text" name="vaccin_5" class="form-control" maxlength="150"
                           value="{{ old('vaccin_5', $eleve->vaccin_5 ?? '') }}">
                </div>
            </div>
        </x-section>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn-annuler" data-bs-dismiss="modal">Annuler</button>
        <button type="submit" class="btn-enregistrer"><i class="fa fa-save"></i> Enregistrer</button>
    </div>
</form>