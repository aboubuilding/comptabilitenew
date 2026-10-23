<div class="modal-header">
    <h5 class="modal-title"><i class="fa-key me-2"></i>{{ $titre }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<form id="formCompte" action="{{ $action }}" method="POST">
    @csrf
    @if($method !== 'POST') @method($method) @endif

    <div class="modal-body">

        @if(!$compte)
            <div class="alert alert-info">
                <i class="fa fa-info-circle me-2"></i>
                Un mot de passe sera <strong>généré automatiquement</strong> et envoyé par email au parent.
            </div>
        @endif

        <div class="row g-3">
            <div class="col-md-12">
                <x-libelle requis>Email du compte</x-libelle>
                <input type="email" name="email" class="form-control" maxlength="150"
                       value="{{ old('email', $compte->email ?? '') }}" required>
            </div>
            <div class="col-md-12">
                <x-libelle requis>Parent titulaire</x-libelle>
                <select name="parent_id" class="form-select" required>
                    <option value="">— Sélectionner —</option>
                    @foreach($parents as $p)
                        <option value="{{ $p->id }}"
                            @selected(old('parent_id', $compte->parent_id ?? '') == $p->id)>
                            {{ $p->nom_complet }}
                            @if($p->is_principal) — Principal @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-12">
                <x-libelle>Statut</x-libelle>
                <select name="statut_compte" class="form-select">
                    @php
                        $statutCompte = (int) old('statut_compte', $compte->statut_compte?->value ?? \App\Domain\Scolarite\Types\StatutCompte::ACTIF->value);
                    @endphp
                    @foreach ([\App\Domain\Scolarite\Types\StatutCompte::ACTIF, \App\Domain\Scolarite\Types\StatutCompte::INACTIF, \App\Domain\Scolarite\Types\StatutCompte::EN_ATTENTE] as $statut)
                        <option value="{{ $statut->value }}" @selected($statutCompte === $statut->value)>{{ $statut->libelle() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn-annuler" data-bs-dismiss="modal">Annuler</button>
        <button type="submit" class="btn-enregistrer"><i class="fa fa-save"></i> Enregistrer</button>
    </div>
</form>