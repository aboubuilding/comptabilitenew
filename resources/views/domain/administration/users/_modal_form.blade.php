<div class="modal-header">
    <h5 class="modal-title"><i class="fa fa-user-gear me-2"></i>{{ $titre }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<form id="formUser" action="{{ $action }}" method="POST" enctype="multipart/form-data" autocomplete="off">
    @csrf
    @if($method !== 'POST') @method($method) @endif

    <div class="modal-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nom <span class="text-danger">*</span></label>
                <input type="text" name="nom" class="form-control"
                       value="{{ old('nom', $user->nom ?? '') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Prénom <span class="text-danger">*</span></label>
                <input type="text" name="prenom" class="form-control"
                       value="{{ old('prenom', $user->prenom ?? '') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Login <span class="text-danger">*</span></label>
                <input type="text" name="login" class="form-control"
                       value="{{ old('login', $user->login ?? '') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control"
                       value="{{ old('email', $user->email ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">
                    Mot de passe
                    @if($user) <small class="text-muted">(laisser vide pour ne pas modifier)</small>
                    @else <span class="text-danger">*</span> @endif
                </label>
                <input type="password" name="mot_passe" class="form-control"
                       {{ $user ? '' : 'required' }} autocomplete="new-password">
            </div>
            <div class="col-md-6">
                <label class="form-label">Profil <span class="text-danger">*</span></label>
                <select name="role_id" class="form-select" required>
                    <option value="">— Sélectionner —</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}"
                            @selected(old('role_id', $user->role_id ?? '') == $role->id)>
                            {{ $role->libelle }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Profils complémentaires</label>
                <div class="text-muted small mb-2">
                    Un utilisateur peut cumuler plusieurs profils (par exemple Comptable et Caissier) : il a alors les droits de chacun.
                </div>
                @php
                    $complementaires = old('profils_complementaires', $user ? $user->profilsComplementaires->pluck('id')->all() : []);
                @endphp
                <div class="row g-1">
                    @foreach($roles as $role)
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="profils_complementaires[]"
                                       value="{{ $role->id }}" id="profil-comp-{{ $role->id }}"
                                       @checked(in_array($role->id, array_map('intval', $complementaires), true))>
                                <label class="form-check-label small" for="profil-comp-{{ $role->id }}">{{ $role->libelle }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Photo</label>
                <input type="file" name="photo" class="form-control" accept="image/*">
                @if($user?->photo)
                    <small class="text-muted">Photo actuelle : {{ basename($user->photo) }}</small>
                @endif
            </div>
            <div class="col-md-6">
                <label class="form-label">Statut</label>
                <select name="etat" class="form-select">
                    <option value="1" @selected(old('etat', $user->etat ?? 1) == 1)>Actif</option>
                    <option value="0" @selected(old('etat', $user->etat ?? 1) == 0)>Inactif</option>
                </select>
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