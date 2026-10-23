{{-- resources/views/admin/layouts/partials/_header.blade.php --}}

<style>
    :root {
        /* Palette unique de l'application (identique à l'écran de connexion).
           Déjà déclarée dans layouts/app.blade.php avec les mêmes valeurs :
           redéclarée ici uniquement pour les jetons propres au header
           (drop-*, item-*, divider, radius) qui n'existent nulle part
           ailleurs. Ne jamais faire diverger les valeurs communes entre
           les deux fichiers. */
        --school-red         : var(--eim-red);
        --school-red-dark    : var(--eim-red-hover);
        --school-red-deep    : var(--eim-red-deep);
        --school-gold        : var(--eim-gold);
        --school-gold-soft   : var(--eim-gold-soft);
        --school-gold-dark   : var(--eim-gold-deep);
        --school-ink         : var(--eim-ink);
        --school-ink-deep    : #1E1414;
        --school-urgent      : #ff7a1a;
        --school-urgent-dark : #cc5500;
        --school-drop-bg     : #fffdf9;
        --school-drop-shadow : 0 16px 40px rgba(43,30,30,0.20);
        --school-drop-border : var(--eim-border);
        --school-item-hover  : var(--eim-cream);
        --school-item-txt    : var(--eim-ink);
        --school-item-icon   : var(--eim-red-hover);
        --school-divider     : var(--eim-border);
        --school-radius      : 8px;
        --school-ff          : var(--eim-ff);
        --school-ff-display  : var(--eim-ff);
    }

    .hbtp-root *, .hbtp-root *::before, .hbtp-root *::after { box-sizing: border-box; margin: 0; padding: 0; }
    .hbtp-root { font-family: var(--school-ff); position: sticky; top: 0; z-index: 1000; width: 100%; }

    /* ===== TOP BAR — identité de l'établissement ===== */
    .hbtp-top {
        background     : linear-gradient(120deg, var(--eim-red-deep), var(--eim-red));
        border-bottom  : 2px solid var(--eim-gold);
        height         : 58px;
        display        : flex;
        align-items    : center;
        justify-content: space-between;
        gap            : 16px;
        padding        : 0 22px;
        box-shadow     : 0 2px 14px rgba(20,29,39,0.18);
        position       : relative;
        z-index        : 2;
    }

    .hbtp-brand { display:flex; align-items:center; gap:11px; text-decoration:none; flex-shrink:0; }
    .hbtp-brand-icon {
        width:40px; height:40px; border-radius:999px; flex-shrink:0;
        display:flex; align-items:center; justify-content:center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.25);
        transition: transform .2s, box-shadow .2s;
    }
    .hbtp-brand-icon img { width:40px; height:40px; display:block; }
    .hbtp-brand:hover .hbtp-brand-icon { transform: translateY(-1px) scale(1.04); box-shadow: 0 6px 16px rgba(212,169,77,0.45); }
    .hbtp-brand-title {
        font-size:19px; font-weight:800; color:#fff; letter-spacing:-0.02em; line-height:1.15; display:block;
        font-family: var(--eim-ff);
    }
    .hbtp-brand-sub {
        font-size:9.5px; color:rgba(255,255,255,.85); letter-spacing:.12em; text-transform:uppercase;
        line-height:1; margin-top:4px; display:block; font-weight:600;
    }

    .hbtp-top-right { display:flex; align-items:center; gap:5px; flex-shrink:0; }

    .hbtp-icon-btn {
        width:35px; height:35px; border-radius:var(--school-radius);
        background:rgba(255,255,255,.07); border:1px solid rgba(255,255,255,.10);
        color:rgba(255,255,255,.75); display:flex; align-items:center; justify-content:center;
        font-size:14.5px; cursor:pointer; transition:background .15s,color .15s,transform .15s;
        position:relative; text-decoration:none;
    }
    .hbtp-icon-btn:hover { background:rgba(255,255,255,.16); color:#fff; transform: translateY(-1px); }

    .hbtp-notif-dot {
        position:absolute; top:6px; right:6px; width:7px; height:7px;
        background:var(--school-urgent); border-radius:50%; border:1.5px solid var(--school-red-dark);
        animation: mariam-pulse 2s infinite;
    }

    @keyframes mariam-pulse {
        0%   { box-shadow: 0 0 0 0 rgba(255,122,26,.55); }
        70%  { box-shadow: 0 0 0 6px rgba(255,122,26,0); }
        100% { box-shadow: 0 0 0 0 rgba(255,122,26,0); }
    }

    .hbtp-sep { width:1px; height:24px; background:rgba(255,255,255,.14); margin:0 6px; flex-shrink:0; }

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
        color: var(--school-gold-soft);
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
        border-radius: var(--school-radius);
        color: #fff;
        font-family: var(--school-ff);
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s, border-color .15s;
        min-width: 128px;
    }
    .hbtp-year-select:hover {
        background: rgba(255,255,255,.14);
        border-color: rgba(212,169,77,.4);
    }
    .hbtp-year-select:focus {
        outline: none;
        border-color: var(--school-gold);
        box-shadow: 0 0 0 3px rgba(212,169,77,.20);
    }
    .hbtp-year-select option {
        background: var(--school-red-dark);
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
        border: 1.5px solid var(--school-red-dark);
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
        border-radius:var(--school-radius); cursor:pointer; transition:background .15s;
    }
    .hbtp-avatar-btn:hover { background:rgba(255,255,255,.16); }
    .hbtp-avatar-circle {
        width:27px; height:27px; border-radius:50%;
        background: linear-gradient(145deg, var(--school-gold-soft), var(--school-gold));
        color:var(--school-red-deep); font-size:11px; font-weight:800; display:flex;
        align-items:center; justify-content:center; flex-shrink:0;
        box-shadow: 0 0 0 2px rgba(255,255,255,.15);
    }
    .hbtp-avatar-name  { font-size:12.5px; font-weight:600; color:#fff; line-height:1; display:block; }
    .hbtp-avatar-role  { font-size:10px; color:rgba(255,255,255,.45); line-height:1; margin-top:3px; display:block; font-weight:300; }
    .hbtp-avatar-caret { font-size:10px; color:rgba(255,255,255,.5); margin-left:2px; transition:transform .2s; }
    .hbtp-avatar-wrap.open .hbtp-avatar-caret { transform:rotate(180deg); }

    .hbtp-user-drop {
        position:absolute; top:calc(100% + 8px); right:0; width:236px;
        background:var(--school-drop-bg); border-radius:12px;
        box-shadow:var(--school-drop-shadow); border:1px solid var(--school-drop-border);
        display:none; z-index:9999; overflow:hidden;
    }
    .hbtp-avatar-wrap.open .hbtp-user-drop { display:block; animation: mariam-drop-in .16s ease-out; }

    @keyframes mariam-drop-in {
        from { opacity:0; transform: translateY(-6px); }
        to   { opacity:1; transform: translateY(0); }
    }

    .hbtp-udrop-header { padding:15px 16px; background:linear-gradient(135deg,var(--eim-cream),#f4ecdb); border-bottom:1px solid var(--school-divider); }
    .hbtp-udrop-name   { font-size:13px; font-weight:700; color:var(--school-item-txt); }
    .hbtp-udrop-email  { font-size:11px; color:#96979c; margin-top:2px; }
    .hbtp-udrop-role   {
        display:inline-block; margin-top:7px; background:#f0e0bb; color:var(--school-gold-dark);
        font-size:9.5px; font-weight:800; padding:3px 9px; border-radius:20px;
        letter-spacing:.6px; text-transform:uppercase;
    }
    .hbtp-udrop-item { display:flex; align-items:center; gap:10px; padding:10px 16px; font-size:13px; color:var(--school-item-txt); text-decoration:none; transition:background .12s, padding-left .12s; }
    .hbtp-udrop-item:hover    { background:var(--school-item-hover); padding-left:20px; }
    .hbtp-udrop-item i        { font-size:14px; color:var(--school-item-icon); width:16px; }
    .hbtp-udrop-item.danger   { color:var(--school-urgent-dark); }
    .hbtp-udrop-item.danger i { color:var(--school-urgent-dark); }
    .hbtp-udrop-item.danger:hover { background:#fdf2f2; }
    .hbtp-udrop-div { height:1px; background:var(--school-divider); margin:4px 0; }

    /* ===== NAV BAR — encre neutre, le rouge reste à l'identité ===== */
    .hbtp-nav {
        background  : var(--eim-ink);
        height      : 46px;
        display     : flex;
        align-items : stretch;
        padding     : 0 8px;
        position    : relative;
        z-index     : 900;
        overflow    : visible;
        box-shadow  : inset 0 1px 0 rgba(255,255,255,.05);
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
        color          : rgba(255,255,255,.82);
        font-size      : 13px;
        font-weight    : 600;
        cursor         : pointer;
        white-space    : nowrap;
        text-decoration: none;
        transition     : background .15s, color .15s;
        font-family    : var(--school-ff);
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
        background: var(--school-gold); border-radius: 2px 2px 0 0;
        transform: scaleX(0); transform-origin:center; transition: transform .18s ease;
    }
    .hnav-item:hover > .hnav-trigger,
    .hnav-item.open  > .hnav-trigger { background:rgba(255,255,255,.07); color:#fff; }

    .hnav-item:hover > .hnav-trigger::after,
    .hnav-item.open  > .hnav-trigger::after,
    .hnav-item.active > .hnav-trigger::after { transform: scaleX(1); }

    /* État actif : fond rouge de la charte + indicateur doré (pas seulement une couleur de texte). */
    .hnav-item.active > .hnav-trigger,
    .hnav-item.active:hover > .hnav-trigger {
        background: var(--eim-red);
        color: #fff;
    }
    .hnav-trigger:focus-visible { outline: 2px solid var(--eim-gold) !important; outline-offset: -2px; }

    .hnav-badge {
        display:inline-flex; align-items:center; justify-content:center;
        min-width:17px; height:17px; padding:0 5px; border-radius:20px;
        background: var(--school-urgent); color:#fff; font-size:10px; font-weight:800;
        line-height:1; margin-left:2px; box-shadow: 0 0 0 2px rgba(0,0,0,.15);
    }

    .hnav-drop {
        position      : absolute;
        top           : 46px;
        left          : 0;
        min-width     : 264px;
        max-height    : 78vh;
        overflow-y    : auto;
        background    : var(--school-drop-bg);
        border-radius : 0 0 12px 12px;
        box-shadow    : var(--school-drop-shadow);
        border        : 1px solid var(--school-drop-border);
        border-top    : 3px solid var(--school-gold);
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
    .hnav-item.hnav-urgent > .hnav-drop { border-top-color: var(--school-urgent); }

    .hnav-drop-title {
        padding:9px 16px 4px; font-size:10px; text-transform:uppercase;
        color:var(--eim-ink-soft); letter-spacing:.12em; font-weight:700; font-family:var(--school-ff);
        display:flex; align-items:center; gap:6px;
    }
    .hnav-drop-title:first-child { padding-top: 8px; }
    .hnav-drop-title i { font-size: 10px; color: var(--school-gold-dark); }

    .hnav-drop-item {
        display:flex; align-items:center; gap:10px; padding:10px 16px; font-size:13px; font-weight:400; color:var(--school-item-txt); text-decoration:none; transition: background .12s, padding-left .12s, border-color .12s; line-height:1.3; font-family:var(--school-ff); white-space:nowrap; border-left:2.5px solid transparent;
    }
    .hnav-drop-item:hover  { background:var(--school-item-hover); padding-left:20px; border-left-color: var(--school-gold); }
    .hnav-drop-item i      { font-size:14px; color:var(--school-item-icon); width:16px; flex-shrink:0; text-align:center; }
    .hnav-drop-item.urgent-item i { color: var(--school-urgent); }
    .hnav-drop-item.urgent-item:hover { border-left-color: var(--school-urgent); }
    .hnav-drop-item .hnav-mini-badge {
        margin-left:auto; font-size:10px; font-weight:800; color:#fff;
        background: var(--school-urgent); border-radius:20px; padding:1px 7px;
    }
    .hnav-new-tag {
        margin-left: auto;
        font-size: 9px;
        font-weight: 800;
        letter-spacing: .4px;
        text-transform: uppercase;
        color: var(--school-red-dark);
        background: linear-gradient(135deg, var(--school-gold-soft), var(--school-gold));
        padding: 2px 7px;
        border-radius: 20px;
        flex-shrink: 0;
    }
    .hnav-drop-item.urgent-item .hnav-new-tag { margin-left: 8px; }

    .hnav-drop-item.active {
        background: rgba(227, 30, 36, 0.06);
        border-left-color: var(--school-gold);
        color: var(--school-red-dark);
        font-weight: 600;
    }
    .hnav-drop-item.active i {
        color: var(--school-red-dark);
    }

    .hnav-drop-div { height:1px; background:var(--school-divider); margin:5px 0; }

    /* ===== Menus longs : sous-menus à deux niveaux ===== */
    .hnav-drop.hnav-drop-niveaux { overflow: visible; max-height: none; }
    .hnav-sub { position: relative; }
    .hnav-sub-trigger {
        display:flex; align-items:center; gap:10px; padding:11px 16px; font-size:13px; font-weight:600;
        color:var(--school-item-txt); text-decoration:none; line-height:1.3; font-family:var(--school-ff);
        white-space:nowrap; border-left:2.5px solid transparent; transition: background .12s, border-color .12s;
    }
    .hnav-sub-trigger > i:first-child { font-size:14px; color:var(--school-item-icon); width:16px; flex-shrink:0; text-align:center; }
    .hnav-sub-trigger > span { flex:1; }
    .hnav-sub-caret { font-size:10px; color:#a9a49a; transition: transform .16s ease; }
    .hnav-sub:hover > .hnav-sub-trigger,
    .hnav-sub.open  > .hnav-sub-trigger { background:var(--school-item-hover); border-left-color: var(--school-gold); color:var(--school-item-txt); }
    .hnav-sub.active > .hnav-sub-trigger { border-left-color: var(--school-gold); }
    .hnav-sub.active > .hnav-sub-trigger > span { color: var(--school-red); }
    .hnav-subdrop {
        position:absolute; top:-7px; left:100%; min-width:250px; max-height:78vh; overflow-y:auto;
        background:var(--school-drop-bg); border-radius:0 12px 12px 12px; box-shadow:var(--school-drop-shadow);
        border:1px solid var(--school-drop-border); border-top:3px solid var(--school-gold);
        padding:6px 0; z-index:100000;
        /* Fermé, le second niveau est retiré de la mise en page : laissé en place (même invisible),
           il dépassait à droite de l'écran et créait un défilement horizontal sur toutes les pages. */
        display:none;
    }
    .hnav-sub.open > .hnav-subdrop { display:block; animation: hnavSubOuvre .14s ease; }
    @keyframes hnavSubOuvre { from { opacity:0; transform:translateX(-6px); } to { opacity:1; transform:translateX(0); } }
    /* Pas assez de place à droite : le second niveau s'ouvre à gauche */
    .hnav-sub.vers-gauche > .hnav-subdrop { left:auto; right:100%; border-radius:12px 0 12px 12px; }

    .hnav-more { position:relative; display:flex; align-items:stretch; flex-shrink:0; margin-left:auto; }
    .hnav-more > .hnav-drop { left:auto; right:0; min-width:264px; }
    .hnav-more-title {
        padding:8px 16px 3px; font-size:10px; text-transform:uppercase;
        color:var(--eim-ink-soft); letter-spacing:.12em; font-weight:700; font-family:var(--school-ff);
        display:flex; align-items:center; gap:6px;
    }
    .hnav-more-title i { font-size:12px; color:var(--school-gold); }

    /* ===== HAMBURGER ===== */
    .hbtp-hamburger {
        display:none; flex-direction:column; gap:4px; justify-content:center;
        width:35px; height:35px; cursor:pointer; padding:6px;
        border-radius:var(--school-radius); background:rgba(255,255,255,.07);
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
        /* Sur mobile, les menus s'empilent et se déplient l'un sous l'autre */
        .hbtp-nav-items { flex-direction:column; width:100%; }
        .hnav-item     { flex-direction:column; height:auto; }
        .hnav-trigger  { padding:12px 16px; justify-content:space-between; height:auto; }
        .hnav-trigger::after { display:none; }
        .hnav-drop {
            position:static; box-shadow:none; border:none;
            border-top:2px solid rgba(255,255,255,.15); border-radius:0;
            background:rgba(0,0,0,.18); transform:none !important;
            display:none; opacity:1; visibility:visible; pointer-events:auto; max-height:none; min-width:0;
        }
        .hnav-item.open > .hnav-drop { display:block; }
        .hnav-drop-item       { color:rgba(255,255,255,.92); white-space:normal; border-left-color: transparent !important; }
        .hnav-drop-item:hover { background:rgba(0,0,0,.18); padding-left:20px; }
        .hnav-drop-item i     { color:rgba(255,255,255,.72); }
        .hnav-drop-title      { color:rgba(255,255,255,.5); }
        .hnav-drop-title i    { color: var(--school-gold-soft); }
        .hnav-drop-div        { background:rgba(255,255,255,.12); }
        .hnav-sub-trigger     { color:rgba(255,255,255,.92); white-space:normal; border-left-color: transparent !important; }
        .hnav-sub-trigger > i:first-child { color:rgba(255,255,255,.72); }
        .hnav-sub:hover > .hnav-sub-trigger,
        .hnav-sub.open  > .hnav-sub-trigger { background:rgba(0,0,0,.18); color:#fff; }
        .hnav-sub.active > .hnav-sub-trigger > span { color:#fff; }
        .hnav-sub.open > .hnav-sub-trigger .hnav-sub-caret { transform:rotate(90deg); }
        .hnav-subdrop {
            position:static; display:none; min-width:0; max-height:none; box-shadow:none; border:none; border-radius:0;
            background:rgba(0,0,0,.16); padding:0 0 4px 14px;
        }
        .hnav-sub.open > .hnav-subdrop { display:block; animation:none; }
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
            <div class="hbtp-brand-icon"><x-logo variant="sceau" :size="40" decoratif /></div>
            <div>
                <span class="hbtp-brand-title">EIM Compta</span>
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
            <button class="hbtp-icon-btn" id="btnSearch" data-search-toggle title="Recherche (Ctrl+K)">
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
                    <a href="{{ route('profil.show') }}" class="hbtp-udrop-item" role="menuitem">
                        <i class="fas fa-user-circle"></i> Mon profil
                    </a>
                    <a href="{{ route('profil.show') }}#password" class="hbtp-udrop-item" role="menuitem">
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

            {{-- ================================================================
                 MENUS 1 à 6 du cahier des charges (config/menu.php).
                 Chaque entrée n'apparaît que si le profil de l'utilisateur y a
                 droit (config/permissions.php) ; un groupe ou un menu sans
                 entrée visible n'est pas affiché.
                 ================================================================ --}}
            @foreach (config('menu', []) as $menu)
                @php
                    $groupesVisibles = collect($menu['groupes'])
                        ->map(fn ($entrees) => array_values(array_filter(
                            $entrees,
                            fn ($entree) => auth()->check() && auth()->user()->can($entree[3]) && \Illuminate\Support\Facades\Route::has($entree[2])
                        )))
                        ->filter(fn ($entrees) => $entrees !== []);

                    $menuActif = $groupesVisibles->flatten(1)->contains(
                        fn ($entree) => request()->routeIs(\Illuminate\Support\Str::beforeLast($entree[2], '.') . '.*')
                    );
                @endphp

                @continue($groupesVisibles->isEmpty())

                @php
                    // Au-delà de ce nombre d'écrans, le menu passe en sous-menus à deux niveaux
                    $menuLong = $groupesVisibles->count() > 1 && $groupesVisibles->flatten(1)->count() > 8;
                @endphp

                <div class="hnav-item {{ $menuActif ? 'active' : '' }}">
                    <a href="#" class="hnav-trigger" aria-haspopup="true" aria-expanded="false">
                        <i class="fas {{ $menu['icone'] }}"></i> {{ $menu['libelle'] }} <i class="fas fa-chevron-down caret"></i>
                    </a>
                    @if ($menuLong)
                        {{-- Menu long : chaque groupe est un sous-menu qui ouvre ses écrans à côté --}}
                        <div class="hnav-drop hnav-drop-niveaux" role="menu">
                            @foreach ($groupesVisibles as $titre => $entrees)
                                @php
                                    $groupeActif = collect($entrees)->contains(
                                        fn ($entree) => request()->routeIs(\Illuminate\Support\Str::beforeLast($entree[2], '.') . '.*')
                                    );
                                @endphp
                                <div class="hnav-sub {{ $groupeActif ? 'active' : '' }}">
                                    <a href="#" class="hnav-sub-trigger" role="menuitem" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas {{ $menu['icones'][$titre] ?? $entrees[0][1] }}"></i>
                                        <span>{{ $titre }}</span>
                                        <i class="fas fa-chevron-right hnav-sub-caret"></i>
                                    </a>
                                    <div class="hnav-subdrop" role="menu" aria-label="{{ $titre }}">
                                        @foreach ($entrees as [$libelle, $icone, $route])
                                            <a href="{{ route($route) }}" class="hnav-drop-item" role="menuitem">
                                                <i class="fas {{ $icone }}"></i> {{ $libelle }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="hnav-drop" role="menu">
                            @foreach ($groupesVisibles as $titre => $entrees)
                                @if (! $loop->first)
                                    <div class="hnav-drop-div"></div>
                                @endif
                                <div class="hnav-drop-title">{{ $titre }}</div>
                                @foreach ($entrees as [$libelle, $icone, $route])
                                    <a href="{{ route($route) }}" class="hnav-drop-item">
                                        <i class="fas {{ $icone }}"></i> {{ $libelle }}
                                    </a>
                                @endforeach
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach

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

        /* ===== SOUS-MENUS (menus longs) =====
           Bureau : le survol du groupe ouvre ses écrans à côté. Mobile et
           clavier : un clic (ou Entrée) déplie le groupe. */
        function fermerSousMenus(sauf) {
            document.querySelectorAll('.hnav-sub.open').forEach(function (el) {
                if (el === sauf) return;
                el.classList.remove('open');
                el.querySelector(':scope > .hnav-sub-trigger').setAttribute('aria-expanded', 'false');
            });
        }

        function ouvrirSousMenu(sub) {
            fermerSousMenus(sub);
            sub.classList.add('open');
            sub.querySelector(':scope > .hnav-sub-trigger').setAttribute('aria-expanded', 'true');
            if (isMobile()) return;
            // Bascule à gauche si le second niveau sortirait de l'écran
            sub.classList.remove('vers-gauche');
            var panneau = sub.querySelector(':scope > .hnav-subdrop');
            if (panneau.getBoundingClientRect().right > window.innerWidth - 8) {
                sub.classList.add('vers-gauche');
            }
        }

        function bindSousMenus() {
            document.querySelectorAll('.hnav-sub').forEach(function (sub) {
                if (sub._mariam) return;
                sub._mariam = true;
                var trigger = sub.querySelector(':scope > .hnav-sub-trigger');

                sub.addEventListener('mouseenter', function () {
                    if (!isMobile()) ouvrirSousMenu(sub);
                });
                sub.addEventListener('mouseleave', function () {
                    if (isMobile()) return;
                    sub.classList.remove('open');
                    trigger.setAttribute('aria-expanded', 'false');
                });
                trigger.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (sub.classList.contains('open') && isMobile()) {
                        sub.classList.remove('open');
                        trigger.setAttribute('aria-expanded', 'false');
                    } else {
                        ouvrirSousMenu(sub);
                    }
                });
            });
        }

        function bindAll() {
            document.querySelectorAll('#hbtp-nav-items > .hnav-item:not(.hnav-more)').forEach(bindItem);
            bindSousMenus();
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
            // Le survol ouvre déjà le menu : un clic qui suit ce survol ne doit
            // pas le refermer (sinon « survoler puis cliquer » le fait disparaître).
            var ouvertParSurvol = false;
            aWrap.addEventListener('mouseenter', function () {
                ouvertParSurvol = !aWrap.classList.contains('open');
                aWrap.classList.add('open');
                aBtn.setAttribute('aria-expanded', 'true');
            });
            aWrap.addEventListener('mouseleave', function () {
                ouvertParSurvol = false;
                aWrap.classList.remove('open');
                aBtn.setAttribute('aria-expanded', 'false');
            });
            aBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                if (ouvertParSurvol) {
                    ouvertParSurvol = false;
                    return;
                }
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

        /* =====  THÈME =====
           Applique la fois la classe .dark-theme (utilisée par ce fichier /
           d'éventuels styles locaux) ET l'attribut data-theme sur <html>
           (posé par le layout principal) : les deux mécanismes doivent
           rester synchronisés pour qu'un CSS sombre ciblant l'un ou l'autre
           fonctionne réellement.
        */
        var tBtn  = document.getElementById('themeToggle');
        var tIcon = document.getElementById('themeIcon');
        function applyTheme(dark) {
            document.documentElement.classList.toggle('dark-theme', dark);
            document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
            if (tIcon) tIcon.className = dark ? 'fas fa-sun' : 'fas fa-moon';
            try { localStorage.setItem('mariam-theme', dark ? 'dark' : 'light'); } catch(e){}
        }
        if (tBtn) {
            try { var sv = localStorage.getItem('mariam-theme'); if (sv) applyTheme(sv === 'dark'); } catch(e){}
            tBtn.addEventListener('click', function () {
                applyTheme(!document.documentElement.classList.contains('dark-theme'));
            });
        }

        /* Ctrl+K est géré uniquement dans _search.blade.php (seul
           propriétaire du raccourci, pour éviter une double ouverture de
           la modale) — rien à faire ici. */

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
                    var parentSub = el.closest('.hnav-sub');
                    if (parentSub) {
                        parentSub.classList.add('active');
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

    })();
</script>