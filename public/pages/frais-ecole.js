// public/pages/frais-ecole.js

(function() {
    'use strict';

    const config = window.fraisConfig || {};
    const routes = config.routes || {};
    const anneeId = config.anneeId || null;

    let currentEditId = null;
    let ligneCount = 1;
    let dataTable = null;

    // Type paiement labels
    const typeLabels = {
        1: 'Inscription',
        2: 'Scolarité',
        3: 'Services',
        4: 'Produit',
        5: 'Livre',
        6: 'Caution',
        7: 'Bus',
        8: 'Cantine',
        9: 'Autres',
        10: 'Assurance',
        11: 'Extra scolaire',
        12: 'Examen'
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
        dataTable = $('#fraisTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: routes.getData,
                type: 'GET',
                data: function(d) {
                    d.type_paiement = $('#filterType').val();
                    d.niveau_id = $('#filterNiveau').val();
                    d.has_echeancier = $('#filterEcheancier').val();
                    d.etat = $('#filterActive').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '40px' },
                { data: 'libelle', name: 'libelle' },
                { data: 'montant', name: 'montant' },
                { data: 'type_paiement', name: 'type_paiement' },
                { data: 'niveau_id', name: 'niveau_id' },
                { data: 'plan_echeancier_id', name: 'plan_echeancier_id' },
                { data: 'etat', name: 'etat' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            order: [[1, 'asc']],
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
        $('#filterType, #filterNiveau, #filterEcheancier, #filterActive').on('change', function() {
            dataTable.ajax.reload();
        });
    }

    function updateCount(count) {
        $('#fraisCount').text(count + ' frais');
    }

    // ============================================
    // GESTION DES LIGNES D'ÉCHÉANCIER
    // ============================================

    function addLigne() {
        ligneCount++;
        const html = `
            <tr>
                <td><input type="number" class="form-control form-control-sm ligne-ordre" value="${ligneCount}" min="1"></td>
                <td><input type="text" class="form-control form-control-sm ligne-libelle" placeholder="Tranche ${ligneCount}"></td>
                <td><input type="number" class="form-control form-control-sm ligne-montant" step="0.01" placeholder="0" min="0"></td>
                <td><input type="number" class="form-control form-control-sm ligne-pourcentage" step="0.01" placeholder="0" min="0" max="100"></td>
                <td><input type="number" class="form-control form-control-sm ligne-jour" min="1" max="31" placeholder="1-31"></td>
                <td><input type="date" class="form-control form-control-sm ligne-date"></td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger btn-remove-ligne">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#lignesTbody').append(html);
        updateLigneOrdres();
    }

    function removeLigne(btn) {
        const row = $(btn).closest('tr');
        if ($('#lignesTbody tr').length <= 1) {
            showToast('Il doit y avoir au moins une ligne.', 'warning');
            return;
        }
        row.remove();
        updateLigneOrdres();
    }

    function updateLigneOrdres() {
        $('#lignesTbody tr').each(function(index) {
            $(this).find('.ligne-ordre').val(index + 1);
        });
    }

    // ============================================
    // GESTION DE L'ÉCHÉANCIER
    // ============================================

    function toggleEcheancier(show) {
        if (show) {
            $('#echeancierContainer').show();
            $('#planEcheancierSection').show();
            $('#plan_nom').prop('required', true);
        } else {
            $('#echeancierContainer').hide();
            $('#planEcheancierSection').hide();
            $('#plan_nom').prop('required', false);
            $('#plan_echeancier_id').val('');
        }
    }

    // ============================================
    // EVENTS
    // ============================================

    function bindEvents() {
        // Nouveau
        $('#btnNouveau').on('click', function() {
            resetForm();
            currentEditId = null;
            $('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Nouveau frais');
            toggleEcheancier(false);
            $('#has_echeancier').prop('checked', false);
        });

        // Toggle échéancier
        $('#has_echeancier').on('change', function() {
            toggleEcheancier($(this).is(':checked'));
        });

        // Sélection du plan existant ou nouveau
        $('#plan_echeancier_id').on('change', function() {
            const val = $(this).val();
            if (val === 'new') {
                $('#planEcheancierSection').show();
                $('#plan_annee_id').val(anneeId);
            } else if (val && val !== '') {
                $('#planEcheancierSection').hide();
                loadPlanData(val);
            } else {
                $('#planEcheancierSection').hide();
            }
        });

        // Gestion des lignes
        $(document).on('click', '#btnAddLigne', function() {
            addLigne();
        });

        $(document).on('click', '.btn-remove-ligne', function() {
            removeLigne(this);
        });

        // Sauvegarde
        $('#saveFraisBtn').on('click', saveFrais);

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
            deleteFrais(id);
        });

        // Réinitialisation du modal à la fermeture
        $('#fraisModal').on('hidden.bs.modal', function() {
            resetForm();
        });
    }

    // ============================================
    // CRUD OPERATIONS
    // ============================================

    function saveFrais() {
        const data = getFormData();
        const id = currentEditId;
        const isEdit = !!id;

        if (!validateForm(data)) return;

        const url = isEdit ? routes.update.replace(':id', id) : routes.store;
        const method = isEdit ? 'PUT' : 'POST';

        $('#saveFraisBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enregistrement...');

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
                    $('#fraisModal').modal('hide');
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
                $('#saveFraisBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Enregistrer');
            }
        });
    }

    function deleteFrais(id) {
        showConfirm(
            'Supprimer ce frais ?',
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
        const action = 'changer le statut';
        showConfirm(
            'Changer le statut ?',
            'Voulez-vous changer le statut de ce frais ?',
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
                    const typeLabel = typeLabels[data.type_paiement] || 'Inconnu';
                    const niveauLibelle = data.niveau ? data.niveau.libelle : '-';

                    let echeancierHtml = 'Aucun';
                    if (data.plan_echeancier) {
                        echeancierHtml = `
                            <strong>${escapeHtml(data.plan_echeancier.nom)}</strong>
                            <br>
                            <small class="text-muted">${data.plan_echeancier.lignes ? data.plan_echeancier.lignes.length : 0} tranches</small>
                        `;
                    }

                    $('#detailModalTitle').html(`<i class="fas fa-circle-info me-2"></i>Détail - ${escapeHtml(data.libelle)}`);
                    $('#detailModalBody').html(`
                        <div class="row g-3">
                            <div class="col-md-6">
                                <p><strong>Libellé :</strong> ${escapeHtml(data.libelle || '-')}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Montant :</strong> <strong>${formatNumber(data.montant)} FCFA</strong></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Type :</strong> ${escapeHtml(typeLabel)}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Niveau :</strong> ${escapeHtml(niveauLibelle)}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Statut :</strong> ${data.etat === 1 ? '<span class="badge badge-success">Actif</span>' : '<span class="badge badge-danger">Inactif</span>'}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Échéancier :</strong> ${echeancierHtml}</p>
                            </div>
                            ${data.plan_echeancier && data.plan_echeancier.lignes ? `
                                <div class="col-12 mt-3">
                                    <h6>Lignes d'échéancier</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>Ordre</th>
                                                    <th>Libellé</th>
                                                    <th>Montant</th>
                                                    <th>%</th>
                                                    <th>Jour</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${data.plan_echeancier.lignes.map(ligne => `
                                                    <tr>
                                                        <td>${ligne.ordre}</td>
                                                        <td>${escapeHtml(ligne.libelle)}</td>
                                                        <td>${formatNumber(ligne.montant)} FCFA</td>
                                                        <td>${ligne.pourcentage || 0}%</td>
                                                        <td>${ligne.jour_echeance || '-'}</td>
                                                        <td>${formatDate(ligne.date_echeance)}</td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
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
                    $('#modalTitle').html(`<i class="fas fa-edit me-2"></i>Modifier - ${escapeHtml(item.libelle)}`);

                    $('#fraisId').val(item.id);
                    $('#libelle').val(item.libelle || '');
                    $('#montant').val(item.montant || '');
                    $('#type_paiement').val(item.type_paiement || '');
                    $('#type_forfait').val(item.type_forfait || '');
                    $('#niveau_id').val(item.niveau_id || '');
                    $('#est_active').prop('checked', item.etat === 1);

                    const hasEcheancier = item.plan_echeancier_id !== null;
                    $('#has_echeancier').prop('checked', hasEcheancier);
                    toggleEcheancier(hasEcheancier);

                    if (hasEcheancier && item.plan_echeancier) {
                        $('#plan_echeancier_id').val(item.plan_echeancier_id);
                        loadPlanData(item.plan_echeancier_id);
                    }

                    $('#fraisModal').modal('show');
                }
            },
            error: function() {
                showToast('Erreur lors du chargement des données.', 'danger');
            }
        });
    }

    function loadPlanData(planId) {
        $.ajax({
            url: routes.getPlans,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const plan = response.data.find(p => p.id === parseInt(planId));
                    if (plan) {
                        $('#plan_nom').val(plan.nom || '');
                        $('#plan_description').val(plan.description || '');

                        if (plan.lignes && plan.lignes.length > 0) {
                            $('#lignesTbody').empty();
                            plan.lignes.forEach(function(ligne, index) {
                                const html = `
                                    <tr>
                                        <td><input type="number" class="form-control form-control-sm ligne-ordre" value="${ligne.ordre || index + 1}" min="1"></td>
                                        <td><input type="text" class="form-control form-control-sm ligne-libelle" value="${escapeHtml(ligne.libelle || '')}"></td>
                                        <td><input type="number" class="form-control form-control-sm ligne-montant" step="0.01" value="${ligne.montant || 0}" min="0"></td>
                                        <td><input type="number" class="form-control form-control-sm ligne-pourcentage" step="0.01" value="${ligne.pourcentage || 0}" min="0" max="100"></td>
                                        <td><input type="number" class="form-control form-control-sm ligne-jour" min="1" max="31" value="${ligne.jour_echeance || ''}"></td>
                                        <td><input type="date" class="form-control form-control-sm ligne-date" value="${ligne.date_echeance || ''}"></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger btn-remove-ligne">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                `;
                                $('#lignesTbody').append(html);
                            });
                            ligneCount = plan.lignes.length;
                        }
                    }
                }
            },
            error: function() {
                showToast('Erreur lors du chargement du plan.', 'danger');
            }
        });
    }

    // ============================================
    // FORMULAIRES
    // ============================================

    function resetForm() {
        $('#fraisForm')[0].reset();
        $('#fraisId').val('');
        currentEditId = null;
        $('.is-invalid').removeClass('is-invalid');
        $('#est_active').prop('checked', true);
        $('#has_echeancier').prop('checked', false);
        toggleEcheancier(false);
        $('#lignesTbody').empty();
        ligneCount = 0;
        // Ajouter une ligne par défaut
        const defaultHtml = `
            <tr>
                <td><input type="number" class="form-control form-control-sm ligne-ordre" value="1" min="1"></td>
                <td><input type="text" class="form-control form-control-sm ligne-libelle" placeholder="Tranche 1"></td>
                <td><input type="number" class="form-control form-control-sm ligne-montant" step="0.01" placeholder="0" min="0"></td>
                <td><input type="number" class="form-control form-control-sm ligne-pourcentage" step="0.01" placeholder="0" min="0" max="100"></td>
                <td><input type="number" class="form-control form-control-sm ligne-jour" min="1" max="31" placeholder="1-31"></td>
                <td><input type="date" class="form-control form-control-sm ligne-date"></td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger btn-remove-ligne">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#lignesTbody').append(defaultHtml);
        ligneCount = 1;
    }

    function getFormData() {
        const hasEcheancier = $('#has_echeancier').is(':checked');
        const planEcheancierId = $('#plan_echeancier_id').val();
        const isNewPlan = planEcheancierId === 'new';

        const data = {
            libelle: $('#libelle').val().trim(),
            montant: parseFloat($('#montant').val()) || 0,
            type_paiement: parseInt($('#type_paiement').val()) || null,
            type_forfait: parseInt($('#type_forfait').val()) || null,
            niveau_id: parseInt($('#niveau_id').val()) || null,
            etat: $('#est_active').is(':checked') ? 1 : 0,
        };

        // Gestion de l'échéancier
        if (hasEcheancier) {
            if (isNewPlan || planEcheancierId === '') {
                // Créer un nouveau plan
                data.plan_echeancier = {
                    nom: $('#plan_nom').val().trim(),
                    description: $('#plan_description').val().trim(),
                    annee_id: parseInt($('#annee_id').val()) || null,
                    lignes: getLignesData()
                };
            } else {
                data.plan_echeancier_id = parseInt(planEcheancierId);
            }
        }

        if (currentEditId) {
            data.id = currentEditId;
        }

        return data;
    }

    function getLignesData() {
        const lignes = [];
        $('#lignesTbody tr').each(function() {
            const ordre = parseInt($(this).find('.ligne-ordre').val()) || 0;
            const libelle = $(this).find('.ligne-libelle').val().trim();
            const montant = parseFloat($(this).find('.ligne-montant').val()) || 0;
            const pourcentage = parseFloat($(this).find('.ligne-pourcentage').val()) || 0;
            const jour = parseInt($(this).find('.ligne-jour').val()) || null;
            const date = $(this).find('.ligne-date').val() || null;

            if (libelle) {
                lignes.push({ ordre, libelle, montant, pourcentage, jour_echeance: jour, date_echeance: date });
            }
        });
        return lignes;
    }

    function validateForm(data) {
        $('.is-invalid').removeClass('is-invalid');
        let isValid = true;

        if (!data.libelle) {
            $('#libelle').addClass('is-invalid');
            isValid = false;
        }

        if (!data.type_paiement) {
            $('#type_paiement').addClass('is-invalid');
            isValid = false;
        }

        const hasEcheancier = $('#has_echeancier').is(':checked');
        if (hasEcheancier) {
            const planEcheancierId = $('#plan_echeancier_id').val();
            if (planEcheancierId === 'new' || planEcheancierId === '') {
                if (!$('#plan_nom').val().trim()) {
                    $('#plan_nom').addClass('is-invalid');
                    isValid = false;
                }
                const lignes = getLignesData();
                if (lignes.length === 0) {
                    showToast('Veuillez ajouter au moins une ligne d\'échéancier.', 'warning');
                    isValid = false;
                }
            }
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
