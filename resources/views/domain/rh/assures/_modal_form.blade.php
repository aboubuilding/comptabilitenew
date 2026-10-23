<div class="modal-header">
    <h5 class="modal-title">
        <i class="fa fa-shield-halved me-2"></i>{{ $titre }}
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
</div>

<form id="formAssure" action="{{ $action }}" method="POST" autocomplete="off">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="modal-body">
        <x-section>
            <div class="row g-3">
                <div class="col-md-6">
                    <x-libelle requis>Employé</x-libelle>
                    <select name="employe_id" class="form-select" required>
                        <option value="">— Sélectionner —</option>
                        @foreach($employes as $employe)
                            <option value="{{ $employe->id }}"
                                @selected(old('employe_id', $assure->employe_id ?? '') == $employe->id)>
                                {{ $employe->nom_prenom }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <x-libelle requis>Assurance</x-libelle>
                    <select name="assurance_id" class="form-select" required>
                        <option value="">— Sélectionner —</option>
                        @foreach($assurances as $assurance)
                            <option value="{{ $assurance->id }}"
                                @selected(old('assurance_id', $assure->assurance_id ?? '') == $assurance->id)>
                                {{ $assurance->nom_assureur }} — {{ $assurance->police_assurance }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <x-libelle requis>Date de souscription</x-libelle>
                    <input type="date" name="date_souscription" class="form-control"
                           value="{{ old('date_souscription', optional($assure->date_souscription ?? null)->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                           required>
                </div>

                <div class="col-md-4">
                    <x-libelle requis>Prélèvement mensuel (FCFA)</x-libelle>
                    <input type="number" step="0.01" min="0" name="prelevement_mensuel" class="form-control"
                           value="{{ old('prelevement_mensuel', $assure->prelevement_mensuel ?? '') }}"
                           required>
                </div>

                <div class="col-md-4">
                    <x-libelle>Statut</x-libelle>
                    <select name="statut" class="form-select">
                        <option value="actif"    @selected(old('statut', $assure->statut ?? 'actif') === 'actif')>Actif</option>
                        <option value="suspendu" @selected(old('statut', $assure->statut ?? '') === 'suspendu')>Suspendu</option>
                        <option value="termine"  @selected(old('statut', $assure->statut ?? '') === 'termine')>Terminé</option>
                    </select>
                </div>
            </div>
        </x-section>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn-annuler" data-bs-dismiss="modal">Annuler</button>
        <button type="submit" class="btn-enregistrer">
            <i class="fa fa-save"></i> Enregistrer
        </button>
    </div>
</form>