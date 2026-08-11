{{-- resources/views/layouts/partials/_search-modal-mariam.blade.php --}}

<style>
    /* ===== SEARCH MODAL — École Mariam ===== */
    :root {
        --mariam-primary: #d21034;
        --mariam-primary-dark: #8b0d24;
        --mariam-accent: #e8a838;
        --mariam-accent-light: #f2c766;
        --mariam-ff: 'Kumbh Sans', sans-serif;
    }

    .search-modal {
        position: fixed;
        inset: 0;
        z-index: 10000;
        background: rgba(139, 13, 36, 0.85);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding-top: 8vh;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.25s ease, visibility 0.25s ease;
    }

    .search-modal.open {
        opacity: 1;
        visibility: visible;
    }

    .search-modal-content {
        background: #ffffff;
        border-radius: 16px;
        max-width: 700px;
        width: 92%;
        box-shadow: 0 30px 70px rgba(0,0,0,0.25);
        overflow: hidden;
        border: 1px solid rgba(255,255,255,0.15);
        transform: translateY(-20px) scale(0.96);
        transition: transform 0.3s ease, opacity 0.3s ease;
    }

    .search-modal.open .search-modal-content {
        transform: translateY(0) scale(1);
    }

    .search-modal-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        background: linear-gradient(120deg, var(--mariam-primary-dark), var(--mariam-primary));
        border-bottom: 2px solid var(--mariam-accent);
    }

    .search-modal-header i {
        font-size: 1.2rem;
        color: #fff;
    }

    .search-modal-input {
        flex: 1;
        border: none;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 8px;
        font-size: 1.1rem;
        font-weight: 400;
        color: #fff;
        font-family: var(--mariam-ff);
        outline: none;
        padding: 10px 16px;
        transition: background 0.3s ease;
    }

    .search-modal-input::placeholder {
        color: rgba(255, 255, 255, 0.6);
        font-weight: 300;
    }

    .search-modal-input:focus {
        background: rgba(255, 255, 255, 0.25);
    }

    .search-modal-close {
        background: rgba(255, 255, 255, 0.15);
        border: none;
        font-size: 1.2rem;
        color: #fff;
        cursor: pointer;
        padding: 8px 12px;
        border-radius: 8px;
        transition: all 0.2s ease;
        line-height: 1;
    }

    .search-modal-close:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: rotate(90deg);
    }

    .search-modal-close i {
        color: #fff;
    }

    /* --- Résultats --- */
    .search-modal-body {
        padding: 8px 0;
        max-height: 55vh;
        overflow-y: auto;
    }

    .search-result-item {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 12px 20px;
        cursor: pointer;
        transition: all 0.15s ease;
        border-bottom: 1px solid #f0f2f5;
        font-family: var(--mariam-ff);
        text-decoration: none;
        color: inherit;
    }

    .search-result-item:last-child {
        border-bottom: none;
    }

    .search-result-item:hover {
        background: #f5f7fa;
        padding-left: 26px;
        border-left: 4px solid var(--mariam-primary);
    }

    .search-result-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(120deg, var(--mariam-primary-dark), var(--mariam-primary));
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        color: #fff;
        flex-shrink: 0;
    }

    .search-result-info {
        flex: 1;
        min-width: 0;
    }

    .search-result-title {
        font-weight: 600;
        font-size: 0.95rem;
        color: #1a2c3e;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .search-result-sub {
        font-size: 0.8rem;
        color: #6f7e8c;
        margin-top: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .search-result-item .badge-category {
        background: var(--mariam-accent);
        color: #fff;
        font-size: 0.6rem;
        font-weight: 700;
        padding: 2px 10px;
        border-radius: 20px;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        flex-shrink: 0;
        margin-left: 8px;
    }

    .search-empty {
        padding: 40px 20px;
        text-align: center;
        color: #6f7e8c;
        font-family: var(--mariam-ff);
    }

    .search-empty i {
        font-size: 3rem;
        color: #dce4ea;
        margin-bottom: 12px;
        display: block;
    }

    /* Scrollbar */
    .search-modal-body::-webkit-scrollbar {
        width: 4px;
    }

    .search-modal-body::-webkit-scrollbar-track {
        background: #f0f2f5;
    }

    .search-modal-body::-webkit-scrollbar-thumb {
        background: var(--mariam-primary);
        border-radius: 4px;
    }

    /* Responsive */
    @media (max-width: 600px) {
        .search-modal { padding-top: 4vh; }
        .search-modal-content { width: 96%; }
        .search-modal-header { padding: 12px 16px; }
        .search-modal-input { font-size: 0.95rem; padding: 8px 12px; }
        .search-result-item { padding: 10px 14px; }
        .search-result-title { font-size: 0.85rem; }
        .search-result-sub { font-size: 0.7rem; }
        .search-result-icon { width: 32px; height: 32px; font-size: 0.9rem; }
    }
</style>

<div class="search-modal" id="searchModal" role="dialog" aria-modal="true" aria-label="Recherche">
    <div class="search-modal-content">
        <div class="search-modal-header">
            <i class="fas fa-search"></i>
            <input type="text" class="search-modal-input" id="searchInput"
                   placeholder="Rechercher un élève, enseignant, classe, facture..."
                   aria-label="Champ de recherche"
                   autocomplete="off">
            <button class="search-modal-close" id="searchModalClose" aria-label="Fermer la recherche">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="search-modal-body" id="searchResults">
            <!-- Résultats dynamiques via JavaScript -->
            <div class="search-empty">
                <i class="fas fa-search-plus"></i>
                <p>Commencez à taper pour rechercher...</p>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        'use strict';

        const modal = document.getElementById('searchModal');
        const closeBtn = document.getElementById('searchModalClose');
        const input = document.getElementById('searchInput');
        const resultsContainer = document.getElementById('searchResults');

        // ============================================
        // DONNÉES DE RECHERCHE (Exemple)
        // ============================================
        const searchData = [
            {
                id: 1,
                title: 'KOUADIO Jean',
                subtitle: 'Matricule EL-001 · Classe : CM1 · Responsable : KOUADIO Paul',
                icon: 'fa-user-graduate',
                category: 'Élève',
                url: '/eleves/1',
                type: 'eleve'
            },
            {
                id: 2,
                title: 'KONAN François',
                subtitle: 'Matière : Mathématiques · Classe : CM2 · Ancienneté : 5 ans',
                icon: 'fa-chalkboard-teacher',
                category: 'Enseignant',
                url: '/enseignants/1',
                type: 'enseignant'
            },
            {
                id: 3,
                title: 'Classe CM1',
                subtitle: 'Effectif : 28 élèves · Enseignant titulaire : M. BAMBA',
                icon: 'fa-door-open',
                category: 'Classe',
                url: '/classes/1',
                type: 'classe'
            },
            {
                id: 4,
                title: 'Les Misérables – Victor Hugo',
                subtitle: 'Exemplaire n° EX-001 · Disponible · Rayon : A-1',
                icon: 'fa-book',
                category: 'Livre',
                url: '/bibliotheque/1',
                type: 'livre'
            },
            {
                id: 5,
                title: 'Facture n° FAC-2026-001',
                subtitle: 'Élève : KOUADIO Jean · Montant : 150 000 FCFA · Statut : En attente',
                icon: 'fa-file-invoice-dollar',
                category: 'Facture',
                url: '/factures/1',
                type: 'facture'
            },
            {
                id: 6,
                title: 'Paiement REC-001',
                subtitle: 'Reçu le 19/07/2026 · Montant : 50 000 FCFA · Mode : Espèces',
                icon: 'fa-credit-card',
                category: 'Paiement',
                url: '/paiements/1',
                type: 'paiement'
            },
            {
                id: 7,
                title: 'BAMBA Mamadou',
                subtitle: 'Directeur · Ancienneté : 10 ans',
                icon: 'fa-user-tie',
                category: 'Direction',
                url: '/direction/1',
                type: 'direction'
            },
            {
                id: 8,
                title: 'Cantine Scolaire',
                subtitle: 'Menu du jour : Riz gras · 250 élèves inscrits',
                icon: 'fa-utensils',
                category: 'Cantine',
                url: '/cantine/1',
                type: 'cantine'
            }
        ];

        // ============================================
        // FONCTIONS DE RECHERCHE
        // ============================================

        function search(query) {
            if (!query || query.trim() === '') {
                return [];
            }

            const q = query.toLowerCase().trim();
            return searchData.filter(item => {
                return item.title.toLowerCase().includes(q) ||
                    item.subtitle.toLowerCase().includes(q) ||
                    item.category.toLowerCase().includes(q);
            });
        }

        function renderResults(results) {
            if (results.length === 0) {
                resultsContainer.innerHTML = `
                    <div class="search-empty">
                        <i class="fas fa-search-minus"></i>
                        <p>Aucun résultat trouvé.</p>
                        <small style="color: #a8b6c2;">Essayez avec d'autres mots-clés</small>
                    </div>
                `;
                return;
            }

            let html = '';
            results.forEach(item => {
                html += `
                    <a href="${item.url}" class="search-result-item" tabindex="0">
                        <div class="search-result-icon"><i class="fas ${item.icon}"></i></div>
                        <div class="search-result-info">
                            <div class="search-result-title">
                                ${item.title}
                                <span class="badge-category">${item.category}</span>
                            </div>
                            <div class="search-result-sub">${item.subtitle}</div>
                        </div>
                    </a>
                `;
            });

            resultsContainer.innerHTML = html;
        }

        // ============================================
        // GESTION DES ÉVÉNEMENTS
        // ============================================

        // Recherche en temps réel
        let searchTimeout;
        input.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const results = search(this.value);
                renderResults(results);
            }, 300);
        });

        // Ouvrir avec Ctrl+K
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                openModal();
            }
            if (e.key === 'Escape' && modal.classList.contains('open')) {
                closeModal();
            }
        });

        // Fermer avec le bouton
        closeBtn.addEventListener('click', closeModal);

        // Cliquer à l'extérieur pour fermer
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        // ============================================
        // FONCTIONS D'OUVERTURE/FERMETURE
        // ============================================

        function openModal() {
            modal.classList.add('open');
            setTimeout(() => {
                input.focus();
                input.select();
            }, 100);
            document.body.style.overflow = 'hidden';

            // Réinitialiser les résultats
            resultsContainer.innerHTML = `
                <div class="search-empty">
                    <i class="fas fa-search-plus"></i>
                    <p>Commencez à taper pour rechercher...</p>
                </div>
            `;
            input.value = '';
        }

        function closeModal() {
            modal.classList.remove('open');
            document.body.style.overflow = '';
            input.blur();
        }

        // ============================================
        // EXPOSITION DES FONCTIONS GLOBALES
        // ============================================

        window.openSearchModal = openModal;
        window.closeSearchModal = closeModal;

        // ============================================
        // LIEN AVEC LE BOUTON DE RECHERCHE DU HEADER
        // ============================================

        document.addEventListener('DOMContentLoaded', function() {
            const searchBtn = document.querySelector('[data-search-toggle]');
            if (searchBtn) {
                searchBtn.addEventListener('click', openModal);
            }
        });

        console.log('🔍 Modale de recherche Mariam prête.');
    })();
</script>
