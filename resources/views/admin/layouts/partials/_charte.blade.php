{{--
    Charte « EIM Compta » : jetons de couleur, rayons et police, partagés par
    le back-office (layout app) et l'écran de connexion. Les --school-* restent
    des alias pour les vues existantes. Les PDF (DomPDF, sans var()) et les
    graphiques Chart.js reprennent ces valeurs en hexadécimal.
--}}
<style>
    :root {
        /* Charte « EIM Compta » */
        --eim-red: #E31E24;
        --eim-red-hover: #B3171C;
        --eim-red-deep: #8E1216;
        --eim-gold: #D4A94D;          /* accent seulement, jamais en texte sur fond clair */
        --eim-gold-soft: #EAD3A0;
        --eim-gold-deep: #8A6516;     /* or assombri lisible en texte (≥ 4.5:1 sur blanc) */
        --eim-ink: #2B1E1E;
        --eim-ink-soft: #5A4A4A;
        --eim-bg: #FBF7EF;
        --eim-surface: #FFFFFF;
        --eim-cream: #FBF4E6;
        --eim-hover: #FDF9F2;
        --eim-border: #EADFCB;
        --eim-success: #1F7A4D;
        --eim-danger: #B3171C;
        --eim-radius-sm: 8px;
        --eim-radius: 12px;
        --eim-radius-lg: 20px;
        --eim-radius-pill: 999px;
        --eim-ff: 'Manrope', system-ui, -apple-system, 'Segoe UI', sans-serif;
        --eim-focus: 0 0 0 2px var(--eim-surface), 0 0 0 4px var(--eim-red);

        /* Alias historiques : les vues existantes continuent de fonctionner */
        --school-red: var(--eim-red);
        --school-red-dark: var(--eim-red-hover);
        --school-red-deep: var(--eim-red-deep);
        --school-gold: var(--eim-gold);
        --school-gold-soft: var(--eim-gold-soft);
        --school-gold-dark: var(--eim-gold-deep);
        --school-ink: var(--eim-ink);
        --school-ink-deep: #1E1414;
        --school-muted: var(--eim-ink-soft);
        --school-urgent: #ff7a1a;
        --school-urgent-dark: #cc5500;
        --school-paper: var(--eim-cream);
        --school-paper-line: var(--eim-border);
        --school-page-bg: var(--eim-bg);
        --school-ff: var(--eim-ff);
        --school-ff-display: var(--eim-ff);

        --bs-body-font-family: var(--eim-ff);
        --bs-body-color: var(--eim-ink);
        --bs-primary: var(--eim-red);
        --bs-primary-rgb: 227, 30, 36;
        --bs-link-color: var(--eim-red-hover);
        --bs-link-hover-color: var(--eim-red-deep);
        --bs-border-color: var(--eim-border);
        --bs-border-radius: var(--eim-radius-sm);
    }
</style>
