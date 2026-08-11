// public/pages/user.js

(function() {
    'use strict';

    const config = window.usersConfig || {};
    const users = config.users || [];
    const routes = config.routes || {};

    let currentEditId = null;

    // ============================================
    // INITIALISATION
    // ============================================

    $(document).ready(function() {
        renderTable(users);
        updateCount(users.length);
        bindEvents();
    });

    // ============================================
    // RENDU DU TABLEAU
    // ============================================

    function renderTable(data) {
        const tbody = $('#usersTbody');
        if (!data || data.length === 0) {
            tbody.html(`
            <tr>
                <td colspan="6">
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        Aucun utilisateur trouvé.
                        <br>
                        <small>Cliquez sur "Nouvel utilisateur" pour en créer un.</small>
                    </div>
                </td>
            </tr>
        `);
            return;
        }

        // Mapping des rôles (peut être chargé depuis le serveur)
        const roleLabels = {
            1: 'Administrateur',
            2: 'Directeur',
            3: 'Comptable',
            4: 'Admin Adjoint',
            5: 'Caissier',
            6: 'Secrétaire',
            7: 'Enseignant',
            8: 'Parent'
        };

        const roleBadgeClasses = {
            1: 'badge-danger',
            2: 'badge-primary',
            3: 'badge-success',
            4: 'badge-info',
            5: 'badge-warning',
            6: 'badge-secondary',
            7: 'badge-dark',
            8: 'badge-info'
        };

        let html = '';
        data.forEach(function(item) {
            // Utiliser les données de l'API ou le mapping local
            const roleLabel = item.role_label || roleLabels[item.role] || 'Inconnu';
            const roleBadgeClass = item.role_badge_class || roleBadgeClasses[item.role] || 'badge-secondary';

            const fullName = item.prenom && item.nom ? `${escapeHtml(item.prenom)} ${escapeHtml(item.nom)}` : (item.login || '-');
            const activeBadge = item.etat === 1
                ? '<span class="badge badge-active">Actif</span>'
                : '<span class="badge badge-inactive">Inactif</span>';
            const roleBadge = `<span class="badge ${roleBadgeClass}">${escapeHtml(roleLabel)}</span>`;

            html += `
            <tr data-id="${item.id}">
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-circle me-2" style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg, #e8b23a, #d9a441);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#1a2b40;">
                            ${fullName.substring(0, 2).toUpperCase()}
                        </div>
                        <div>
                            <strong>${fullName}</strong>
                            <div class="text-muted small">${escapeHtml(item.login || '-')}</div>
                        </div>
                    </div>
                </td>
                <td>${roleBadge}</td>
                <td>${escapeHtml(item.email || '-')}</td>
                <td>${activeBadge}</td>
                <td>${formatDate(item.created_at)}</td>
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
                                <button class="dropdown-item btn-password" data-id="${item.id}">
                                    <i class="fas fa-key"></i> Mot de passe
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
        $('#userCount').text(count + ' utilisateur' + (count > 1 ? 's' : ''));
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
        const role = $('#filterRole').val();
        const etat = $('#filterActive').val();

        let filtered = users;

        if (role !== '') {
            filtered = filtered.filter(item => item.role === parseInt(role));
        }

        if (etat !== '') {
            filtered = filtered.filter(item => item.etat === parseInt(etat));
        }

        if (search) {
            filtered = filtered.filter(item => {
                const nom = (item.nom || '').toLowerCase();
                const prenom = (item.prenom || '').toLowerCase();
                const login = (item.login || '').toLowerCase();
                const email = (item.email || '').toLowerCase();
                return nom.includes(search) || prenom.includes(search) ||
                    login.includes(search) || email.includes(search);
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
        $('#filterRole, #filterActive').on('change', filterData);

        // Nouveau
        $('#btnNouveau').on('click', function() {
            resetForm();
            currentEditId = null;
            $('#modalTitle').html('<i class="fas fa-user-plus me-2"></i>Nouvel utilisateur');
            $('#confirmPasswordGroup').show();
            $('#mot_passe').prop('required', true);
            $('#mot_passe_confirmation').prop('required', true);
        });

        // Sauvegarde
        $('#saveUserBtn').on('click', saveUser);

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

        // Mot de passe
        $(document).on('click', '.btn-password', function() {
            const id = $(this).data('id');
            openPasswordModal(id);
        });

        // Activer/Désactiver
        $(document).on('click', '.btn-toggle-active', function() {
            const id = $(this).data('id');
            toggleActive(id);
        });

        // Supprimer
        $(document).on('click', '.btn-delete', function() {
            const id = $(this).data('id');
            deleteUser(id);
        });

        // Sauvegarde mot de passe
        $('#savePasswordBtn').on('click', savePassword);

        // Réinitialisation du modal à la fermeture
        $('#userModal').on('hidden.bs.modal', function() {
            resetForm();
        });

        $('#passwordModal').on('hidden.bs.modal', function() {
            $('#passwordForm')[0].reset();
        });
    }

    // ============================================
    // CRUD OPERATIONS
    // ============================================

    function saveUser() {
        const data = getFormData();
        const id = currentEditId;
        const isEdit = !!id;

        if (!validateForm(data)) return;

        const url = isEdit ? routes.update.replace(':id', id) : routes.store;
        const method = isEdit ? 'PUT' : 'POST';

        $('#saveUserBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enregistrement...');

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
                    $('#userModal').modal('hide');
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
                $('#saveUserBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Enregistrer');
            }
        });
    }

    function deleteUser(id) {
        const item = users.find(a => a.id === id);
        if (!item) return;

        showConfirm(
            'Supprimer cet utilisateur ?',
            `Voulez-vous vraiment supprimer l'utilisateur "${escapeHtml(item.prenom)} ${escapeHtml(item.nom)}" ? Cette action est irréversible.`,
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
        const item = users.find(a => a.id === id);
        if (!item) return;

        const action = item.etat === 1 ? 'désactiver' : 'activer';
        showConfirm(
            `${action.charAt(0).toUpperCase() + action.slice(1)} l'utilisateur ?`,
            `Voulez-vous ${action} l'utilisateur "${escapeHtml(item.prenom)} ${escapeHtml(item.nom)}" ?`,
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
        const item = users.find(a => a.id === id);
        if (!item) return;

        $.ajax({
            url: routes.show.replace(':id', id),
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    const fullName = data.prenom && data.nom ? `${escapeHtml(data.prenom)} ${escapeHtml(data.nom)}` : (data.login || '-');
                    $('#detailModalTitle').html(`<i class="fas fa-circle-info me-2"></i>Détail - ${fullName}`);
                    $('#detailModalBody').html(`
                        <div class="row g-3">
                            <div class="col-12 text-center mb-3">
                                <div class="avatar-circle mx-auto" style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg, #e8b23a, #d9a441);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;color:#1a2b40;">
                                    ${fullName.substring(0, 2).toUpperCase()}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Nom :</strong> ${escapeHtml(data.nom || '-')}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Prénom :</strong> ${escapeHtml(data.prenom || '-')}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Login :</strong> <code>${escapeHtml(data.login || '-')}</code></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Email :</strong> ${escapeHtml(data.email || '-')}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Rôle :</strong> <span class="badge ${data.role_badge_class}">${escapeHtml(data.role_label)}</span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Statut :</strong> ${data.etat === 1 ? '<span class="badge badge-active">Actif</span>' : '<span class="badge badge-inactive">Inactif</span>'}</p>
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
        const item = users.find(a => a.id === id);
        if (!item) return;

        currentEditId = id;
        const fullName = item.prenom && item.nom ? `${escapeHtml(item.prenom)} ${escapeHtml(item.nom)}` : (item.login || '');
        $('#modalTitle').html(`<i class="fas fa-edit me-2"></i>Modifier - ${fullName}`);

        $('#userId').val(item.id);
        $('#nom').val(item.nom || '');
        $('#prenom').val(item.prenom || '');
        $('#login').val(item.login || '');
        $('#email').val(item.email || '');
        $('#role').val(item.role || '');
        $('#est_active').prop('checked', item.etat === 1);

        // Cacher la confirmation de mot de passe pour l'édition
        $('#confirmPasswordGroup').hide();
        $('#mot_passe').prop('required', false);
        $('#mot_passe_confirmation').prop('required', false);
        $('#mot_passe').attr('placeholder', 'Laisser vide pour conserver');

        $('#userModal').modal('show');
    }

    function openPasswordModal(id) {
        const item = users.find(a => a.id === id);
        if (!item) return;

        $('#passwordUserId').val(id);
        $('#passwordModal').modal('show');
        $('#new_password').focus();
    }

    function savePassword() {
        const id = $('#passwordUserId').val();
        const password = $('#new_password').val();
        const confirmation = $('#new_password_confirmation').val();

        if (!password || password.length < 6) {
            showToast('Le mot de passe doit contenir au moins 6 caractères.', 'warning');
            return;
        }

        if (password !== confirmation) {
            showToast('Les mots de passe ne correspondent pas.', 'warning');
            return;
        }

        $('#savePasswordBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enregistrement...');

        $.ajax({
            url: routes.changePassword.replace(':id', id),
            method: 'POST',
            data: {
                mot_passe: password,
                mot_passe_confirmation: confirmation
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    $('#passwordModal').modal('hide');
                    $('#passwordForm')[0].reset();
                } else {
                    showToast(response.message || 'Erreur.', 'danger');
                }
            },
            error: function() {
                showToast('Erreur lors du changement de mot de passe.', 'danger');
            },
            complete: function() {
                $('#savePasswordBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Enregistrer');
            }
        });
    }

    // ============================================
    // FORMULAIRES
    // ============================================

    function resetForm() {
        $('#userForm')[0].reset();
        $('#userId').val('');
        currentEditId = null;
        $('.is-invalid').removeClass('is-invalid');
        $('#est_active').prop('checked', true);
        $('#confirmPasswordGroup').show();
        $('#mot_passe').prop('required', true);
        $('#mot_passe_confirmation').prop('required', true);
        $('#mot_passe').attr('placeholder', '••••••••');
    }

    function getFormData() {
        const data = {
            nom: $('#nom').val().trim(),
            prenom: $('#prenom').val().trim(),
            login: $('#login').val().trim(),
            email: $('#email').val().trim(),
            role: parseInt($('#role').val()),
            etat: $('#est_active').is(':checked') ? 1 : 0,
            mot_passe: $('#mot_passe').val()
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
        if (!data.prenom) {
            $('#prenom').addClass('is-invalid');
            isValid = false;
        }
        if (!data.login) {
            $('#login').addClass('is-invalid');
            isValid = false;
        }
        if (!data.role) {
            $('#role').addClass('is-invalid');
            isValid = false;
        }

        // Vérification du mot de passe uniquement pour la création
        if (!currentEditId) {
            if (!data.mot_passe || data.mot_passe.length < 6) {
                $('#mot_passe').addClass('is-invalid');
                showToast('Le mot de passe doit contenir au moins 6 caractères.', 'warning');
                isValid = false;
            }
            const confirmation = $('#mot_passe_confirmation').val();
            if (data.mot_passe !== confirmation) {
                $('#mot_passe_confirmation').addClass('is-invalid');
                showToast('Les mots de passe ne correspondent pas.', 'warning');
                isValid = false;
            }
        }

        if (!isValid) {
            showToast('Veuillez remplir tous les champs obligatoires.', 'warning');
        }

        return isValid;
    }
})();
