<div class="modal-header">
    <h5 class="modal-title"><i class="fa fa-envelope me-2"></i>{{ $titre }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<form id="formMessage" action="{{ $action }}" method="POST">
    @csrf
    @if($method !== 'POST') @method($method) @endif

    <div class="modal-body">
        <div class="row g-3">
            {{-- Bloc message --}}
            <div class="col-md-12">
                <label class="form-label">Modèle (optionnel)</label>
                <select name="modele_id" class="form-select" id="select-modele">
                    <option value="">— Aucun —</option>
                    @foreach($modeles as $modele)
                        <option value="{{ $modele->id }}"
                                data-sujet="{{ $modele->sujet }}"
                                data-corps="{{ $modele->corps }}"
                                @selected(old('modele_id', $message->modele_id ?? '') == $modele->id)>
                            {{ $modele->libelle }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-12">
                <label class="form-label">Sujet <span class="text-danger">*</span></label>
                <input type="text" name="sujet" class="form-control"
                       value="{{ old('sujet', $message->sujet ?? '') }}" required>
            </div>

            <div class="col-md-12">
                <label class="form-label">Corps <span class="text-danger">*</span></label>
                <textarea name="corps" rows="8" class="form-control" required>{{ old('corps', $message->corps ?? '') }}</textarea>
                <small class="text-muted">
                    Variables : {{ implode(', ', array_keys(\App\Domain\Administration\Models\ModeleMessage::variablesParDefaut())) }}
                </small>
            </div>

            {{-- Bloc ciblage (uniquement à la création, pas en édition d'un brouillon déjà ciblé) --}}
            @if(!$message)
                <div class="col-md-12"><hr><h6 class="mb-2"><i class="fa fa-users"></i> Ciblage</h6></div>

                <div class="col-md-4">
                    <label class="form-label">Type de ciblage</label>
                    <select name="ciblage_type" class="form-select" id="ciblage_type" required>
                        <option value="libre">Adresses libres</option>
                        <option value="classe">Par classe</option>
                        <option value="niveau">Par niveau</option>
                        <option value="tiers">Contacts tiers</option>
                    </select>
                </div>

                <div class="col-md-8 ciblage-bloc" data-type="libre">
                    <label class="form-label">Adresses email (une par ligne)</label>
                    <textarea name="emails_libres_text" class="form-control" rows="3"
                              placeholder="parent1@example.com&#10;parent2@example.com"></textarea>
                </div>

                <div class="col-md-8 ciblage-bloc d-none" data-type="classe">
                    <label class="form-label">Classe</label>
                    <select name="classe_id" class="form-select">
                        <option value="">— Sélectionner —</option>
                        @foreach (\App\Domain\Scolarite\Models\Classe::actif()->where('annee_id', app(\App\Support\AnneeScolaireContext::class)->id())->orderBy('libelle')->get(['id', 'libelle']) as $classe)
                            <option value="{{ $classe->id }}">{{ $classe->libelle }}</option>
                        @endforeach
                    </select>
                    <div class="form-text small">Les parents des élèves inscrits dans cette classe recevront le message.</div>
                </div>

                <div class="col-md-8 ciblage-bloc d-none" data-type="niveau">
                    <label class="form-label">Niveau</label>
                    <select name="niveau_id" class="form-select">
                        <option value="">— Sélectionner —</option>
                        @foreach (\App\Domain\Scolarite\Models\Niveau::actif()->orderBy('numero_ordre')->get(['id', 'libelle']) as $niveau)
                            <option value="{{ $niveau->id }}">{{ $niveau->libelle }}</option>
                        @endforeach
                    </select>
                    <div class="form-text small">Les parents des élèves inscrits à ce niveau recevront le message.</div>
                </div>

                <div class="col-md-8 ciblage-bloc d-none" data-type="tiers">
                    <label class="form-label">Contacts tiers</label>
                    <select name="tiers_ids[]" class="form-select" multiple>
                        @foreach($tiers as $t)
                            <option value="{{ $t->id }}">{{ $t->nom }} — {{ $t->email }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn-annuler" data-bs-dismiss="modal">Annuler</button>
        @if(!$message)
            <button type="submit" name="envoyer" value="1" class="btn-nouveau">
                <i class="fa fa-paper-plane"></i> Enregistrer et envoyer
            </button>
        @endif
        <button type="submit" class="btn-enregistrer">
            <i class="fa fa-save"></i> Enregistrer en brouillon
        </button>
    </div>
</form>

{{-- Script en ligne : ce fragment est injecté en AJAX, une pile @push n'y serait jamais rendue. --}}
<script>
$(function () {
    // Affiche le bon bloc de ciblage
    $('#ciblage_type').on('change', function () {
        const type = $(this).val();
        $('.ciblage-bloc').addClass('d-none').filter('[data-type="' + type + '"]').removeClass('d-none');
    }).trigger('change');

    // Pré-remplit sujet + corps depuis le modèle choisi
    $('#select-modele').on('change', function () {
        const opt = $(this).find(':selected');
        const sujet = opt.data('sujet');
        const corps = opt.data('corps');
        if (sujet) $('input[name="sujet"]').val(sujet);
        if (corps) $('textarea[name="corps"]').val(corps);
    });
});
</script>
