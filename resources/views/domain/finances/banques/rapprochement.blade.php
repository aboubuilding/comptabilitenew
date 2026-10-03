@extends('admin.layouts.app')

@section('title', 'Rapprochement bancaire — ' . $banque->nom)
@section('page_icon', 'fa-file-import')
@section('page_title', 'Rapprochement bancaire')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('finances.banques.index') }}">Banques</a></li>
    <li class="breadcrumb-item"><a href="{{ route('finances.banques.show', $banque) }}">{{ $banque->nom }}</a></li>
    <li class="breadcrumb-item active">Rapprochement</li>
@endsection

@section('page_actions')
    <a href="{{ route('finances.banques.show', $banque) }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Retour
    </a>
@endsection

@section('css')
<style>
    .form-section { background:#faf6ee; border:1px solid #ece4d6; border-radius:12px; padding:1.25rem; }
    .form-section-title { font-size:.76rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--school-red); margin-bottom:.75rem; display:flex; align-items:center; gap:6px; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color: var(--school-ink); }
    .form-section .form-control { border-radius:8px; border:1px solid #ece4d6; padding:.55rem .75rem; font-size:.88rem; }
    .form-section .form-control:focus { border-color:var(--school-red); box-shadow: 0 0 0 .2rem rgba(200,30,58,.12); }

    .main-card { border:none; border-radius:16px; box-shadow: 0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom: 1px solid #ece4d6 !important; }

    .hint-box { background: #f0f6ff; border-left: 3px solid #2563eb; padding: .9rem 1rem; border-radius: 8px; font-size: .85rem; color: #1e3a8a; }
    .hint-box code { background: rgba(37,99,235,.10); padding: 1px 5px; border-radius: 4px; font-size: .8rem; }

    .btn-primary-red { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
    .btn-primary-red:hover { color:#fff; box-shadow: 0 4px 12px rgba(200,30,58,.3); }
</style>
@endsection

@section('contenu')
<div class="row g-4">
    <div class="col-md-8">
        <div class="card main-card">
            <div class="card-header bg-white py-3 px-4">
                <h6 class="mb-0" style="color: var(--school-ink);">
                    <i class="fas fa-file-import me-1" style="color: var(--school-gold-dark);"></i>
                    Importer un relevé bancaire
                </h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('finances.banques.rapprochement.analyser', $banque) }}"
                      method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="form-section mb-3">
                        <div class="form-section-title">
                            <i class="fas fa-file-excel"></i> Fichier du relevé (CSV ou Excel)
                        </div>
                        <input type="file" name="fichier" class="form-control" accept=".csv,.xlsx,.xls" required>
                        <div class="form-text small mt-2">
                            Taille maximale : 5 Mo. Formats acceptés : CSV, XLSX, XLS.
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('finances.banques.show', $banque) }}" class="btn btn-light">Annuler</a>
                        <button type="submit" class="btn btn-primary-red">
                            <i class="fas fa-search me-1"></i> Analyser le relevé
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="hint-box">
            <div class="fw-semibold mb-2"><i class="fas fa-info-circle me-1"></i> Format attendu</div>
            <p class="mb-2 small">Le fichier doit contenir une première ligne d'en-têtes avec au minimum :</p>
            <ul class="mb-2 small ps-3">
                <li><code>numero</code> ou <code>numero_cheque</code></li>
                <li><code>date</code> ou <code>date_encaissement</code></li>
                <li><code>montant</code></li>
                <li><code>libelle</code> (optionnel)</li>
            </ul>
            <p class="small mb-0">
                Les dates au format <code>YYYY-MM-DD</code> ou <code>DD/MM/YYYY</code> sont acceptées.
                Les lignes malformées sont ignorées automatiquement.
            </p>
        </div>
    </div>
</div>
@endsection