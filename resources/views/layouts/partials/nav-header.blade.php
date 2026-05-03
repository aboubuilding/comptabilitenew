@php
    $session = session()->get('LoginUser');
    $compteId = $session['compte_id'] ?? null;
    $user = $compteId ? \App\Models\User::rechercheUserById($compteId) : null;
    $role = $user?->role ?? null;
    $annees = \App\Models\Annee::where('etat', 1)->orderByDesc('date_rentree')->get();
    $currentAnnee = $session['annee_id'] ?? null;
    $currentAnneeLibelle = $session['annee_libelle'] ?? '2024-2025';
@endphp

<header class="eim-topbar">
    {{-- Gauche : Logo + Nom école --}}
    <div class="eim-topbar-left">
        <a href="{{ url('/') }}" class="eim-brand">
            <div class="eim-brand-logo-wrapper">
                <img src="{{ asset('admin/images/logo_mariam.png') }}" alt="École Internationale Mariam" class="eim-brand-logo">
            </div>
            <div class="eim-brand-info">
                <h1 class="eim-brand-name">
                    École Internationale <span class="eim-brand-highlight">Mariam</span>
                </h1>
                <span class="eim-brand-tagline">Excellence & Formation</span>
            </div>
        </a>
    </div>

    {{-- Centre/Droite : Outils --}}
    <div class="eim-topbar-right">
        {{-- Sélecteur d'année --}}
        <div class="eim-year-selector">
            <i class="fas fa-graduation-cap"></i>
            <select id="change_annee" class="eim-year-select">
                @foreach($annees as $annee)
                    <option value="{{ $annee->id }}" >
                        {{ $annee->libelle }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Plein écran --}}
        <button class="eim-icon-btn dz-fullscreen" title="Plein écran" type="button">
            <i class="fas fa-expand" id="icon-full"></i>
            <i class="fas fa-compress" id="icon-minimize" style="display:none"></i>
        </button>

        {{-- 📬 COURRIERS (NOUVEAU) --}}
        <div class="eim-dropdown-wrapper">
            <button class="eim-icon-btn eim-mail-btn" data-bs-toggle="dropdown" aria-label="Gestion des courriers">
                <i class="fas fa-envelope"></i>
                <span class="eim-badge" id="mail-count">0</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end eim-dropdown-menu">
                <div class="eim-dropdown-header">
                    <h6 class="mb-0"><i class="fas fa-envelope me-2 text-primary"></i>Courriers</h6>
                </div>
                <div class="eim-dropdown-body p-0">
                    <a href="{{ url('/courriers/inbox') }}" class="dropdown-item d-flex align-items-center justify-content-between py-2 px-3">
                        <span><i class="fas fa-inbox me-2 text-primary"></i> Boîte de réception</span>
                        <span class="badge bg-primary rounded-pill" id="unread-badge">0</span>
                    </a>
                    <a href="{{ url('/courriers/outbox') }}" class="dropdown-item d-flex align-items-center justify-content-between py-2 px-3">
                        <span><i class="fas fa-paper-plane me-2 text-success"></i> Envoyés</span>
                    </a>
                    <a href="{{ url('/courriers/draft') }}" class="dropdown-item d-flex align-items-center justify-content-between py-2 px-3">
                        <span><i class="fas fa-pen-fancy me-2 text-warning"></i> Brouillons</span>
                    </a>
                    <a href="{{ url('/courriers/archive') }}" class="dropdown-item d-flex align-items-center justify-content-between py-2 px-3">
                        <span><i class="fas fa-archive me-2 text-secondary"></i> Archives</span>
                    </a>
                </div>
                <div class="eim-dropdown-footer text-center p-2 border-top bg-light">
                    <a href="{{ url('/courriers') }}" class="btn btn-sm btn-outline-primary w-100">
                        <i class="fas fa-list me-1"></i> Voir tous les courriers
                    </a>
                </div>
            </div>
        </div>

        {{-- Notifications --}}
        <div class="eim-dropdown-wrapper">
            <button class="eim-icon-btn eim-notif-btn" data-bs-toggle="dropdown" aria-label="Notifications">
                <i class="fas fa-bell"></i>
                <span class="eim-badge" id="notif-count">0</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end eim-dropdown-menu">
                <div class="eim-dropdown-header">
                    <h6 class="mb-0"><i class="fas fa-bell me-2 text-warning"></i>Notifications</h6>
                </div>
                <div class="eim-dropdown-body">
                    <p class="text-muted text-center mb-0 py-3">Aucune nouvelle notification</p>
                </div>
            </div>
        </div>

        {{-- Profil utilisateur --}}
        <div class="eim-dropdown-wrapper eim-profile-wrapper">
            <button class="eim-profile-btn" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="eim-profile-avatar">
                    <img src="{{ $user?->photo ? asset('storage/'.$user->photo) : asset('admin/images/user.jpg') }}" alt="{{ $user?->nom }}">
                </div>
                <div class="eim-profile-details d-none d-lg-block">
                    <span class="eim-profile-name">{{ $user?->nom }} {{ $user?->prenom }}</span>
                    <span class="eim-profile-role">
                        @if($role == \App\Types\Role::ADMIN) Administrateur
                        @elseif($role == \App\Types\Role::COMPTABLE) Comptable
                        @elseif($role == \App\Types\Role::DIRECTEUR) Directeur
                        @elseif($role == \App\Types\Role::CAISSIER) Caissier
                        @else Utilisateur @endif
                    </span>
                </div>
                <i class="fas fa-chevron-down eim-profile-chevron"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end eim-profile-menu">
                <div class="eim-profile-menu-header">
                    <img src="{{ $user?->photo ? asset('storage/'.$user->photo) : asset('admin/images/user.jpg') }}" alt="{{ $user?->nom }}">
                    <div>
                        <div class="eim-pm-name">{{ $user?->nom }} {{ $user?->prenom }}</div>
                        <div class="eim-pm-role">
                            @if($role == \App\Types\Role::ADMIN) Administrateur
                            @elseif($role == \App\Types\Role::COMPTABLE) Comptable
                            @elseif($role == \App\Types\Role::DIRECTEUR) Directeur
                            @elseif($role == \App\Types\Role::CAISSIER) Caissier
                            @else Utilisateur @endif
                        </div>
                    </div>
                </div>
                <div class="eim-profile-menu-body">
                    <a href="{{ url('/utilisateurs/profil') }}" class="eim-pm-item">
                        <i class="fas fa-user"></i> Mon profil
                    </a>
                    <a href="{{ url('/utilisateurs/change-password') }}" class="eim-pm-item">
                        <i class="fas fa-lock"></i> Changer le mot de passe
                    </a>
                    <a href="{{ url('/parametres') }}" class="eim-pm-item">
                        <i class="fas fa-cog"></i> Paramètres
                    </a>
                </div>
                <div class="eim-profile-menu-footer">
                    <a href="{{ url('/logout') }}" class="eim-pm-logout">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                </div>
            </div>
        </div>

        {{-- Hamburger mobile --}}
        <button class="eim-hamburger" id="eim-hamburger" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>