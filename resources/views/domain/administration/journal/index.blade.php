@extends('admin.layouts.app')

@section('page_title', 'Journal & Traçabilité')
@section('page_icon', 'fa-clipboard-list')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Administration</a></li>
    <li class="breadcrumb-item active">Journal</li>
@endsection

@section('page_actions')
    <a href="{{ route('administration.journal.export', request()->query()) }}" class="btn-outline-scolarite">
        <i class="fa fa-file-excel"></i> Exporter
    </a>
@endsection

@section('contenu')
    <div class="main-card p-3 mb-3">
        <form method="GET" action="{{ route('administration.journal.index') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small">Utilisateur</label>
                <select name="user_id" class="form-select filtre-input">
                    <option value="">Tous</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" @selected(($filtres['user_id'] ?? '') == $u->id)>
                            {{ $u->nom }} {{ $u->prenom }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Module</label>
                <select name="module" class="form-select filtre-input">
                    <option value="">Tous</option>
                    @foreach($modules as $m)
                        <option value="{{ $m }}" @selected(($filtres['module'] ?? '') === $m)>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Action</label>
                <select name="action" class="form-select filtre-input">
                    <option value="">Toutes</option>
                    @foreach($types as $t)
                        <option value="{{ $t }}" @selected(($filtres['action'] ?? '') === $t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Du</label>
                <input type="date" name="date_debut" class="form-control filtre-input"
                       value="{{ $filtres['date_debut'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Au</label>
                <input type="date" name="date_fin" class="form-control filtre-input"
                       value="{{ $filtres['date_fin'] ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label small">Recherche</label>
                <input type="text" name="recherche" class="form-control filtre-input"
                       value="{{ $filtres['recherche'] ?? '' }}" placeholder="Description, entité, utilisateur...">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn-filtrer w-100">
                    <i class="fa fa-filter"></i> Filtrer
                </button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('administration.journal.index') }}" class="btn-annuler w-100">Réinitialiser</a>
            </div>
        </form>
    </div>

    <div class="main-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Utilisateur</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Entité</th>
                        <th>Description</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($actions as $a)
                        <tr>
                            <td><small>{{ $a->created_at?->format('d/m/Y H:i:s') }}</small></td>
                            <td>
                                <strong>{{ $a->user_nom ?? '—' }}</strong>
                                <br><small class="text-muted">{{ $a->user_login }}</small>
                            </td>
                            <td><span class="badge bg-secondary">{{ $a->module }}</span></td>
                            <td>
                                @php
                                    $couleur = match($a->action) {
                                        'create' => 'success',
                                        'update' => 'warning',
                                        'delete' => 'danger',
                                        'login'  => 'info',
                                        'logout' => 'secondary',
                                        'export' => 'primary',
                                        default  => 'secondary',
                                    };
                                @endphp
                                <span class="badge bg-{{ $couleur }}">{{ $a->action }}</span>
                            </td>
                            <td>
                                @if($a->entite)
                                    <small>{{ $a->entite }}{{ $a->entite_id ? ' #'.$a->entite_id : '' }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $a->description }}</td>
                            <td><small class="text-muted">{{ $a->ip }}</small></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state text-center py-4">
                                    <i class="fa fa-clipboard-list fa-2x text-muted"></i>
                                    <p class="mt-2 mb-0">Aucune action enregistrée.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">
            {{ $actions->links('vendor.pagination.custom') }}
        </div>
    </div>
@endsection