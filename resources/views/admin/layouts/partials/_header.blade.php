{{-- resources/views/layouts/partials/_header-mariam.blade.php --}}

<style>
    @import url('https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap');

    :root {
        /* Palette inspirée du logo EIM : rouge écusson + or */
        --mariam-top-bg-a    : #7a0f1c;
        --mariam-top-bg-b    : #9c1524;
        --mariam-top-border  : rgba(255,255,255,0.08);
        --mariam-nav-bg-a    : #b31c2b;
        --mariam-nav-bg-b    : #931521;
        --mariam-accent      : #e8b23a;
        --mariam-accent-lt   : #f2c766;
        --mariam-accent-dark : #b8860b;
        --mariam-urgent      : #ff7a1a;
        --mariam-urgent-dark : #cc5500;
        --mariam-drop-bg     : #ffffff;
        --mariam-drop-shadow : 0 16px 40px rgba(122,15,28,0.22);
        --mariam-drop-border : #ece4d6;
        --mariam-item-hover  : #fdf3ee;
        --mariam-item-txt    : #1a1f2e;
        --mariam-item-icon   : #9c1524;
        --mariam-divider     : #f0eadf;
        --mariam-radius      : 8px;
        --mariam-ff          : 'Kumbh Sans', sans-serif;
        --mariam-ff-display  : 'Playfair Display', serif;
    }

    .hbtp-root *, .hbtp-root *::before, .hbtp-root *::after { box-sizing: border-box; margin: 0; padding: 0; }
    .hbtp-root { font-family: var(--mariam-ff); position: sticky; top: 0; z-index: 1000; width: 100%; }

    /* ===== TOP BAR ===== */
    .hbtp-top {
        background     : linear-gradient(120deg, var(--mariam-top-bg-a), var(--mariam-top-bg-b));
        height         : 56px;
        display        : flex;
        align-items    : center;
        justify-content: space-between;
        gap            : 16px;
        padding        : 0 22px;
        border-bottom  : 1px solid var(--mariam-top-border);
        box-shadow     : 0 2px 14px rgba(8,25,46,0.18);
        position       : relative;
        z-index        : 2;
    }

    .hbtp-brand { display:flex; align-items:center; gap:11px; text-decoration:none; flex-shrink:0; }
    .hbtp-brand-icon {
        width:38px; height:38px; border-radius:10px; flex-shrink:0;
        background: linear-gradient(145deg, var(--mariam-accent-lt), var(--mariam-accent));
        display:flex; align-items:center; justify-content:center;
        font-size:18px; color:#1a2b40; box-shadow: 0 4px 12px rgba(232,168,56,0.35);
        transition: transform .2s, box-shadow .2s;
    }
    .hbtp-brand:hover .hbtp-brand-icon { transform: translateY(-1px) scale(1.04); box-shadow: 0 6px 16px rgba(232,168,56,0.45); }
    .hbtp-brand-title {
        font-size:19px; font-weight:700; color:#fff; letter-spacing:.2px; line-height:1.15; display:block;
        font-family: var(--mariam-ff-display);
    }
    .hbtp-brand-sub {
        font-size:9.5px; color:rgba(255,255,255,.52); letter-spacing:1.1px; text-transform:uppercase;
        line-height:1; margin-top:4px; display:block; font-weight:400;
    }

    .hbtp-top-right { display:flex; align-items:center; gap:5px; flex-shrink:0; }

    .hbtp-icon-btn {
        width:35px; height:35px; border-radius:var(--mariam-radius);
        background:rgba(255,255,255,.07); border:1px solid rgba(255,255,255,.10);
        color:rgba(255,255,255,.75); display:flex; align-items:center; justify-content:center;
        font-size:14.5px; cursor:pointer; transition:background .15s,color .15s,transform .15s;
        position:relative; text-decoration:none;
    }
    .hbtp-icon-btn:hover { background:rgba(255,255,255,.16); color:#fff; transform: translateY(-1px); }

    .hbtp-notif-dot {
        position:absolute; top:6px; right:6px; width:7px; height:7px;
        background:var(--mariam-urgent); border-radius:50%; border:1.5px solid var(--mariam-top-bg-a);
        animation: mariam-pulse 2s infinite;
    }

    @keyframes mariam-pulse {
        0%   { box-shadow: 0 0 0 0 rgba(214,69,69,.55); }
        70%  { box-shadow: 0 0 0 6px rgba(214,69,69,0); }
        100% { box-shadow: 0 0 0 0 rgba(214,69,69,0); }
    }

    .hbtp-sep { width:1px; height:24px; background:rgba(255,255,255,.12); margin:0 6px; flex-shrink:0; }

    /* ===== SÉLECTEUR D'ANNÉE SCOLAIRE ===== */
    .hbtp-year-wrap {
        position: relative;
        display: flex;
        align-items: center;
        flex-shrink: 0;
    }
    .hbtp-year-wrap i.hbtp-year-icon {
        position: absolute;
        left: 12px;
        font-size: 13px;
        color: var(--mariam-accent-lt);
        pointer-events: none;
    }
    .hbtp-year-select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        height: 35px;
        padding: 0 30px 0 32px;
        background: rgba(255,255,255,.07);
        border: 1px solid rgba(255,255,255,.14);
        border-radius: var(--mariam-radius);
        color: #fff;
        font-family: var(--mariam-ff);
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s, border-color .15s;
        min-width: 128px;
    }
    .hbtp-year-select:hover {
        background: rgba(255,255,255,.14);
        border-color: rgba(232,168,56,.4);
    }
    .hbtp-year-select:focus {
        outline: none;
        border-color: var(--mariam-accent);
        box-shadow: 0 0 0 3px rgba(232,168,56,.20);
    }
    .hbtp-year-select option {
        background: var(--mariam-top-bg-a);
        color: #fff;
    }
    .hbtp-year-wrap::after {
        content: '';
        position: absolute;
        right: 11px;
        top: 50%;
        width: 7px;
        height: 7px;
        border-right: 1.5px solid rgba(255,255,255,.6);
        border-bottom: 1.5px solid rgba(255,255,255,.6);
        transform: translateY(-65%) rotate(45deg);
        pointer-events: none;
    }
    .hbtp-year-badge-active {
        position: absolute;
        top: -3px;
        right: -3px;
        width: 8px;
        height: 8px;
        background: #35b06a;
        border: 1.5px solid var(--mariam-top-bg-a);
        border-radius: 50%;
    }

    @media (max-width: 900px) {
        .hbtp-year-select { min-width: 100px; font-size: 11.5px; padding-left: 28px; }
    }
    @media (max-width: 560px) {
        .hbtp-year-wrap { display: none; }
    }

    /* ===== AVATAR ===== */
    .hbtp-avatar-wrap { position:relative; }
    .hbtp-avatar-btn {
        display:flex; align-items:center; gap:9px; padding:0 10px 0 6px; height:37px;
        background:rgba(255,255,255,.07); border:1px solid rgba(255,255,255,.10);
        border-radius:var(--mariam-radius); cursor:pointer; transition:background .15s;
    }
    .hbtp-avatar-btn:hover { background:rgba(255,255,255,.16); }
    .hbtp-avatar-circle {
        width:27px; height:27px; border-radius:50%;
        background: linear-gradient(145deg, var(--mariam-accent-lt), var(--mariam-accent));
        color:#1a2b40; font-size:11px; font-weight:800; display:flex;
        align-items:center; justify-content:center; flex-shrink:0;
        box-shadow: 0 0 0 2px rgba(255,255,255,.15);
    }
    .hbtp-avatar-name  { font-size:12.5px; font-weight:600; color:#fff; line-height:1; display:block; }
    .hbtp-avatar-role  { font-size:10px; color:rgba(255,255,255,.45); line-height:1; margin-top:3px; display:block; font-weight:300; }
    .hbtp-avatar-caret { font-size:10px; color:rgba(255,255,255,.5); margin-left:2px; transition:transform .2s; }
    .hbtp-avatar-wrap.open .hbtp-avatar-caret { transform:rotate(180deg); }

    .hbtp-user-drop {
        position:absolute; top:calc(100% + 8px); right:0; width:236px;
        background:var(--mariam-drop-bg); border-radius:12px;
        box-shadow:var(--mariam-drop-shadow); border:1px solid var(--mariam-drop-border);
        display:none; z-index:9999; overflow:hidden;
    }
    .hbtp-avatar-wrap.open .hbtp-user-drop { display:block; animation: mariam-drop-in .16s ease-out; }

    @keyframes mariam-drop-in {
        from { opacity:0; transform: translateY(-6px); }
        to   { opacity:1; transform: translateY(0); }
    }

    .hbtp-udrop-header { padding:15px 16px; background:linear-gradient(135deg,#faf6ee,#f4ecdb); border-bottom:1px solid var(--mariam-divider); }
    .hbtp-udrop-name   { font-size:13px; font-weight:700; color:var(--mariam-item-txt); }
    .hbtp-udrop-email  { font-size:11px; color:#96979c; margin-top:2px; }
    .hbtp-udrop-role   {
        display:inline-block; margin-top:7px; background:#f0e0bb; color:var(--mariam-accent-dark);
        font-size:9.5px; font-weight:800; padding:3px 9px; border-radius:20px;
        letter-spacing:.6px; text-transform:uppercase;
    }
    .hbtp-udrop-item { display:flex; align-items:center; gap:10px; padding:10px 16px; font-size:13px; color:var(--mariam-item-txt); text-decoration:none; transition:background .12s, padding-left .12s; }
    .hbtp-udrop-item:hover    { background:var(--mariam-item-hover); padding-left:20px; }
    .hbtp-udrop-item i        { font-size:14px; color:var(--mariam-item-icon); width:16px; }
    .hbtp-udrop-item.danger   { color:var(--mariam-urgent-dark); }
    .hbtp-udrop-item.danger i { color:var(--mariam-urgent-dark); }
    .hbtp-udrop-item.danger:hover { background:#fdf2f2; }
    .hbtp-udrop-div { height:1px; background:var(--mariam-divider); margin:4px 0; }

    /* ===== NAV BAR ===== */
    .hbtp-nav {
        background  : linear-gradient(90deg, var(--mariam-nav-bg-a), var(--mariam-nav-bg-b));
        height      : 46px;
        display     : flex;
        align-items : stretch;
        padding     : 0 8px;
        position    : relative;
        z-index     : 900;
        overflow    : visible;
        box-shadow  : inset 0 1px 0 rgba(255,255,255,.06);
    }
    .hbtp-nav-items { display:flex; align-items:stretch; flex:1; min-width:0; }

    /* ===== NAV ITEMS ===== */
    .hnav-item {
        position   : relative;
        display    : flex;
        align-items: stretch;
        flex-shrink: 0;
    }
    .hnav-trigger {
        display        : flex;
        align-items    : center;
        gap            : 7px;
        padding        : 0 14px;
        color          : rgba(255,255,255,.90);
        font-size      : 13px;
        font-weight    : 600;
        cursor         : pointer;
        white-space    : nowrap;
        text-decoration: none;
        transition     : background .15s, color .15s;
        font-family    : var(--mariam-ff);
        border         : none;
        background     : transparent;
        height         : 100%;
        position       : relative;
    }
    .hnav-trigger > i:not(.caret) { font-size:14px; width:16px; text-align:center; }
    .hnav-trigger .caret { font-size:10px; opacity:.7; margin-left:1px; transition:transform .2s; }
    .hnav-item.open > .hnav-trigger .caret { transform:rotate(180deg); }

    .hnav-trigger::after {
        content:''; position:absolute; left:14px; right:14px; bottom:0; height:3px;
        background: var(--mariam-accent); border-radius: 2px 2px 0 0;
        transform: scaleX(0); transform-origin:center; transition: transform .18s ease;
    }
    .hnav-item:hover > .hnav-trigger,
    .hnav-item.open  > .hnav-trigger { background:rgba(0,0,0,.16); color:#fff; }

    .hnav-item:hover > .hnav-trigger::after,
    .hnav-item.open  > .hnav-trigger::after,
    .hnav-item.active > .hnav-trigger::after { transform: scaleX(1); }

    .hnav-item.active > .hnav-trigger {
        background: rgba(232, 178, 58, 0.15);
        color: var(--mariam-accent-lt);
        box-shadow: inset 0 -3px 0 var(--mariam-accent);
    }

    .hnav-badge {
        display:inline-flex; align-items:center; justify-content:center;
        min-width:17px; height:17px; padding:0 5px; border-radius:20px;
        background: var(--mariam-urgent); color:#fff; font-size:10px; font-weight:800;
        line-height:1; margin-left:2px; box-shadow: 0 0 0 2px rgba(0,0,0,.08);
    }

    .hnav-drop {
        position      : absolute;
        top           : 46px;
        left          : 0;
        min-width     : 264px;
        background    : var(--mariam-drop-bg);
        border-radius : 0 0 12px 12px;
        box-shadow    : var(--mariam-drop-shadow);
        border        : 1px solid var(--mariam-drop-border);
        border-top    : 3px solid var(--mariam-accent);
        z-index       : 99999;
        padding       : 6px 0;
        opacity       : 0;
        visibility    : hidden;
        transform     : translateY(-6px);
        transition    : opacity .16s ease, transform .16s ease, visibility 0s linear .16s;
        pointer-events: none;
    }
    .hnav-item.open > .hnav-drop {
        opacity       : 1;
        visibility    : visible;
        transform     : translateY(0);
        transition    : opacity .16s ease, transform .16s ease, visibility 0s linear 0s;
        pointer-events: auto;
    }
    .hnav-item.hnav-urgent > .hnav-drop { border-top-color: var(--mariam-urgent); }

    .hnav-drop-title {
        padding:9px 16px 4px; font-size:10px; text-transform:uppercase;
        color:#a9a49a; letter-spacing:.9px; font-weight:800; font-family:var(--mariam-ff);
        display:flex; align-items:center; gap:6px;
    }
    .hnav-drop-title:first-child { padding-top: 8px; }
    .hnav-drop-title i { font-size: 10px; color: var(--mariam-accent-dark); }

    .hnav-drop-item {
        display:flex; align-items:center; gap:10px; padding:10px 16px; font-size:13px; font-weight:400; color:var(--mariam-item-txt); text-decoration:none; transition: background .12s, padding-left .12s, border-color .12s; line-height:1.3; font-family:var(--mariam-ff); white-space:nowrap; border-left:2.5px solid transparent;
    }
    .hnav-drop-item:hover  { background:var(--mariam-item-hover); padding-left:20px; border-left-color: var(--mariam-accent); }
    .hnav-drop-item i      { font-size:14px; color:var(--mariam-item-icon); width:16px; flex-shrink:0; text-align:center; }
    .hnav-drop-item.urgent-item i { color: var(--mariam-urgent); }
    .hnav-drop-item.urgent-item:hover { border-left-color: var(--mariam-urgent); }
    .hnav-drop-item .hnav-mini-badge {
        margin-left:auto; font-size:10px; font-weight:800; color:#fff;
        background: var(--mariam-urgent); border-radius:20px; padding:1px 7px;
    }

    .hnav-drop-item.active {
        background: rgba(210, 16, 52, 0.04);
        border-left-color: var(--mariam-accent);
        color: var(--mariam-top-bg-a);
        font-weight: 600;
    }
    .hnav-drop-item.active i {
        color: var(--mariam-top-bg-a);
    }

    .hnav-drop-div { height:1px; background:var(--mariam-divider); margin:5px 0; }

    .hnav-more { position:relative; display:flex; align-items:stretch; flex-shrink:0; margin-left:auto; }
    .hnav-more > .hnav-drop { left:auto; right:0; min-width:264px; }
    .hnav-more-title {
        padding:8px 16px 3px; font-size:10px; text-transform:uppercase;
        color:#a9a49a; letter-spacing:.8px; font-weight:800; font-family:var(--mariam-ff);
        display:flex; align-items:center; gap:6px;
    }
    .hnav-more-title i { font-size:12px; color:var(--mariam-accent); }

    /* ===== HAMBURGER ===== */
    .hbtp-hamburger {
        display:none; flex-direction:column; gap:4px; justify-content:center;
        width:35px; height:35px; cursor:pointer; padding:6px;
        border-radius:var(--mariam-radius); background:rgba(255,255,255,.07);
        border:1px solid rgba(255,255,255,.10); flex-shrink:0;
    }
    .hbtp-hamburger span { display:block; width:100%; height:2px; background:rgba(255,255,255,.85); border-radius:2px; transition:transform .25s,opacity .25s; }
    .hbtp-hamburger.open span:nth-child(1) { transform:translateY(6px) rotate(45deg); }
    .hbtp-hamburger.open span:nth-child(2) { opacity:0; }
    .hbtp-hamburger.open span:nth-child(3) { transform:translateY(-6px) rotate(-45deg); }

    /* ===== RESPONSIVE ===== */
    @media (max-width:1100px) { .hnav-trigger { padding:0 11px; font-size:12px; } }
    @media (max-width:768px) {
        .hbtp-hamburger { display:flex; }
        .hbtp-brand-sub { display:none; }
        .hbtp-nav {
            height:0; overflow:hidden; flex-direction:column;
            padding:0; transition:height .3s ease; display:flex;
        }
        .hbtp-nav.mobile-open { height:auto; padding:6px 0; overflow:visible; }
        .hnav-item     { flex-direction:column; height:auto; }
        .hnav-trigger  { padding:12px 16px; justify-content:space-between; height:auto; }
        .hnav-trigger::after { display:none; }
        .hnav-drop {
            position:static; box-shadow:none; border:none;
            border-top:2px solid rgba(255,255,255,.3); border-radius:0;
            background:rgba(0,0,0,.12); transform:none !important;
        }
        .hnav-drop-item       { color:rgba(255,255,255,.92); white-space:normal; border-left-color: transparent !important; }
        .hnav-drop-item:hover { background:rgba(0,0,0,.12); padding-left:20px; }
        .hnav-drop-item i     { color:rgba(255,255,255,.72); }
        .hnav-drop-title      { color:rgba(255,255,255,.5); }
        .hnav-drop-title i    { color: var(--mariam-accent-lt); }
        .hnav-drop-div        { background:rgba(255,255,255,.15); }
        .hbtp-user-drop       { right:-10px; }
        .hnav-more            { display:none !important; }
    }
    @media (max-width:400px) {
        .hbtp-avatar-name,.hbtp-avatar-role { display:none; }
        .hbtp-avatar-btn { padding:0 6px; }
    }
</style>

{{-- ============================================
     HEADER MARIAM - VERSION VIEW COMPOSER
     ============================================ --}}

<div class="hbtp-root" id="header-top" role="banner">
    <!-- TOP BAR -->
    <div class="hbtp-top">
        <a href="{{ route('tableau') }}" class="hbtp-brand" title="Accueil École Internationale Mariam">
            <div class="hbtp-brand-icon"><i class="fas fa-graduation-cap"></i></div>
            <div>
                <span class="hbtp-brand-title">EIM · Gestion</span>
                <span class="hbtp-brand-sub">École Internationale Mariam</span>
            </div>
        </a>

        <div class="hbtp-top-right">
            {{-- Sélecteur d'année scolaire --}}
            <div class="hbtp-year-wrap" title="Changer d'année scolaire">
                <i class="fas fa-calendar-alt hbtp-year-icon"></i>
                <select name="annee_id" id="select-annee-scolaire" class="hbtp-year-select">
                    @if(isset($headerAnnees) && $headerAnnees->count() > 0)
                        @foreach($headerAnnees as $annee)
                            <option value="{{ $annee->id }}"
                                {{ isset($headerAnneeCourante) && $headerAnneeCourante->id == $annee->id ? 'selected' : '' }}>
                                {{ $annee->libelle }}
                            </option>
                        @endforeach
                    @else
                        <option value="">Aucune année disponible</option>
                    @endif
                </select>
                <span class="hbtp-year-badge-active" title="Année en cours"></span>
            </div>

            <div class="hbtp-sep"></div>

            {{-- Bouton Hamburger --}}
            <button class="hbtp-hamburger" id="hbtp-hamburger" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="hbtp-main-nav">
                <span></span><span></span><span></span>
            </button>

            {{-- Thème --}}
            <button class="hbtp-icon-btn" id="themeToggle" aria-label="Basculer le mode sombre">
                <i class="fas fa-moon" id="themeIcon"></i>
            </button>

            {{-- Recherche --}}
            <button class="hbtp-icon-btn" id="btnSearch" title="Recherche (Ctrl+K)">
                <i class="fas fa-search"></i>
            </button>

            {{-- Notifications --}}
            <a href="#" class="hbtp-icon-btn" title="Notifications">
                <i class="fas fa-bell"></i>
                <span class="hbtp-notif-dot"></span>
            </a>

            <div class="hbtp-sep"></div>

            {{-- Avatar utilisateur --}}
            <div class="hbtp-avatar-wrap" id="hbtp-avatar-wrap">
                <div class="hbtp-avatar-btn" id="hbtp-avatar-btn" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false">
                    <div class="hbtp-avatar-circle">
                        {{ strtoupper(substr($headerNomComplet ?? 'AD', 0, 2)) }}
                    </div>
                    <div>
                        <span class="hbtp-avatar-name">{{ $headerNomComplet ?? 'Utilisateur' }}</span>
                        <span class="hbtp-avatar-role">{{ $headerRoleLabel ?? 'Rôle' }}</span>
                    </div>
                    <i class="fas fa-chevron-down hbtp-avatar-caret"></i>
                </div>

                {{-- Dropdown utilisateur --}}
                <div class="hbtp-user-drop" id="hbtp-user-drop" role="menu">
                    <div class="hbtp-udrop-header">
                        <div class="hbtp-udrop-name">{{ $headerNomComplet ?? 'Nom Prénom' }}</div>
                        <div class="hbtp-udrop-email">{{ $headerUserEmail ?? 'user@ecoleinternationalemariam.net' }}</div>
                        <div class="hbtp-udrop-role">{{ $headerRoleLabel ?? 'Rôle' }}</div>
                    </div>
                    <a href="{{ route('profile') }}" class="hbtp-udrop-item" role="menuitem">
                        <i class="fas fa-user-circle"></i> Mon profil
                    </a>
                    <a href="{{ route('profile') }}#password" class="hbtp-udrop-item" role="menuitem">
                        <i class="fas fa-key"></i> Changer mot de passe
                    </a>
                    <div class="hbtp-udrop-div"></div>
                    <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                        @csrf
                        <button type="submit" class="hbtp-udrop-item danger" role="menuitem" style="width:100%; border:none; background:transparent; text-align:left; cursor:pointer;">
                            <i class="fas fa-sign-out-alt"></i> Déconnexion
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- NAV BAR -->
    <nav class="hbtp-nav" id="hbtp-main-nav" aria-label="Navigation principale">
        <div class="hbtp-nav-items" id="hbtp-nav-items">

            {{-- Tableau de bord --}}
            <div class="hnav-item {{ request()->routeIs('tableau') ? 'active' : '' }}">
                <a href="{{ route('tableau') }}" class="hnav-trigger">
                    <i class="fas fa-chart-pie"></i> Tableau de bord
                </a>
            </div>

            {{-- Scolarité --}}
            <div class="hnav-item">
                <a href="#" class="hnav-trigger" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-school"></i> Scolarité <i class="fas fa-chevron-down caret"></i>
                </a>
                <div class="hnav-drop" role="menu">
                    <a href="#" class="hnav-drop-item"><i class="fas fa-user-graduate"></i> Élèves</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-search"></i> Rechercher un élève</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-chalkboard"></i> Classes</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-user-friends"></i> Parents</a>
                    <div class="hnav-drop-div"></div>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-file-signature"></i> Préinscriptions</a>
                </div>
            </div>

            {{-- Trésorerie --}}
            <div class="hnav-item">
                <a href="#" class="hnav-trigger">
                    <i class="fas fa-wallet"></i> Trésorerie <i class="fas fa-chevron-down caret"></i>
                </a>
                <div class="hnav-drop">
                    <div class="hnav-drop-title"><i class="fas fa-arrow-right-arrow-left"></i> Opérations</div>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-receipt"></i> Paiements élèves</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-exchange-alt"></i> Mouvements de trésorerie</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-cash-register"></i> Poste de caisse</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-clipboard-list"></i> Sessions de caisse</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-file-invoice"></i> Bons de caisse</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-money-check-alt"></i> Avances</a>

                    <div class="hnav-drop-div"></div>
                    <div class="hnav-drop-title"><i class="fas fa-sliders-h"></i> Paramétrage</div>
                    <a href="{{ url('/frais-ecoles') }}" class="hnav-drop-item"><i class="fas fa-tags"></i> Tarifs</a>
                    <a href="{{ url('/evenements') }}" class="hnav-drop-item"><i class="fas fa-calendar-check"></i> Événements</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-concierge-bell"></i> Services</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-futbol"></i> Activités extrascolaires</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-percent"></i> Remises</a>
                </div>
            </div>

            {{-- Recouvrement --}}
            <div class="hnav-item hnav-urgent">
                <a href="#" class="hnav-trigger">
                    <i class="fas fa-hand-holding-usd"></i> Recouvrement
                    <span class="hnav-badge">12</span>
                    <i class="fas fa-chevron-down caret"></i>
                </a>
                <div class="hnav-drop" role="menu">
                    <a href="#" class="hnav-drop-item"><i class="fas fa-chart-line"></i> Tableau de bord recouvrement</a>
                    <a href="#" class="hnav-drop-item urgent-item">
                        <i class="fas fa-exclamation-triangle"></i> Échéances en retard
                        <span class="hnav-mini-badge">12</span>
                    </a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-hourglass-half"></i> Échéances à venir</a>
                    <div class="hnav-drop-div"></div>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-history"></i> Historique des relances</a>
                    <div class="hnav-drop-div"></div>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-file-export"></i> Rapport de recouvrement</a>
                </div>
            </div>

            {{-- CDI --}}
            <div class="hnav-item">
                <a href="#" class="hnav-trigger">
                    <i class="fas fa-book-open"></i> CDI <i class="fas fa-chevron-down caret"></i>
                </a>
                <div class="hnav-drop">
                    <div class="hnav-drop-title"><i class="fas fa-layer-group"></i> Fonds documentaire</div>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-book"></i> Catalogue</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-book-medical"></i> Nouveau livre</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-barcode"></i> Exemplaires</a>

                    <div class="hnav-drop-div"></div>
                    <div class="hnav-drop-title"><i class="fas fa-arrows-rotate"></i> Circulation</div>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-hand-holding"></i> Prêts</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-bookmark"></i> Réservations</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-coins"></i> Pénalités</a>
                </div>
            </div>

            {{-- Transport --}}
            <div class="hnav-item">
                <a href="#" class="hnav-trigger">
                    <i class="fas fa-bus-alt"></i> Transport <i class="fas fa-chevron-down caret"></i>
                </a>
                <div class="hnav-drop">
                    <div class="hnav-drop-title"><i class="fas fa-route"></i> Organisation</div>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-route"></i> Circuits</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-map-marker-alt"></i> Arrêts</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-user-check"></i> Affectations</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-clipboard-check"></i> Présences transport</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-gas-pump"></i> Pleins &amp; entretien</a>

                    <div class="hnav-drop-div"></div>
                    <div class="hnav-drop-title"><i class="fas fa-file-invoice-dollar"></i> Facturation</div>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-user-plus"></i> Souscriptions bus</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-file-invoice-dollar"></i> Paiements bus</a>
                </div>
            </div>

            {{-- Cantine --}}
            <div class="hnav-item">
                <a href="#" class="hnav-trigger">
                    <i class="fas fa-utensils"></i> Cantine <i class="fas fa-chevron-down caret"></i>
                </a>
                <div class="hnav-drop">
                    <div class="hnav-drop-title"><i class="fas fa-sliders-h"></i> Paramétrage</div>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-calendar-day"></i> Jours de service</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-list-ul"></i> Types de repas</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-clipboard-list"></i> Menus</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-utensil-spoon"></i> Préparations</a>

                    <div class="hnav-drop-div"></div>
                    <div class="hnav-drop-title"><i class="fas fa-chart-line"></i> Suivi &amp; facturation</div>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-chart-line"></i> Consommations</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-user-plus"></i> Souscriptions cantine</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-file-invoice-dollar"></i> Facturation cantine</a>
                </div>
            </div>

            {{-- RH --}}
            <div class="hnav-item">
                <a href="#" class="hnav-trigger">
                    <i class="fas fa-users"></i> RH <i class="fas fa-chevron-down caret"></i>
                </a>
                <div class="hnav-drop">
                    <div class="hnav-drop-title"><i class="fas fa-id-badge"></i> Personnel</div>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-id-badge"></i> Employés</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-user-tie"></i> Postes</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-building"></i> Départements</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-file-signature"></i> Contrats</a>

                    <div class="hnav-drop-div"></div>
                    <div class="hnav-drop-title"><i class="fas fa-clock"></i> Suivi</div>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-umbrella-beach"></i> Congés</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-clock"></i> Présences</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-file-invoice-dollar"></i> Bulletins de paie</a>
                </div>
            </div>

            {{-- Stock --}}
            <div class="hnav-item">
                <a href="#" class="hnav-trigger">
                    <i class="fas fa-boxes"></i> Stock <i class="fas fa-chevron-down caret"></i>
                </a>
                <div class="hnav-drop">
                    <a href="#" class="hnav-drop-item"><i class="fas fa-tags"></i> Catégories</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-cube"></i> Articles</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-warehouse"></i> Magasins</a>
                    <div class="hnav-drop-div"></div>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-exchange-alt"></i> Mouvements</a>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-clipboard-list"></i> Inventaires</a>
                </div>
            </div>

            {{-- Administration --}}
            <div class="hnav-item">
                <a href="#" class="hnav-trigger">
                    <i class="fas fa-cog"></i> Administration <i class="fas fa-chevron-down caret"></i>
                </a>
                <div class="hnav-drop">
                    <a href="{{ url('/users') }}" class="hnav-drop-item"><i class="fas fa-users-cog"></i> Utilisateurs</a>
                    <a href="{{ url('/annees') }}" class="hnav-drop-item"><i class="fas fa-calendar-alt"></i> Années scolaires</a>

                    <div class="hnav-drop-div"></div>
                    <div class="hnav-drop-title"><i class="fas fa-graduation-cap"></i> Structure pédagogique</div>

                    <a href="{{ url('/cycles') }}" class="hnav-drop-item"><i class="fas fa-layer-group"></i> Cycles</a>
                    <a href="{{ url('/niveaux') }}" class="hnav-drop-item"><i class="fas fa-flag"></i> Niveaux</a>

                    <div class="hnav-drop-div"></div>
                    <a href="#" class="hnav-drop-item"><i class="fas fa-history"></i> Journal d'activités</a>
                </div>
            </div>

        </div>
    </nav>
</div>

<script>
    (function () {
        'use strict';

        var navItems    = document.getElementById('hbtp-nav-items');
        var isMobile    = function () { return window.innerWidth <= 768; };

        function closeAll() {
            document.querySelectorAll('.hnav-item.open').forEach(function (el) {
                el.classList.remove('open');
                var t = el.querySelector(':scope > .hnav-trigger');
                if (t) t.setAttribute('aria-expanded', 'false');
            });
        }

        function openItem(item) {
            item.classList.add('open');
            var t = item.querySelector(':scope > .hnav-trigger');
            if (t) t.setAttribute('aria-expanded', 'true');
        }

        function bindItem(item) {
            if (item._mariam) return;
            item._mariam = true;
            var trigger = item.querySelector(':scope > .hnav-trigger');
            if (!trigger) return;

            item.addEventListener('mouseenter', function () {
                if (isMobile()) return;
                closeAll();
                openItem(item);
            });
            item.addEventListener('mouseleave', function () {
                if (isMobile()) return;
                item.classList.remove('open');
                trigger.setAttribute('aria-expanded', 'false');
            });

            trigger.addEventListener('click', function (e) {
                if (!isMobile()) return;
                e.preventDefault();
                var was = item.classList.contains('open');
                closeAll();
                if (!was) openItem(item);
            });
        }

        function bindAll() {
            document.querySelectorAll('#hbtp-nav-items > .hnav-item:not(.hnav-more)').forEach(bindItem);
        }

        function rebuildMoreMenu() {
            var old = navItems.querySelector('.hnav-more');
            if (old) old.remove();

            var items = Array.from(navItems.querySelectorAll(':scope > .hnav-item'));
            items.forEach(function (el) { el.style.display = ''; el._mariam = false; });

            if (isMobile()) { bindAll(); return; }

            requestAnimationFrame(function () {
                var navBar    = document.querySelector('.hbtp-nav');
                var available = navBar ? navBar.clientWidth - 100 : 9999;
                var total     = 0;
                var hidden    = [];

                items.forEach(function (item) {
                    total += item.offsetWidth;
                    if (total > available) {
                        hidden.push(item);
                        item.style.display = 'none';
                    }
                });

                if (hidden.length) {
                    var more = document.createElement('div');
                    more.className = 'hnav-item hnav-more';

                    var mTrigger = document.createElement('a');
                    mTrigger.href = '#';
                    mTrigger.className = 'hnav-trigger';
                    mTrigger.setAttribute('aria-haspopup', 'true');
                    mTrigger.setAttribute('aria-expanded', 'false');
                    mTrigger.innerHTML = '<i class="fas fa-ellipsis-h"></i>&nbsp;Plus&nbsp;<i class="fas fa-chevron-down caret"></i>';

                    var mDrop = document.createElement('div');
                    mDrop.className = 'hnav-drop';
                    mDrop.setAttribute('role', 'menu');

                    hidden.forEach(function (item, idx) {
                        var trig = item.querySelector(':scope > .hnav-trigger');
                        var drop = item.querySelector(':scope > .hnav-drop');
                        if (!trig) return;

                        var ic  = trig.querySelector('i:not(.caret)');
                        var tmp = trig.cloneNode(true);
                        var cr  = tmp.querySelector('.caret'); if (cr) cr.remove();
                        var bd  = tmp.querySelector('.hnav-badge'); if (bd) bd.remove();
                        var lbl = tmp.textContent.trim();

                        if (drop) {
                            var sec = document.createElement('div');
                            sec.className = 'hnav-more-title';
                            sec.innerHTML = (ic ? ic.outerHTML : '') + ' ' + lbl;
                            mDrop.appendChild(sec);
                            drop.querySelectorAll('.hnav-drop-item, .hnav-drop-div, .hnav-drop-title').forEach(function (n) {
                                mDrop.appendChild(n.cloneNode(true));
                            });
                            if (idx < hidden.length - 1) {
                                var sep = document.createElement('div');
                                sep.className = 'hnav-drop-div';
                                mDrop.appendChild(sep);
                            }
                        } else {
                            var a = document.createElement('a');
                            a.href = trig.getAttribute('href') || '#';
                            a.className = 'hnav-drop-item';
                            a.innerHTML = (ic ? ic.outerHTML : '') + ' ' + lbl;
                            mDrop.appendChild(a);
                        }
                    });

                    more.appendChild(mTrigger);
                    more.appendChild(mDrop);
                    navItems.appendChild(more);

                    more.addEventListener('mouseenter', function () {
                        if (isMobile()) return;
                        closeAll();
                        openItem(more);
                    });
                    more.addEventListener('mouseleave', function () {
                        if (isMobile()) return;
                        more.classList.remove('open');
                        mTrigger.setAttribute('aria-expanded', 'false');
                    });
                    mTrigger.addEventListener('click', function (e) {
                        e.preventDefault();
                        if (!isMobile()) return;
                        var was = more.classList.contains('open');
                        closeAll();
                        if (!was) openItem(more);
                    });
                }

                bindAll();
            });
        }

        /* ===== AVATAR ===== */
        var aWrap = document.getElementById('hbtp-avatar-wrap');
        var aBtn  = document.getElementById('hbtp-avatar-btn');
        if (aWrap && aBtn) {
            aWrap.addEventListener('mouseenter', function () {
                aWrap.classList.add('open');
                aBtn.setAttribute('aria-expanded', 'true');
            });
            aWrap.addEventListener('mouseleave', function () {
                aWrap.classList.remove('open');
                aBtn.setAttribute('aria-expanded', 'false');
            });
            aBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                var o = aWrap.classList.toggle('open');
                aBtn.setAttribute('aria-expanded', String(o));
            });
            document.addEventListener('click', function (e) {
                if (!aWrap.contains(e.target)) {
                    aWrap.classList.remove('open');
                    aBtn.setAttribute('aria-expanded', 'false');
                }
            });
        }

        /* ===== HAMBURGER ===== */
        var hbg = document.getElementById('hbtp-hamburger');
        var nav = document.getElementById('hbtp-main-nav');
        if (hbg && nav) {
            hbg.addEventListener('click', function () {
                var o = nav.classList.toggle('mobile-open');
                hbg.classList.toggle('open', o);
                hbg.setAttribute('aria-expanded', String(o));
            });
        }

        /* ===== THÈME ===== */
        var tBtn  = document.getElementById('themeToggle');
        var tIcon = document.getElementById('themeIcon');
        function applyTheme(dark) {
            document.documentElement.classList.toggle('dark-theme', dark);
            if (tIcon) tIcon.className = dark ? 'fas fa-sun' : 'fas fa-moon';
            try { localStorage.setItem('mariam-theme', dark ? 'dark' : 'light'); } catch(e){}
        }
        if (tBtn) {
            try { var sv = localStorage.getItem('mariam-theme'); if (sv) applyTheme(sv === 'dark'); } catch(e){}
            tBtn.addEventListener('click', function () {
                applyTheme(!document.documentElement.classList.contains('dark-theme'));
            });
        }

        /* ===== CTRL+K RECHERCHE ===== */
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                var b = document.getElementById('btnSearch');
                if (b) b.click();
            }
        });

        /* ===== SURBRILLANCE DES MENUS ===== */
        function highlightActive() {
            var currentPath = window.location.pathname;

            document.querySelectorAll('.hnav-drop-item, .hnav-trigger').forEach(function (el) {
                var href = el.getAttribute('href');
                if (href && href !== '#' && href !== '' && href === currentPath) {
                    el.classList.add('active');
                    var parentItem = el.closest('.hnav-item');
                    if (parentItem) {
                        parentItem.classList.add('active');
                    }
                } else {
                    el.classList.remove('active');
                }
            });
        }

        /* ===== RÉSIZE ===== */
        var rt;
        window.addEventListener('resize', function () {
            clearTimeout(rt);
            rt = setTimeout(rebuildMoreMenu, 200);
        });

        /* ===== SÉLECTEUR D'ANNÉE ===== */
        var yearSelect = document.getElementById('select-annee-scolaire');
        if (yearSelect) {
            yearSelect.addEventListener('change', function () {
                var url = new URL(window.location.href);
                url.searchParams.set('annee_id', this.value);
                window.location.href = url.toString();
            });
        }

        /* ===== INIT ===== */
        rebuildMoreMenu();
        highlightActive();

        console.log('✅ Header Mariam chargé avec succès');
        console.log('👤 Utilisateur:', '{{ $headerNomComplet ?? "Non connecté" }}');
        console.log('📅 Année:', '{{ $headerAnneeCourante->libelle ?? "Aucune" }}');

    })();
</script>
