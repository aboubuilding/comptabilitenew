@extends('admin.layouts.app')

@section('page_title', 'Communications')
@section('page_icon', 'fa-envelope')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Administration</a></li>
    <li class="breadcrumb-item active">Communications</li>
@endsection

@section('page_actions')
    @can('communications.envoyer')
    <button type="button" class="btn-nouveau" id="btn-nouveau-message">
        <i class="fa fa-plus"></i> Nouveau message
    </button>
    @endcan
    <a href="{{ route('administration.modeles.index') }}" class="btn-outline-scolarite ms-2">
        <i class="fa fa-file-lines"></i> Modèles
    </a>
    <a href="{{ route('administration.messages.export', request()->query()) }}" class="btn-outline-scolarite ms-2">
        <i class="fa fa-file-excel"></i> Exporter
    </a>
@endsection

@section('contenu')

    {{-- Stats --}}
    <div class="row mb-3">
        @php
            $cartes = [
                ['Total', $stats['total'], 'secondary', 'fa-envelope'],
                ['Brouillons', $stats['brouillons'], 'secondary', 'fa-pen'],
                ['En cours', $stats['en_cours'], 'info', 'fa-spinner'],
                ['Envoyés', $stats['envoyes'], 'success', 'fa-check'],
            ];
        @endphp
        @foreach($cartes as [$lib, $val, $coul, $ico])
            <div class="col-md-3">
                <div class="stat-card p-3 bg-white rounded shadow-sm">
                    <div class="text-muted small"><i class="fa {{ $ico }} me-1"></i> {{ $lib }}</div>
                    <div class="h4 mb-0">{{ $val }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Filtres --}}
    <div class="main-card p-3 mb-3">
        <form method="GET" action="{{ route('administration.messages.index') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <x-libelle class="small">Statut</x-libelle>
                <select name="statut" class="form-select filtre-input">
                    <option value="">Tous</option>
                    @foreach(['brouillon','en_cours','envoye','partiel','echec'] as $s)
                        <option value="{{ $s }}" @selected(($filtres['statut'] ?? '') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <x-libelle class="small">Recherche</x-libelle>
                <input type="text" name="recherche" class="form-control filtre-input"
                       value="{{ $filtres['recherche'] ?? '' }}" placeholder="Sujet ou corps...">
            </div>
            <div class="col-md-2">
                <x-libelle class="small">Du</x-libelle>
                <input type="date" name="date_debut" class="form-control filtre-input"
                       value="{{ $filtres['date_debut'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <x-libelle class="small">Au</x-libelle>
                <input type="date" name="date_fin" class="form-control filtre-input"
                       value="{{ $filtres['date_fin'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn-filtrer w-100">
                    <i class="fa fa-filter"></i> Filtrer
                </button>
            </div>
        </form>
    </div>

    {{-- Tableau --}}
    <div class="main-card">
        <x-tableau class="table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Sujet</th>
                        <th>Modèle</th>
                        <th>Expéditeur</th>
                        <th class="text-center">Destinataires</th>
                        <th>Statut</th>
                        <th>Date d'envoi</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($messages as $m)
                        <tr>
                            <td><strong>{{ $m->sujet }}</strong></td>
                            <td>{{ $m->modele->libelle ?? '—' }}</td>
                            <td>{{ $m->expediteur_nom ?? '—' }}</td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $m->nb_destinataires }}</span>
                                @if($m->nb_envoyes > 0)
                                    <span class="badge bg-success">{{ $m->nb_envoyes }}</span>
                                @endif
                                @if($m->nb_echecs > 0)
                                    <span class="badge bg-danger">{{ $m->nb_echecs }}</span>
                                @endif
                            </td>
                            <td>
                                <x-badge class="bg-{{ $m->statut?->couleur() ?? 'secondary' }}">
                                    {{ $m->statut?->libelle() ?? '—' }}
                                </x-badge>
                            </td>
                            <td>
                                @if($m->date_envoi)
                                    {{ $m->date_envoi->format('d/m/Y H:i') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <x-actions>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('administration.messages.show', $m) }}">
                                                <i class="fa fa-eye"></i> Consulter
                                            </a>
                                        </li>
                                        @if($m->statut?->value === 'brouillon')
                                            @can('communications.envoyer')
                                            <li>
                                                <button type="button" class="dropdown-item btn-edit-message"
                                                        data-url="{{ route('administration.messages.edit', $m) }}">
                                                    <i class="fa fa-edit"></i> Modifier
                                                </button>
                                            </li>
                                            @endcan
                                            @can('communications.envoyer')
                                            <li>
                                                <form action="{{ route('administration.messages.envoyer', $m) }}"
                                                      method="POST" class="form-confirm-envoyer">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-primary">
                                                        <i class="fa fa-paper-plane"></i> Envoyer
                                                    </button>
                                                </form>
                                            </li>
                                            @endcan
                                        @endif
                                        @can('communications.envoyer')
                                        <x-actions.supprimer :action="route('administration.messages.destroy', $m)" icone="fa fa-trash">Supprimer</x-actions.supprimer>
                                        @endcan
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="7" icone="fa fa-envelope fa-2x text-muted" :paragraphe="false" class="text-center py-4"><p class="mt-2 mb-0">Aucun message.</p></x-vide>
                    @endforelse
                </tbody>
            </x-tableau>
        <div class="p-3">
            <x-pagination :liste="$messages" />
        </div>
    </div>

    {{-- Modale --}}
    <x-modale id="modalMessage" aria-hidden="true" dialogue="modal-xl modal-dialog-centered" contenu-id="modalMessageContent" />

