// public/pages/annee.js

(function() {
    'use strict';

    const config = window.anneesConfig || {};
    const annees = config.annees || [];
    const routes = config.routes || {};

    let currentEditId = null;

    // ============================================
    // INITIALISATION
    // ============================================

    $(document).ready(function() {
        renderTable(annees);
        updateCount(annees.length);
        bindEvents();
    });

    // ============================================
    // RENDU DU TABLEAU
    // ============================================

    function renderTable(data) {
        const tbody = $('#anneesTbody');
        if (!data || data.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="fas fa-calendar-alt"></i>
                            Aucune année scolaire trouvée.
                            <br>
                            <small>Cliquez sur "Nouvelle année" pour en créer une.</small>
                        </div>
                    </td>
                </tr>
            `);
            return;
        }

        let html = '';
        data.forEach(function(item) {
            const activeBadge = item.etat === 1
                ? '<span class="badge badge-active">Active</span>'
                : '<span class="badge badge-inactive">Inactive</span>';

            const statutBadge = getStatutBadge(item.statut_annee);

            html += `
                <tr data-id="${item.id}">
                    <td><strong>${escapeHtml(item.libelle || '-')}</strong></td>
                    <td>${formatDate(item.date_rentree)}</td>
                    <td>${formatDate(item.date_fin)}</td>
                    <td>${formatDate(item.date_ouverture_inscription)}</td>
                    <td>${formatDate(item.date_fermeture_reinscription)}</td>
                    <td>${activeBadge}</td>
                    <td>${statutBadge}</td>
                    <td class="text-center">
                        <div class="dropdown">
                            <button class="btn action-dropdown-btn" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <button class="dropdown-item btn-detail" data-id="${item.id}">
                                        <i class="fas fa-eye"></i> Détail
                                    </button>
                                </li>
                                <li>
                                    <button class="dropdown-item btn-edit" data-id="${item.id}">
                                        <i class="fas fa-edit"></i> Modifier
                                    </button>
                                </li>
                                <li>
                                    <button class="dropdown-item btn-toggle-active" data-id="${item.id}">
                                        <i class="fas ${item.etat === 1 ? 'fa-pause' : 'fa-play'}"></i>
                                        ${item.etat === 1 ? 'Désactiver' : 'Activer'}
                                    </button>
                                </li>
                                <li>
                                    <button class="dropdown-item btn-set-open" data-id="${item.id}">
                                        <i class="fas fa-check-circle"></i> Ouvrir
                                    </button>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <button class="dropdown-item text-danger btn-delete" data-id="${item.id}">
                                        <i class="fas fa-trash-alt"></i> Supprimer
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            `;
        });

        tbody.html(html);
    }

    // ============================================
    // FONCTIONS UTILITAIRES
    // ============================================

    function getStatutBadge(statut) {
        const map = {
            1: { label: 'Non ouvert', class: 'badge-warning' },
            2: { label: 'Ouvert', class: 'badge-success' },
            3: { label: 'Clôturé', class: 'badge-danger' }
        };
        const s = map[statut] || { label: 'Inconnu', class: 'badge-secondary' };
        return `<span class="badge ${s.class}">${s.label}</span>`;
    }

    function formatDate(date) {
        if (!date) return '-';
        const d = new Date(date);
        return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function updateCount(count) {
        $('#anneeCount').text(count + ' année' + (count > 1 ? 's' : ''));
    }

    function showToast(message, type) {
        const toast = $('#toastBtp');
        toast.removeClass('show toast-danger toast-warning');
        toast.html('<span>' + escapeHtml(message) + '</span>');
        if (type) toast.addClass('toast-' + type);
        toast.addClass('show');
        setTimeout(() => toast.removeClass('show'), 3500);
    }

    function showConfirm(title, text, confirmText, callback) {
        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: confirmText || 'Oui, supprimer',
            cancelButtonText: 'Annuler',
            customClass: {
                popup: 'swal-btp-popup',
                title: 'swal-btp-title',
                confirmButton: 'swal-btp-confirm',
                cancelButton: 'swal-btp-cancel'
            }
        }).then((result) => {
            if (result.isConfirmed) callback();
        });
    }

    // ============================================
    // GESTION DES FILTRES
    // ============================================

    function filterData() {
        const search = $('#searchInput').val().toLowerCase();
        const active = $('#filterActive').val();

        let filtered = annees;

        if (active !== '') {
            filtered = filtered.filter(item => item.etat === parseInt(active));
        }

        if (search) {
            filtered = filtered.filter(item => {
                const libelle = (item.libelle || '').toLowerCase();
                return libelle.includes(search);
            });
        }

        renderTable(filtered);
        updateCount(filtered.length);
    }

    // ============================================
    // EVENTS
    // ============================================

    function bindEvents() {
        // Filtres
        $('#btnFiltrer').on('click', filterData);
        $('#searchInput').on('keyup', function(e) {
            if (e.key === 'Enter') filterData();
        });
        $('#filterActive').on('change', filterData);

        // Nouveau
        $('#btnNouveau').on('click', function() {
            resetForm();
            currentEditId = null;
            $('#modalTitle').html('<i class="fas fa-calendar-plus me-2"></i>Nouvelle année scolaire');
        });

        // Sauvegarde
        $('#saveAnneeBtn').on('click', saveAnnee);

        // Détail
        $(document).on('click', '.btn-detail', function() {
            const id = $(this).data('id');
            showDetail(id);
        });

        // Édition
        $(document).on('click', '.btn-edit', function() {
            const id = $(this).data('id');
            loadForEdit(id);
        });

        // Activer/Désactiver
        $(document).on('click', '.btn-toggle-active', function() {
            const id = $(this).data('id');
            toggleActive(id);
        });

        // Ouvrir
        $(document).on('click', '.btn-set-open', function() {
            const id = $(this).data('id');
            setOpen(id);
        });

        // Supprimer
        $(document).on('click', '.btn-delete', function() {
            const id = $(this).data('id');
            deleteAnnee(id);
        });

        // Réinitialisation du modal à la fermeture
        $('#anneeModal').on('hidden.bs.modal', function() {
            resetForm();
        });
    }

    // ============================================
    // CRUD OPERATIONS
    // ============================================

    function saveAnnee() {
        const data = getFormData();
        const id = currentEditId;
        const isEdit = !!id;

        if (!validateForm(data)) return;

        const url = isEdit ? routes.update.replace(':id', id) : routes.store;
        const method = isEdit ? 'PUT' : 'POST';

        $('#saveAnneeBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enregistrement...');

        $.ajax({
            url: url,
            method: method,
            data: data,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    $('#anneeModal').modal('hide');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(response.message || 'Erreur lors de l\'enregistrement.', 'danger');
                }
            },
            error: function(xhr) {
                let msg = 'Erreur lors de l\'enregistrement.';
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    msg = errors.join(' ');
                } else if (xhr.responseJSON?.message) {
                    msg = xhr.responseJSON.message;
                }
                showToast(msg, 'danger');
            },
            complete: function() {
                $('#saveAnneeBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Enregistrer');
            }
        });
    }

    function deleteAnnee(id) {
        const item = annees.find(a => a.id === id);
        if (!item) return;

        showConfirm(
            'Supprimer cette année ?',
            `Voulez-vous vraiment supprimer l'année "${escapeHtml(item.libelle)}" ? Cette action est irréversible.`,
            'Oui, supprimer',
            function() {
                $.ajax({
                    url: routes.destroy.replace(':id', id),
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            showToast(response.message, 'success');
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            showToast(response.message || 'Erreur lors de la suppression.', 'danger');
                        }
                    },
                    error: function() {
                        showToast('Erreur lors de la suppression.', 'danger');
                    }
                });
            }
        );
    }

    function toggleActive(id) {
        const item = annees.find(a => a.id === id);
        if (!item) return;

        const action = item.etat === 1 ? 'désactiver' : 'activer';
        showConfirm(
            `${action.charAt(0).toUpperCase() + action.slice(1)} l'année ?`,
            `Voulez-vous ${action} l'année "${escapeHtml(item.libelle)}" ?`,
            `Oui, ${action}`,
            function() {
                $.ajax({
                    url: routes.toggleActive.replace(':id', id),
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            showToast(response.message, 'success');
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            showToast(response.message || 'Erreur.', 'danger');
                        }
                    },
                    error: function() {
                        showToast('Erreur lors de l\'opération.', 'danger');
                    }
                });
            }
        );
    }

    function setOpen(id) {
        const item = annees.find(a => a.id === id);
        if (!item) return;

        showConfirm(
            'Ouvrir cette année ?',
            `Voulez-vous définir "${escapeHtml(item.libelle)}" comme année en cours ? Les autres années seront fermées.`,
            'Oui, ouvrir',
            function() {
                $.ajax({
                    url: routes.setActive.replace(':id', id),
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            showToast(response.message, 'success');
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            showToast(response.message || 'Erreur.', 'danger');
                        }
                    },
                    error: function() {
                        showToast('Erreur lors de l\'opération.', 'danger');
                    }
                });
            }
        );
    }

    function showDetail(id) {
        const item = annees.find(a => a.id === id);
        if (!item) return;

        $.ajax({
            url: routes.show.replace(':id', id),
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    $('#detailModalTitle').html(`<i class="fas fa-circle-info me-2"></i>Détail - ${escapeHtml(data.libelle)}`);
                    $('#detailModalBody').html(`
                        <div class="row g-3">
                            <div class="col-12">
                                <p><strong>Libellé :</strong> ${escapeHtml(data.libelle || '-')}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Date rentrée :</strong> ${formatDate(data.date_rentree)}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Date fin :</strong> ${formatDate(data.date_fin)}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Ouverture inscriptions :</strong> ${formatDate(data.date_ouverture_inscription) || '-'}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Fermeture réinscriptions :</strong> ${formatDate(data.date_fermeture_reinscription) || '-'}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Statut :</strong> ${getStatutBadge(data.statut_annee)}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Active :</strong> ${data.etat === 1 ? '<span class="badge badge-active">Active</span>' : '<span class="badge badge-inactive">Inactive</span>'}</p>
                            </div>
                            <div class="col-12">
                                <p><strong>Créé le :</strong> ${formatDate(data.created_at)}</p>
                                <p><strong>Modifié le :</strong> ${formatDate(data.updated_at)}</p>
                            </div>
                        </div>
                    `);
                    $('#detailModal').modal('show');
                }
            },
            error: function() {
                showToast('Erreur lors du chargement des détails.', 'danger');
            }
        });
    }

    function loadForEdit(id) {
        const item = annees.find(a => a.id === id);
        if (!item) return;

        currentEditId = id;
        $('#modalTitle').html(`<i class="fas fa-edit me-2"></i>Modifier - ${escapeHtml(item.libelle)}`);

        $('#anneeId').val(item.id);
        $('#libelle').val(item.libelle || '');
        $('#date_rentree').val(item.date_rentree || '');
        $('#date_fin').val(item.date_fin || '');
        $('#date_ouverture_inscription').val(item.date_ouverture_inscription || '');
        $('#date_fermeture_reinscription').val(item.date_fermeture_reinscription || '');
        $('#est_active').prop('checked', item.etat === 1);
        $('#est_cloturee').prop('checked', item.statut_annee === 3);

        $('#anneeModal').modal('show');
    }

    // ============================================
    // FORMULAIRES
    // ============================================

    function resetForm() {
        $('#anneeForm')[0].reset();
        $('#anneeId').val('');
        currentEditId = null;
        $('.is-invalid').removeClass('is-invalid');
        // Réinitialiser les valeurs par défaut
        $('#est_active').prop('checked', true);
        $('#est_cloturee').prop('checked', false);
    }

    function getFormData() {
        const data = {
            libelle: $('#libelle').val().trim(),
            date_rentree: $('#date_rentree').val(),
            date_fin: $('#date_fin').val(),
            date_ouverture_inscription: $('#date_ouverture_inscription').val(),
            date_fermeture_reinscription: $('#date_fermeture_reinscription').val(),
            etat: $('#est_active').is(':checked') ? 1 : 0,
            statut_annee: $('#est_cloturee').is(':checked') ? 3 : 1,
        };

        if (currentEditId) {
            data.id = currentEditId;
        }

        return data;
    }

    function validateForm(data) {
        $('.is-invalid').removeClass('is-invalid');
        let isValid = true;

        if (!data.libelle) {
            $('#libelle').addClass('is-invalid');
            isValid = false;
        }
        if (!data.date_rentree) {
            $('#date_rentree').addClass('is-invalid');
            isValid = false;
        }
        if (!data.date_fin) {
            $('#date_fin').addClass('is-invalid');
            isValid = false;
        }
        if (data.date_rentree && data.date_fin && data.date_rentree >= data.date_fin) {
            $('#date_fin').addClass('is-invalid');
            showToast('La date de fin doit être après la date de début.', 'warning');
            isValid = false;
        }
        // Vérification des dates d'inscription
        if (data.date_ouverture_inscription && data.date_fermeture_reinscription &&
            data.date_fermeture_reinscription < data.date_ouverture_inscription) {
            $('#date_fermeture_reinscription').addClass('is-invalid');
            showToast('La date de fermeture des réinscriptions doit être après la date d\'ouverture.', 'warning');
            isValid = false;
        }

        if (!isValid) {
            showToast('Veuillez remplir tous les champs obligatoires.', 'warning');
        }

        return isValid;
    }
})();
