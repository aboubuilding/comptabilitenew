@extends('admin.layouts.app')

@section('title', 'Rémunérations')
@section('page_icon', 'fa-sliders-h')
@section('page_title', 'Rémunérations & Déductions')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Ressources Humaines</li>
    <li class="breadcrumb-item"><a href="{{ route('rh.paies.index') }}">Paie</a></li>
    <li class="breadcrumb-item active">Rémunérations</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('rh.paies.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-money-check-alt me-1"></i> Bulletins
    </x-bouton>
@endsection

@section('css')
<style>
    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; margin-bottom:1rem; }
    .main-card .card-header { border-bottom:1px solid var(--eim-border) !important; padding:.9rem 1.25rem; }
    .main-card .card-header h6 { margin:0; font-size:.92rem; font-weight:700; color:var(--school-ink); display:flex; align-items:center; gap:8px; }
    .main-card .card-header i { color:var(--school-gold-dark); }
    .form-section { padding:1rem 1.25rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control { border-radius:8px; border:1px solid var(--eim-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:8px; padding:.55rem 1.2rem; font-weight:600; font-size:.88rem; }
    .total-box { background:var(--eim-cream); border-radius:10px; padding:1rem 1.15rem; margin-top:1rem; }
    .total-row { display:flex; justify-content:space-between; padding:.35rem 0; font-size:.9rem; }
    .total-row.grand { font-size:1.15rem; font-weight:700; padding-top:.6rem; border-top:1px solid var(--eim-border); margin-top:.4rem; color:var(--school-red-dark); }
</style>
@endsection

@section('contenu')
<div class="row g-3">

    {{-- Sélection employé --}}
    <div class="col-12">
        <x-carte>
            <div class="card-header">
                <h6><i class="fas fa-user me-2"></i>Sélectionner un employé</h6>
            </div>
            <x-section>
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-6">
                        <x-libelle>Employé</x-libelle>
                        <select name="employe_id" class="form-select form-control" required>
                            <option value="">— Sélectionner —</option>
                            @foreach ($employes as $e)
                                <option value="{{ $e->id }}" @selected(($employe->id ?? null) == $e->id)>
                                    {{ $e->nom_prenom }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <x-bouton variante="filtrer" type="submit" class="w-100">
                            <i class="fas fa-search me-1"></i> Charger
                        </x-bouton>
                    </div>
                </form>
            </x-section>
        </x-carte>
    </div>

    @if ($employe)
        {{-- Rémunération --}}
        <div class="col-lg-6">
            <x-carte>
                <div class="card-header">
                    <h6><i class="fas fa-coins me-2"></i>Rémunération — {{ $employe->nom_prenom }}</h6>
                </div>
                <form method="POST" action="{{ route('rh.remunerations.storeRemuneration') }}" class="form-section">
                    @csrf
                    <input type="hidden" name="employe_id" value="{{ $employe->id }}">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <x-libelle requis>Salaire de base</x-libelle>
                            <input type="number" name="salaire_base" step="0.01" min="0" class="form-control" required
                                   value="{{ $remuneration?->salaire_base ?? $employe->salaire }}">
                        </div>
                        <div class="col-md-6">
                            <x-libelle>Prime ancienneté</x-libelle>
                            <input type="number" name="prime_anciennete" step="0.01" min="0" class="form-control"
                                   value="{{ $remuneration?->prime_anciennete ?? 0 }}">
                        </div>
                        <div class="col-md-6">
                            <x-libelle>Sursalaire</x-libelle>
                            <input type="number" name="sursalaire" step="0.01" min="0" class="form-control"
                                   value="{{ $remuneration?->sursalaire ?? 0 }}">
                        </div>
                        <div class="col-md-6">
                            <x-libelle>Bonus</x-libelle>
                            <input type="number" name="bonus" step="0.01" min="0" class="form-control"
                                   value="{{ $remuneration?->bonus ?? 0 }}">
                        </div>
                        <div class="col-md-6">
                            <x-libelle>Logement</x-libelle>
                            <input type="number" name="logement" step="0.01" min="0" class="form-control"
                                   value="{{ $remuneration?->logement ?? 0 }}">
                        </div>
                        <div class="col-md-6">
                            <x-libelle>Nourriture</x-libelle>
                            <input type="number" name="nourriture" step="0.01" min="0" class="form-control"
                                   value="{{ $remuneration?->nourriture ?? 0 }}">
                        </div>
                        <div class="col-md-6">
                            <x-libelle>Déplacement</x-libelle>
                            <input type="number" name="deplacement" step="0.01" min="0" class="form-control"
                                   value="{{ $remuneration?->deplacement ?? 0 }}">
                        </div>
                        <div class="col-md-6">
                            <x-libelle>Autre avantage</x-libelle>
                            <input type="number" name="autre_avantage" step="0.01" min="0" class="form-control"
                                   value="{{ $remuneration?->autre_avantage ?? 0 }}">
                        </div>
                    </div>

                    @if ($remuneration)
                        <div class="total-box">
                            <div class="total-row">
                                <span>Primes & avantages</span>
                                <span>{{ number_format($remuneration->total_primes, 2, ',', ' ') }}</span>
                            </div>
                            <div class="total-row grand">
                                <span>Brut total</span>
                                <span>{{ number_format($remuneration->brut_total, 2, ',', ' ') }}</span>
                            </div>
                        </div>
                    @endif

                    <div class="mt-3 text-end">
                        <x-bouton type="submit">
                            <i class="fas fa-save me-1"></i> Enregistrer
                        </x-bouton>
                    </div>
                </form>
            </x-carte>
        </div>

        {{-- Déductions & Retenues --}}
        <div class="col-lg-6">
            <x-carte>
                <div class="card-header">
                    <h6><i class="fas fa-minus-circle me-2"></i>Déductions &amp; Retenues</h6>
                </div>

                {{-- Déductions --}}
                <form method="POST" action="{{ route('rh.remunerations.storeDeduction') }}" class="form-section">
                    @csrf
                    <input type="hidden" name="employe_id" value="{{ $employe->id }}">
                    <h6 class="small text-uppercase text-muted mb-2">Déductions</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <x-libelle>CNSS</x-libelle>
                            <input type="number" name="cnss" step="0.01" min="0" class="form-control"
                                   value="{{ $deduction?->cnss ?? 0 }}">
                        </div>
                        <div class="col-md-4">
                            <x-libelle>Charges familiales</x-libelle>
                            <input type="number" name="charges_familiale" step="0.01" min="0" class="form-control"
                                   value="{{ $deduction?->charges_familiale ?? 0 }}">
                        </div>
                        <div class="col-md-4">
                            <x-libelle>Forfait professionnel</x-libelle>
                            <input type="number" name="forfait_professionnel" step="0.01" min="0" class="form-control"
                                   value="{{ $deduction?->forfait_professionnel ?? 0 }}">
                        </div>
                    </div>
                    <div class="mt-3 text-end">
                        <x-bouton type="submit">
                            <i class="fas fa-save me-1"></i> Enregistrer
                        </x-bouton>
                    </div>
                </form>

                <hr class="my-0">

                {{-- Retenues --}}
                <form method="POST" action="{{ route('rh.remunerations.storeRetenue') }}" class="form-section">
                    @csrf
                    <input type="hidden" name="employe_id" value="{{ $employe->id }}">
                    <h6 class="small text-uppercase text-muted mb-2">Retenues (IRPP)</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <x-libelle requis>IRPP</x-libelle>
                            <input type="number" name="irpp" step="0.01" min="0" class="form-control" required
                                   value="{{ $retenue?->irpp ?? 0 }}">
                        </div>
                    </div>
                    <div class="mt-3 text-end">
                        <x-bouton type="submit">
                            <i class="fas fa-save me-1"></i> Enregistrer
                        </x-bouton>
                    </div>
                </form>
            </x-carte>
        </div>
    @else
        <div class="col-12">
            <x-carte>
                <x-section class="text-center text-muted py-5">
                    <i class="fas fa-user-slash fa-2x mb-2 d-block" style="color:#d8c8ae;"></i>
                    Sélectionnez un employé pour gérer sa rémunération.
                </x-section>
            </x-carte>
        </div>
    @endif
</div>
@endsection