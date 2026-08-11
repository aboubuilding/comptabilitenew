// public/pages/niveau.js

(function() {
    'use strict';

    const config = window.niveauxConfig || {};
    const niveaux = config.niveaux || [];
    const cycles = config.cycles || [];
    const routes = config.routes || {};

    let currentEditId = null;

    // Mapping des cycles pour un accès rapide
    const cycleMap = {};
    cycles.forEach(function(cycle) {
        cycleMap[cycle.id] = cycle.libelle;
    });

    // ============================================
    // INITIALISATION
    // ============================================

    $(document).ready(function() {
        renderTable(niveaux);
        updateCount(niveaux.length);
        bindEvents();
    });

    // ============================================
    // RENDU DU TABLEAU
    // ============================================

    function renderTable(data) {
        const tbody = $('#niveauxTbody');
        if (!data || data.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="fas fa-flag"></i>
                            Aucun niveau trouvé.
                            <br>
                            <small>Cliquez sur "Nouveau niveau" pour en créer un.</small>
                        </div>
                    </td>
                </tr>
            `);
            return;
        }

        let html = '';
        data.forEach(function(item) {
            const cycleLibelle = item.cycle_libelle || cycleMap[item.cycle_id] || '-';
            const activeBadge = item.etat === 1
                ? '<span class="badge badge-success">Actif</span>'
                : '<span class="badge badge-danger">Inactif</span>';

            html += `
                <tr data-id="${item.id}">
                    <td><strong>${escapeHtml(item.libelle || '-')}</strong></td>
                    <td>${escapeHtml(cycleLibelle)}</td>
                    <td class="text-center">${item.numero_ordre ?? '-'}</td>
                    <td>${escapeHtml(item.description ? item.description.substring(0, 60) + (item.description.length > 60 ? '...' : '') : '-')}</td>
                    <td>${activeBadge}</td>
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
        $('#niveauCount').text(count + ' niveau' + (count > 1 ? 'x' : ''));
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
        const cycleId = $('#filterCycle').val();
        const etat = $('#filterActive').val();

        let filtered = niveaux;

        if (cycleId !== '') {
            filtered = filtered.filter(item => item.cycle_id === parseInt(cycleId));
        }

        if (etat !== '') {
            filtered = filtered.filter(item => item.etat === parseInt(etat));
        }

        if (search) {
            filtered = filtered.filter(item => {
                const libelle = (item.libelle || '').toLowerCase();
                const description = (item.description || '').toLowerCase();
                return libelle.includes(search) || description.includes(search);
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
        $('#filterCycle, #filterActive').on('change', filterData);

        // Nouveau
        $('#btnNouveau').on('click', function() {
            resetForm();
            currentEditId = null;
            $('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Nouveau niveau');
        });

        // Sauvegarde
        $('#saveNiveauBtn').on('click', saveNiveau);

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

        // Supprimer
        $(document).on('click', '.btn-delete', function() {
            const id = $(this).data('id');
            deleteNiveau(id);
        });

        // Réinitialisation du modal à la fermeture
        $('#niveauModal').on('hidden.bs.modal', function() {
            resetForm();
        });
    }

    // ============================================
    // CRUD OPERATIONS
    // ============================================

    function saveNiveau() {
        const data = getFormData();
        const id = currentEditId;
        const isEdit = !!id;

        if (!validateForm(data)) return;

        const url = isEdit ? routes.update.replace(':id', id) : routes.store;
        const method = isEdit ? 'PUT' : 'POST';

        $('#saveNiveauBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enregistrement...');

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
                    $('#niveauModal').modal('hide');
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
                $('#saveNiveauBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Enregistrer');
            }
        });
    }

    function deleteNiveau(id) {
        const item = niveaux.find(a => a.id === id);
        if (!item) return;

        showConfirm(
            'Supprimer ce niveau ?',
            `Voulez-vous vraiment supprimer le niveau "${escapeHtml(item.libelle)}" ? Cette action est irréversible.`,
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
        const item = niveaux.find(a => a.id === id);
        if (!item) return;

        const action = item.etat === 1 ? 'désactiver' : 'activer';
        showConfirm(
            `${action.charAt(0).toUpperCase() + action.slice(1)} le niveau ?`,
            `Voulez-vous ${action} le niveau "${escapeHtml(item.libelle)}" ?`,
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

    function showDetail(id) {
        const item = niveaux.find(a => a.id === id);
        if (!item) return;

        $.ajax({
            url: routes.show.replace(':id', id),
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    const cycleLibelle = data.cycle_libelle || cycleMap[data.cycle_id] || '-';
                    $('#detailModalTitle').html(`<i class="fas fa-circle-info me-2"></i>Détail - ${escapeHtml(data.libelle)}`);
                    $('#detailModalBody').html(`
                        <div class="row g-3">
                            <div class="col-12">
                                <p><strong>Libellé :</strong> ${escapeHtml(data.libelle || '-')}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Cycle :</strong> ${escapeHtml(cycleLibelle)}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Numéro d'ordre :</strong> ${data.numero_ordre ?? '-'}</p>
                            </div>
                            <div class="col-12">
                                <p><strong>Description :</strong></p>
                                <p class="text-muted">${escapeHtml(data.description || 'Aucune description')}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Statut :</strong> ${data.etat === 1 ? '<span class="badge badge-success">Actif</span>' : '<span class="badge badge-danger">Inactif</span>'}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Créé le :</strong> ${formatDate(data.created_at)}</p>
                            </div>
                            <div class="col-12">
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
        const item = niveaux.find(a => a.id === id);
        if (!item) return;

        currentEditId = id;
        $('#modalTitle').html(`<i class="fas fa-edit me-2"></i>Modifier - ${escapeHtml(item.libelle)}`);

        $('#niveauId').val(item.id);
        $('#libelle').val(item.libelle || '');
        $('#description').val(item.description || '');
        $('#numero_ordre').val(item.numero_ordre || '');
        $('#cycle_id').val(item.cycle_id || '');
        $('#est_active').prop('checked', item.etat === 1);

        $('#niveauModal').modal('show');
    }

    // ============================================
    // FORMULAIRES
    // ============================================

    function resetForm() {
        $('#niveauForm')[0].reset();
        $('#niveauId').val('');
        currentEditId = null;
        $('.is-invalid').removeClass('is-invalid');
        $('#est_active').prop('checked', true);
    }

    function getFormData() {
        const data = {
            libelle: $('#libelle').val().trim(),
            description: $('#description').val().trim(),
            numero_ordre: parseInt($('#numero_ordre').val()) || 0,
            cycle_id: parseInt($('#cycle_id').val()) || null,
            etat: $('#est_active').is(':checked') ? 1 : 0,
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

        if (!data.cycle_id) {
            $('#cycle_id').addClass('is-invalid');
            isValid = false;
        }

        if (!isValid) {
            showToast('Veuillez remplir tous les champs obligatoires.', 'warning');
        }

        return isValid;
    }
})();
