/**
 * Layout JavaScript — École Internationale Mariam
 * Gestion du menu, notifications, et interactions
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        initMobileMenu();
        initSubmenuToggle();
        initFullscreen();
        initYearChange();
        initNotifications();
        initScrollEffects();
    });

    /* ── Menu mobile drawer ── */
    function initMobileMenu() {
        const $hamburger = $('#eim-hamburger');
        const $navList = $('.eim-nav-list');
        const $overlay = $('#eim-mobile-overlay');

        function toggleMenu() {
            const isOpen = $navList.hasClass('open');
            $navList.toggleClass('open', !isOpen);
            $overlay.toggleClass('show', !isOpen);
            $hamburger.toggleClass('open', !isOpen);
            $hamburger.attr('aria-expanded', !isOpen);
        }

        $hamburger.on('click', toggleMenu);
        $overlay.on('click', toggleMenu);

        // Fermer au clic sur un lien
        $navList.on('click', '.eim-nav-link', function () {
            if (window.innerWidth <= 768) {
                setTimeout(toggleMenu, 200);
            }
        });

        // Fermer avec Échap
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' && $navList.hasClass('open')) {
                toggleMenu();
            }
        });
    }

    /* ── Toggle sous-menus mobiles ── */
    function initSubmenuToggle() {
        $(document).on('click', '.eim-nav-item.has-submenu > .eim-nav-link', function (e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                const $item = $(this).parent();
                const wasOpen = $item.hasClass('open');
                
                // Fermer les autres
                $('.eim-nav-item.has-submenu').removeClass('open');
                
                if (!wasOpen) {
                    $item.addClass('open');
                }
            }
        });
    }

    /* ── Plein écran ── */
    function initFullscreen() {
        $('.dz-fullscreen').on('click', function () {
            toggleFullscreen();
        });

        document.addEventListener('fullscreenchange', updateFullscreenIcon);
        document.addEventListener('webkitfullscreenchange', updateFullscreenIcon);
    }

    function toggleFullscreen() {
        const el = document.documentElement;
        if (!document.fullscreenElement && !document.webkitFullscreenElement) {
            if (el.requestFullscreen) el.requestFullscreen();
            else if (el.webkitRequestFullscreen) el.webkitRequestFullscreen();
        } else {
            if (document.exitFullscreen) document.exitFullscreen();
            else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
        }
    }

    function updateFullscreenIcon() {
        const isFull = !!(document.fullscreenElement || document.webkitFullscreenElement);
        $('#icon-full').toggle(!isFull);
        $('#icon-minimize').toggle(isFull);
    }

    /* ── Changement d'année ── */
    function initYearChange() {
        $(document).on('change', '#change_annee', function () {
            const anneeId = $(this).val();
            if (!anneeId) return;

            $.ajax({
                type: 'POST',
                url: '/change/annee/session',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: { annee_id: anneeId },
                success: function (data) {
                    if (data?.success) {
                        showToast('success', 'Année scolaire mise à jour');
                        setTimeout(() => location.reload(), 800);
                    }
                },
                error: function (xhr) {
                    console.error('Erreur changement année:', xhr);
                    showToast('error', 'Erreur lors du changement d\'année');
                }
            });
        });
    }

    /* ── Notifications (simulation) ── */
    function initNotifications() {
        // Mettre à jour le badge si nécessaire
        // updateNotifBadge(3);
    }

    window.updateNotifBadge = function (count) {
        const $badge = $('#notif-count');
        if (count > 0) {
            $badge.text(count > 99 ? '99+' : count).show();
        } else {
            $badge.hide();
        }
    };

    /* ── Effets au scroll ── */
    function initScrollEffects() {
        let lastScroll = 0;
        const $topbar = $('.eim-topbar');
        const $navbar = $('.eim-navbar');

        $(window).on('scroll', function () {
            const currentScroll = $(this).scrollTop();
            
            if (currentScroll > 50) {
                $topbar.css('box-shadow', '0 4px 20px rgba(0,0,0,0.25)');
            } else {
                $topbar.css('box-shadow', 'var(--shadow-lg)');
            }

            lastScroll = currentScroll;
        });
    }

    /* ── Toast notifications ── */
    window.showToast = function (type, message) {
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        
        const colors = {
            success: '#16a34a',
            error: '#dc2626',
            warning: '#d97706',
            info: '#0ea5e9'
        };

        const toast = $(`
            <div class="eim-toast" style="background: ${colors[type] || colors.info}">
                <i class="fas ${icons[type] || icons.info}"></i>
                <span>${message}</span>
            </div>
        `);

        $('#toast-container').append(toast);
        
        setTimeout(() => {
            toast.fadeOut(300, function () {
                $(this).remove();
            });
        }, 4000);
    };

}(jQuery));

/* Styles pour les toasts */
$('<style>').text(`
    #toast-container {
        position: fixed;
        top: 90px;
        right: 24px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .eim-toast {
        padding: 14px 20px;
        border-radius: 10px;
        color: white;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        animation: slideIn 0.3s ease;
        min-width: 300px;
    }
    .eim-toast i {
        font-size: 1.2rem;
    }
    @keyframes slideIn {
        from { transform: translateX(120%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
`).appendTo('head');