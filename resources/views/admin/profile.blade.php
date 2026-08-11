<?php
{{-- resources/views/admin/profile.blade.php --}}

@extends('admin.layouts.app')

@section('title', 'Mon profil · École Mariam')

@section('page_title', 'Mon profil')
@section('page_icon', 'fa-user-circle')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item active">Mon profil</li>
@endsection

@section('contenu')
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="avatar-circle mx-auto mb-3" style="width:100px;height:100px;border-radius:50%;background:linear-gradient(145deg, #f2c766, #e8b23a);display:flex;align-items:center;justify-content:center;font-size:36px;font-weight:800;color:#1a2b40;">
                        {{ strtoupper(substr($nomComplet, 0, 2) ?? 'AD') }}
                    </div>
                    <h4 class="mb-1">{{ $nomComplet }}</h4>
                    <p class="text-muted">{{ $roleLabel }}</p>
                    <p class="text-muted small"><i class="fas fa-envelope me-1"></i> {{ $user->email }}</p>
                    <hr>
                    <p class="text-muted small">
                        <i class="fas fa-calendar me-1"></i> Membre depuis : {{ $user->created_at->format('d/m/Y') }}
                    </p>
                    <p class="text-muted small">
                        <i class="fas fa-clock me-1"></i> Dernière connexion : {{ $user->updated_at->diffForHumans() }}
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            {{-- Formulaire de mise à jour du profil --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i> Modifier mes informations</h5>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form action="{{ route('profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" name="nom" id="nom" class="form-control @error('nom') is-invalid @enderror"
                                       value="{{ old('nom', $user->nom) }}" required>
                                @error('nom')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                                <input type="text" name="prenom" id="prenom" class="form-control @error('prenom') is-invalid @enderror"
                                       value="{{ old('prenom', $user->prenom) }}" required>
                                @error('prenom')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $user->email) }}" required>
                            @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-save me-1"></i> Mettre à jour
                        </button>
                    </form>
                </div>
            </div>

            {{-- Formulaire de changement de mot de passe --}}
            <div class="card shadow-sm" id="password">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-lock me-2"></i> Changer mon mot de passe</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.password') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mot de passe actuel <span class="text-danger">*</span></label>
                            <input type="password" name="current_password" id="current_password"
                                   class="form-control @error('current_password') is-invalid @enderror" required>
                            @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nouveau mot de passe <span class="text-danger">*</span></label>
                            <input type="password" name="new_password" id="new_password"
                                   class="form-control @error('new_password') is-invalid @enderror" required>
                            @error('new_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="new_password_confirmation" class="form-label">Confirmer le nouveau mot de passe <span class="text-danger">*</span></label>
                            <input type="password" name="new_password_confirmation" id="new_password_confirmation"
                                   class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key me-1"></i> Changer le mot de passe
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        .avatar-circle {
            box-shadow: 0 4px 20px rgba(232,168,56,0.3);
        }
        .card-header {
            background: #f8f9fa;
            border-bottom: 2px solid #e8b23a;
        }
        .btn-danger {
            background: linear-gradient(120deg, #8b0d24, #d21034);
            border: none;
        }
        .btn-danger:hover {
            background: linear-gradient(120deg, #7a0f1c, #b31c2b);
        }
        .btn-warning {
            background: linear-gradient(120deg, #b8860b, #e8b23a);
            border: none;
            color: #fff;
        }
        .btn-warning:hover {
            background: linear-gradient(120deg, #9c7510, #d4a02e);
            color: #fff;
        }
    </style>
@endsection
