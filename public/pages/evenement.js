// public/pages/evenement.js

(function() {
    'use strict';

    const config = window.evenementConfig || {};
    const routes = config.routes || {};
    const anneeId = config.anneeId || null;

    let currentEditId = null;
    let dataTable = null;

    // Type labels
    const typeLabels = {
        'excursion': 'Excursion',
        'voyage': 'Voyage',
        'sortie_pedagogique': 'Sortie Pédagogique',
        'competition': 'Compétition',
        'autre': 'Autre'
    };

    const typeColors = {
        'excursion': 'primary',
        'voyage': 'info',
        'sortie_pedagogique': 'success',
        'competition': 'warning',
        'autre': 'secondary'
    };

    // ============================================
    // INITIALISATION
    // ============================================

    $(document).ready(function() {
        initDataTable();
        bindEvents();
    });

    // ============================================
    // DATATABLES
    // ============================================

    function initDataTable() {
        dataTable = $('#evenementsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: routes.getData,
                type: 'GET',
                data: function(d) {
                    d.type = $('#filterType').val();
                    d.statut = $('#filterStatut').val();
                    d.etat = $('#filterActive').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '40px' },
                { data: 'nom', name: 'nom' },
                { data: 'type', name: 'type' },
                { data: 'date_evenement', name: 'date_evenement' },
                { data: 'capacite', name: 'capacite' },
                { data: 'participation', name: 'participation' },
                { data: 'statut', name: 'statut' },
                { data: 'etat', name: 'etat' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            order: [[3, 'asc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Tous']],
            language: {
                processing: 'Traitement en cours...',
                search: 'Rechercher :',
                lengthMenu: 'Afficher _MENU_ entrées',
                info: 'Affichage de _START_ à _END_ sur _TOTAL_ entrées',
                infoEmpty: 'Affichage de 0 à 0 sur 0 entrées',
                infoFiltered: '(filtré de _MAX_ entrées totales)',
                zeroRecords: 'Aucun enregistrement trouvé',
                paginate: {
                    first: 'Premier',
                    previous: 'Précédent',
                    next: 'Suivant',
                    last: 'Dernier'
                }
            },
            drawCallback: function() {
                updateCount(this.api().page.info().recordsTotal);
            }
        });

        // Rafraîchir les filtres
        $('#filterType, #filterStatut, #filterActive').on('change', function() {
            dataTable.ajax.reload();
        });
    }

    function updateCount(count) {
        $('#evenementCount').text(count + ' événement' + (count > 1 ? 's' : ''));
    }

    // ============================================
    // EVENTS
    // ============================================

    function bindEvents() {
        // Nouveau
        $('#btnNouveau').on('click', function() {
            resetForm();
            currentEditId = null;
            $('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Nouvel événement');
        });

        // Sauvegarde
        $('#saveEvenementBtn').on('click', saveEvenement);

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
            deleteEvenement(id);
        });

        // Réinitialisation du modal à la fermeture
        $('#evenementModal').on('hidden.bs.modal', function() {
            resetForm();
        });
    }

    // ============================================
    // CRUD OPERATIONS
    // ============================================

    function saveEvenement() {
        const data = getFormData();
        const id = currentEditId;
        const isEdit = !!id;

        if (!validateForm(data)) return;

        const url = isEdit ? routes.update.replace(':id', id) : routes.store;
        const method = isEdit ? 'PUT' : 'POST';

        $('#saveEvenementBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enregistrement...');

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
                    $('#evenementModal').modal('hide');
                    dataTable.ajax.reload();
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
                $('#saveEvenementBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Enregistrer');
            }
        });
    }

    function deleteEvenement(id) {
        showConfirm(
            'Supprimer cet événement ?',
            'Cette action est irréversible. Voulez-vous continuer ?',
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
                            dataTable.ajax.reload();
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
        showConfirm(
            'Changer le statut ?',
            'Voulez-vous changer le statut de cet événement ?',
            'Oui, changer',
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
                            dataTable.ajax.reload();
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
        $.ajax({
            url: routes.show.replace(':id', id),
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    const typeLabel = typeLabels[data.type] || 'Inconnu';
                    const typeColor = typeColors[data.type] || 'secondary';

                    let statutLabel = 'Inconnu';
                    let statutBadge = 'secondary';
                    if (data.date_evenement) {
                        const eventDate = new Date(data.date_evenement);
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);

                        if (eventDate < today) {
                            statutLabel = 'Passé';
                            statutBadge = 'secondary';
                        } else if (eventDate.getTime() === today.getTime()) {
                            statutLabel = 'Aujourd\'hui';
                            statutBadge = 'warning';
                        } else {
                            statutLabel = 'À venir';
                            statutBadge = 'success';
                        }
                    }

                    $('#detailModalTitle').html(`<i class="fas fa-circle-info me-2"></i>Détail - ${escapeHtml(data.nom)}`);
                    $('#detailModalBody').html(`
                        <div class="row g-3">
                            <div class="col-md-8">
                                <p><strong>Nom :</strong> ${escapeHtml(data.nom || '-')}</p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>Type :</strong> <span class="badge badge-${typeColor}">${escapeHtml(typeLabel)}</span></p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>Date :</strong> ${formatDate(data.date_evenement)}</p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>Statut :</strong> <span class="badge badge-${statutBadge}">${statutLabel}</span></p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>État :</strong> ${data.etat === 1 ? '<span class="badge badge-success">Actif</span>' : '<span class="badge badge-danger">Inactif</span>'}</p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>Participation :</strong> <strong>${formatNumber(data.participation)} FCFA</strong></p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>Capacité :</strong> ${data.capacite || 'Illimité'}</p>
                            </div>
                            ${data.description ? `
                                <div class="col-12">
                                    <p><strong>Description :</strong></p>
                                    <p class="text-muted">${escapeHtml(data.description)}</p>
                                </div>
                            ` : ''}
                            <div class="col-12 text-muted small">
                                <p>Créé le : ${formatDate(data.created_at)}</p>
                                <p>Modifié le : ${formatDate(data.updated_at)}</p>
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
        $.ajax({
            url: routes.show.replace(':id', id),
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const item = response.data;
                    currentEditId = id;
                    $('#modalTitle').html(`<i class="fas fa-edit me-2"></i>Modifier - ${escapeHtml(item.nom)}`);

                    $('#evenementId').val(item.id);
                    $('#nom').val(item.nom || '');
                    $('#type').val(item.type || '');
                    $('#description').val(item.description || '');
                    $('#date_evenement').val(item.date_evenement || '');
                    $('#participation').val(item.participation || '');
                    $('#capacite').val(item.capacite || '');
                    $('#est_active').prop('checked', item.etat === 1);

                    $('#evenementModal').modal('show');
                }
            },
            error: function() {
                showToast('Erreur lors du chargement des données.', 'danger');
            }
        });
    }

    // ============================================
    // FORMULAIRES
    // ============================================

    function resetForm() {
        $('#evenementForm')[0].reset();
        $('#evenementId').val('');
        currentEditId = null;
        $('.is-invalid').removeClass('is-invalid');
        $('#est_active').prop('checked', true);
    }

    function getFormData() {
        const data = {
            nom: $('#nom').val().trim(),
            type: $('#type').val(),
            description: $('#description').val().trim(),
            date_evenement: $('#date_evenement').val(),
            participation: parseFloat($('#participation').val()) || 0,
            capacite: parseInt($('#capacite').val()) || null,
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

        if (!data.nom) {
            $('#nom').addClass('is-invalid');
            isValid = false;
        }

        if (!data.type) {
            $('#type').addClass('is-invalid');
            isValid = false;
        }

        if (!data.date_evenement) {
            $('#date_evenement').addClass('is-invalid');
            isValid = false;
        }

        if (data.participation < 0) {
            $('#participation').addClass('is-invalid');
            showToast('Le montant de participation doit être supérieur ou égal à 0.', 'warning');
            isValid = false;
        }

        if (!isValid) {
            showToast('Veuillez remplir tous les champs obligatoires.', 'warning');
        }

        return isValid;
    }

    // ============================================
    // FONCTIONS UTILITAIRES
    // ============================================

    function formatNumber(num) {
        if (!num || num === 0) return '0';
        return new Intl.NumberFormat('fr-FR').format(num);
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
})();
