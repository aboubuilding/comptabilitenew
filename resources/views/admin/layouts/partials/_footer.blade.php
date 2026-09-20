{{-- resources/views/admin/layouts/partials/_footer.blade.php --}}

<style>
    /* ===== FOOTER MARIAM =====
       Les jetons de couleur (--school-red, --school-gold, --school-ff...)
       sont déjà déclarés dans layouts/app.blade.php : aucun ajout local
       ici, donc pas de :root redéclaré dans ce fichier — évite un point
       de divergence si les valeurs changent un jour. */

    .mariam-footer {
        font-family: var(--school-ff);
        background: linear-gradient(120deg, var(--school-red-dark), var(--school-red));
        color: rgba(255, 255, 255, 0.85);
        border-top: 3px solid var(--school-gold);
        padding: 20px 24px;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        font-size: 0.85rem;
        margin-top: auto;
        width: 100%;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
        animation: mariam-footer-in 0.5s ease-out both;
    }

    @keyframes mariam-footer-in {
        from { opacity: 0; transform: translateY(12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @media (prefers-reduced-motion: reduce) {
        .mariam-footer { animation: none; }
    }

    .mariam-footer .footer-left {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .mariam-footer .footer-left strong {
        color: #fff;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    .mariam-footer .footer-left small {
        font-size: 0.7rem;
        opacity: 0.75;
        font-weight: 300;
    }

    .mariam-footer .footer-right {
        display: flex;
        align-items: center;
        gap: 18px;
        flex-wrap: wrap;
    }

    .mariam-footer .footer-right a {
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        transition: color 0.2s ease, transform 0.2s ease;
        border-bottom: 1px solid transparent;
        padding-bottom: 2px;
        position: relative;
        font-weight: 500;
    }

    .mariam-footer .footer-right a::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        width: 0;
        height: 2px;
        background: var(--school-gold);
        transition: width 0.3s ease;
    }

    .mariam-footer .footer-right a:hover {
        color: var(--school-gold-soft);
        transform: translateY(-1px);
    }

    .mariam-footer .footer-right a:hover::after {
        width: 100%;
    }

    .mariam-footer .footer-right a:hover i {
        transform: scale(1.1);
    }
    .mariam-footer .footer-right a i {
        transition: transform 0.2s ease;
        display: inline-block;
    }

    .mariam-footer .footer-right span {
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.75rem;
        background: rgba(255, 255, 255, 0.08);
        padding: 4px 14px;
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.05);
        font-weight: 500;
        letter-spacing: 0.5px;
    }

    .mariam-footer .footer-right .separator {
        color: rgba(255, 255, 255, 0.2);
        font-size: 0.7rem;
    }

    @media (max-width: 600px) {
        .mariam-footer {
            flex-direction: column;
            text-align: center;
            padding: 16px 20px;
        }

        .mariam-footer .footer-left {
            align-items: center;
        }

        .mariam-footer .footer-right {
            justify-content: center;
            gap: 12px;
        }

        .mariam-footer .footer-right .separator {
            display: none;
        }
    }

    @media (max-width: 400px) {
        .mariam-footer .footer-right {
            flex-direction: column;
            gap: 8px;
        }
    }
</style>

<footer class="mariam-footer" id="app-footer">
    <div class="footer-left">
        &copy; {{ date('Y') }} — <strong>École Internationale Mariam</strong>
        <small>Plateforme de gestion scolaire et budgétaire</small>
    </div>
    <div class="footer-right">
        <a href="{{ route('tableau') }}" title="Accueil">
            <i class="fas fa-home"></i>
        </a>
        <span class="separator">|</span>
        <a href="#" title="Documentation">
            <i class="fas fa-book"></i> Documentation
        </a>
        <span class="separator">|</span>
        <a href="#" title="Support">
            <i class="fas fa-headset"></i> Support
        </a>
        <span class="separator">|</span>
        <a href="#" title="Mentions légales">
            <i class="fas fa-gavel"></i> Mentions
        </a>
        <span class="separator">|</span>
        {{-- Depuis config('app.version') plutôt qu'en dur : changer de
             version se fait dans .env/config, pas en rouvrant cette vue. --}}
        <span title="Version">
            <i class="fas fa-code-branch"></i> v{{ config('app.version', '1.0.0') }}
        </span>
    </div>
</footer>