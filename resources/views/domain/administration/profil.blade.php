@extends('admin.layouts.app')

@section('title', 'Mon profil')
@section('page_title', 'Mon profil')
@section('page_icon', 'fa-user-circle')

@section('breadcrumb')
    <li><a href="{{ route('tableau') }}">Accueil</a></li>
    <li>Mon profil</li>
@endsection

@section('css')
<style>
    .profil-card { border: none; border-radius: 16px; background: #fff; box-shadow: 0 10px 30px rgba(122,15,28,.06); padding: 1.5rem; height: 100%; }
    .profil-card h6 { font-weight: 700; color: var(--school-ink); margin-bottom: 1rem; }
    .profil-card h6 i { color: var(--school-red); margin-right: .4rem; }
    .profil-ligne { display: flex; justify-content: space-between; gap: 1rem; padding: .55rem 0; border-bottom: 1px solid var(--school-paper-line); font-size: .9rem; }
    .profil-ligne:last-child { border-bottom: none; }
    .profil-ligne span:first-child { color: var(--school-muted); }
    .profil-ligne span:last-child { font-weight: 600; color: var(--school-ink); text-align: right; }
    .profil-badge { display: inline-block; background: var(--school-paper); border: 1px solid var(--school-paper-line); color: var(--school-red-dark); border-radius: 999px; font-size: .75rem; font-weight: 600; padding: .25em .8em; margin: 0 0 .25rem .25rem; }
</style>
@endsection

@section('contenu')
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="profil-card">
                <h6><i class="fas fa-id-badge"></i> Mon compte</h6>
                <div class="profil-ligne"><span>Nom</span><span>{{ $utilisateur->full_name }}</span></div>
                <div class="profil-ligne"><span>Identifiant</span><span>{{ $utilisateur->login }}</span></div>
                <div class="profil-ligne"><span>Email</span><span>{{ $utilisateur->email ?: '—' }}</span></div>
                <div class="profil-ligne">
                    <span>{{ count($profils) > 1 ? 'Profils' : 'Profil' }}</span>
                    <span>
                        @foreach ($profils as $profil)
                            <span class="profil-badge">{{ $profil->label() }}</span>
                        @endforeach
                    </span>
                </div>
                <div class="profil-ligne">
                    <span>Dernière connexion</span>
                    <span>{{ $utilisateur->last_login_at ? \Illuminate\Support\Carbon::parse($utilisateur->last_login_at)->format('d/m/Y H:i') : '—' }}</span>
                </div>
            </div>
        </div>

        <div class="col-lg-6" id="password">
            <div class="profil-card">
                <h6><i class="fas fa-key"></i> Changer mon mot de passe</h6>

                <form method="POST" action="{{ route('profil.mot-de-passe') }}" id="form-mot-de-passe" autocomplete="off">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="mot_passe_actuel">Mot de passe actuel <span class="text-danger">*</span></label>
                        <input type="password" name="mot_passe_actuel" id="mot_passe_actuel" required
                               class="form-control @error('mot_passe_actuel') is-invalid @enderror" autocomplete="current-password">
                        @error('mot_passe_actuel') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="mot_passe">Nouveau mot de passe <span class="text-danger">*</span></label>
                        <input type="password" name="mot_passe" id="mot_passe" required minlength="8"
                               class="form-control @error('mot_passe') is-invalid @enderror" autocomplete="new-password">
                        @error('mot_passe') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Au moins 8 caractères, avec des lettres et des chiffres.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="mot_passe_confirmation">Confirmer le nouveau mot de passe <span class="text-danger">*</span></label>
                        <input type="password" name="mot_passe_confirmation" id="mot_passe_confirmation" required
                               class="form-control" autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn-enregistrer">
                        <i class="fas fa-save"></i> Enregistrer le nouveau mot de passe
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
