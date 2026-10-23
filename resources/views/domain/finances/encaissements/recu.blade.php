{{-- resources/views/domain/finances/encaissements/recu.blade.php --}}
{{--
    Reçu d'un paiement encaissé, imprimé en double sur une seule feuille A4 :
    l'original (payeur) en haut, le duplicata (école) en bas, séparés par un
    pointillé de découpe. Chaque exemplaire occupe une demi-page.
--}}
@php
    // Tous les détails encaissés du paiement : le reçu couvre l'ensemble du paiement
    $detailsAffiches = $paiement->details
        ->where('etat', 1)
        ->where('statut_paiement', \App\Domain\Finances\Types\PaiementStatut::ENCAISSE);
    $total = $detailsAffiches->sum('montant');
    $monnaie = $paiement->montant_recu !== null && $paiement->montant_recu > $total ? (float) $paiement->montant_recu - $total : 0;
    $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ');
    // Au-delà de quelques lignes, un exemplaire ne tient plus dans une demi-page : chacun prend alors la sienne
    $demiPage = $detailsAffiches->count() <= 14;
    // Logo de l'école, intégré au PDF (aucun accès réseau ni fichier à l'impression)
    $cheminLogo = collect([public_path('images/logo/eim-compta-sceau.png'), public_path('images/logo-mariam.png')])->first(fn ($c) => is_file($c));
    $logo = $cheminLogo ? 'data:image/png;base64,' . base64_encode(file_get_contents($cheminLogo)) : null;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu — {{ $paiement->reference }}</title>
    <style>
        @page { margin: 7mm 9mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #2B1E1E; margin: 0; }
        table { border-collapse: collapse; width: 100%; }

        .exemplaire { position: relative; }
        .exemplaire.demi { height: 134mm; overflow: hidden; }
        .exemplaire.seul { page-break-after: always; }
        .decoupe { border-top: 1px dashed #7A6A6A; margin: 3mm 0 4mm; position: relative; height: 0; }
        .decoupe span { position: absolute; top: -6px; left: 45%; background: #fff; padding: 0 6px; font-size: 7px; color: #7A6A6A; }

        /* En-tête : école à gauche, titre et référence à droite */
        .entete td { vertical-align: middle; }
        .entete td.logo { width: 18mm; padding: 0 6px 4px 0; }
        .entete td.logo img { width: 17mm; height: 17mm; }
        .entete { border-bottom: 2px solid #D4A94D; margin-bottom: 6px; }
        .ecole { font-size: 15px; font-weight: bold; color: #E31E24; letter-spacing: .4px; }
        .ecole-sous { font-size: 8.5px; color: #5A4A4A; }
        .titre { text-align: right; padding-bottom: 4px; }
        .titre .nom { font-size: 14px; font-weight: bold; letter-spacing: 1.5px; }
        .titre .reference { font-family: monospace; font-size: 11px; color: #B3171C; font-weight: bold; }
        .mention { display: inline-block; font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px;
                   color: #fff; background: #B3171C; padding: 2px 6px; border-radius: 3px; margin-bottom: 2px; }

        /* Payeur / encaissement */
        .infos { margin-bottom: 8px; }
        .infos > tbody > tr > td { width: 50%; vertical-align: top; padding: 0 3px; }
        .bloc { background: #FBF4E6; border: 1px solid #EADFCB; border-radius: 5px; padding: 6px 9px; }
        .bloc h3 { font-size: 8px; text-transform: uppercase; color: #5A4A4A; margin: 0 0 2px; letter-spacing: .5px; }
        .bloc td { padding: 1.5px 0; font-size: 10px; }
        .bloc td.l { color: #5A4A4A; width: 32%; }
        .bloc td.v { font-weight: bold; text-align: right; }

        /* Lignes encaissées */
        table.lignes { margin-bottom: 8px; }
        table.lignes th { background: #2B1E1E; color: #fff; font-size: 8px; text-transform: uppercase; letter-spacing: .3px;
                          padding: 4px 7px; text-align: left; font-weight: normal; }
        table.lignes td { padding: 4px 7px; border-bottom: 1px solid #EADFCB; font-size: 10px; vertical-align: top; }
        table.lignes .r { text-align: right; }
        table.lignes .sub { font-size: 8px; color: #5A4A4A; }

        /* Signatures à gauche, totaux à droite */
        .pied > tbody > tr > td { vertical-align: top; }
        .signature { width: 29%; text-align: center; font-size: 8.5px; color: #5A4A4A; padding: 34px 8px 0; }
        .signature .trait { border-top: 1px solid #2B1E1E; margin: 0 10px 2px; }
        .totaux td { padding: 2px 7px; font-size: 10px; text-align: right; }
        .totaux td.l { color: #5A4A4A; }
        .totaux td.v { font-weight: bold; width: 110px; }
        .totaux tr.final td { border-top: 2px solid #E31E24; font-size: 14px; color: #B3171C; padding-top: 4px; }

        .bas { margin-top: 8px; padding-top: 4px; border-top: 1px solid #EADFCB; font-size: 7.5px; color: #5A4A4A; }
        .bas td.d { text-align: right; }
    </style>
</head>
<body>
@foreach (['Original — exemplaire du payeur', 'Duplicata — exemplaire de l\'école'] as $exemplaire)
<div class="exemplaire {{ $demiPage ? 'demi' : ($loop->last ? '' : 'seul') }}">
    <table class="entete">
        <tr>
            @if ($logo)
                <td class="logo"><img src="{{ $logo }}" alt="EIM Compta – École Internationale Mariam"></td>
            @endif
            <td>
                <div class="ecole">ÉCOLE INTERNATIONALE MARIAM</div>
                <div class="ecole-sous">Plateforme de gestion scolaire et budgétaire</div>
            </td>
            <td class="titre">
                <div class="mention exemplaire-mention">{{ $exemplaire }}</div>
                <div class="nom">REÇU DE PAIEMENT</div>
                <div class="reference">{{ $paiement->reference ?? '—' }}</div>
            </td>
        </tr>
    </table>

    <table class="infos">
        <tr>
            <td>
                <div class="bloc">
                    <h3>Payeur</h3>
                    <table>
                        @if (! empty($eleve))
                            <tr><td class="l">Élève</td><td class="v">{{ $eleve->nom }} {{ $eleve->prenom }} ({{ $eleve->matricule }})</td></tr>
                        @endif
                        <tr><td class="l">Nom</td><td class="v">{{ $paiement->payeur ?? '—' }}</td></tr>
                        @if ($paiement->telephone_payeur)
                            <tr><td class="l">Téléphone</td><td class="v">{{ $paiement->telephone_payeur }}</td></tr>
                        @endif
                    </table>
                </div>
            </td>
            <td>
                <div class="bloc">
                    <h3>Encaissement</h3>
                    <table>
                        <tr><td class="l">Date</td><td class="v">{{ optional($detail->date_encaissement)->format('d/m/Y') ?? '—' }}</td></tr>
                        <tr><td class="l">Caisse</td><td class="v">{{ $caisse?->libelle ?? '—' }}</td></tr>
                        <tr><td class="l">Mode</td><td class="v">{{ $paiement->mode_paiement?->label() ?? '—' }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table class="lignes">
        <thead>
            <tr>
                <th style="width:58%;">Désignation</th>
                <th style="width:20%;">Nature</th>
                <th class="r" style="width:22%;">Montant (F)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($detailsAffiches as $d)
                <tr>
                    <td>
                        {{ $d->libelle }}
                        @if ($d->quantite && $d->prix_unitaire)
                            <span class="sub">— {{ number_format((float) $d->quantite, 0, ',', ' ') }} × {{ $fmt($d->prix_unitaire) }}</span>
                        @endif
                    </td>
                    <td>{{ $d->type_paiement?->label() ?? '—' }}</td>
                    <td class="r">{{ $fmt($d->montant) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="pied">
        <tr>
            <td class="signature">
                <div class="trait"></div>Le payeur
            </td>
            <td class="signature">
                <div class="trait"></div>Le caissier
                @if ($detail->caissier)<br>{{ $detail->caissier->name }}@endif
            </td>
            <td style="width:42%;">
                <table class="totaux">
                    <tr class="final"><td class="l">TOTAL ENCAISSÉ</td><td class="v">{{ $fmt($total) }} F</td></tr>
                    @if ($paiement->montant_recu !== null)
                        <tr><td class="l">Montant reçu</td><td class="v">{{ $fmt($paiement->montant_recu) }} F</td></tr>
                        @if ($monnaie > 0)
                            <tr><td class="l">Monnaie rendue</td><td class="v">{{ $fmt($monnaie) }} F</td></tr>
                        @endif
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <table class="bas">
        <tr>
            <td>Document officiel de l'École Internationale Mariam, à conserver. Toute reproduction ou falsification est interdite.</td>
            <td class="d">Généré le {{ $genereLe->format('d/m/Y à H:i') }}@if ($generePar) par {{ $generePar->name }}@endif</td>
        </tr>
    </table>
</div>
@if ($demiPage && ! $loop->last)
    <div class="decoupe"><span>✂ découper ici</span></div>
@endif
@endforeach
</body>
</html>