@endsection

@push('js')
<script>
$(function () {
    const modalEl  = document.getElementById('modalMessage');
    const modal    = new bootstrap.Modal(modalEl);
    const $contenu = $('#modalMessageContent');

    function charger(url) {
        $contenu.html('<div class="p-4 text-center"><i class="fa fa-spinner fa-spin"></i> Chargement...</div>');
        modal.show();
        $.get(url, html => $contenu.html(html))
         .fail(() => $contenu.html('<div class="p-4 text-danger">Erreur de chargement.</div>'));
    }

    $('#btn-nouveau-message').on('click', () => charger("{{ route('administration.messages.create') }}"));
    $(document).on('click', '.btn-edit-message', function () { charger($(this).data('url')); });

    // Soumission AJAX
    $(document).on('submit', '#formMessage', function (e) {
        e.preventDefault();
        const $form = $(this);
        const url   = $form.attr('action');
        const methode = $form.find('input[name="_method"]').val() || 'POST';

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();

        // serialize() ignore le bouton cliqué : on l'ajoute, sinon « Enregistrer et
        // envoyer » ne se distingue pas de « Enregistrer en brouillon ».
        const bouton = e.originalEvent?.submitter;
        let donnees  = $form.serialize() + '&_method=' + methode;
        if (bouton && bouton.name) {
            donnees += '&' + encodeURIComponent(bouton.name) + '=' + encodeURIComponent(bouton.value);
        }

        $.ajax({
            url: url,
            type: 'POST',
            data: donnees,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        })
        .done(r => {
            modal.hide();
            if (window.showToastThenReload) window.showToastThenReload(r.message || 'OK');
            else window.location.reload();
        })
        .fail(xhr => {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                $.each(xhr.responseJSON.errors, function (champ, messages) {
                    const $input = $form.find('[name="' + champ + '"]');
                    $input.addClass('is-invalid');
                    $input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                });
            } else {
                const msg = xhr.responseJSON?.message || 'Erreur.';
                if (window.showToast) window.showToast(msg, 'error'); else alert(msg);
            }
        });
    });

    modalEl.addEventListener('hidden.bs.modal', () => $contenu.html(''));
});
</script>
@endpush