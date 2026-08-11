<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Reçu de paiement' }}</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 13px; color: #333; line-height: 1.4; margin:0; padding:0;">

<div style="width:95%; margin:auto;">

    {{-- En-tête --}}
    <div style="text-align:center; margin-bottom:20px;">
        <img src="{{ asset('admin/images/logo_mariam.png') }}" alt="Logo" width="80">
        <h2 style="margin:5px 0;">Reçu de paiement</h2>
        <p style="margin:2px 0;">Année scolaire : {{ $annee->libelle ?? '' }}</p>
    </div>

    {{-- Message --}}
    <p style="margin-bottom:20px;">
        {{ $body ?? '' }}
        <br><br>
        Bien cordialement,<br>
        <b>La Comptabilité</b>
    </p>

    {{-- Infos Parent/Élève --}}
    <table width="100%" cellpadding="5" cellspacing="0" border="0" style="margin-bottom:15px;">
        <tr>
            <td><strong>Parent :</strong> {{ $inscription->parent->nom_parent ?? '' }} {{ $inscription->parent->prenom_parent ?? '' }}</td>
            <td><strong>Élève :</strong> {{ $inscription->eleve->nom ?? '' }} {{ $inscription->eleve->prenom ?? '' }}</td>
        </tr>
        <tr>
            <td><strong>Niveau :</strong> {{ $inscription->niveau->libelle ?? '' }}</td>
            <td><strong>Référence paiement :</strong> {{ $paiement->reference ?? '' }}</td>
        </tr>
    </table>

    {{-- Détails paiement --}}
    <table width="100%" cellpadding="5" cellspacing="0" border="1" style="border-collapse:collapse; margin-bottom:15px;">
        <thead>
            <tr style="background-color:#f2f2f2;">
                <th>Libellé</th>
                <th>Montant payé (FCFA)</th>
                <th>Mode de paiement</th>
                <th>Numéro chèque</th>
                <th>Montant chèque</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @foreach($details as $detail)
                @php $total += $detail->montant ?? 0; @endphp
                <tr>
                    <td>{{ $detail->libelle ?? '' }}</td>
                    <td>{{ number_format($detail->montant ?? 0,0,',',' ') }}</td>
                    <td>
                        @if(($paiement->mode_paiement ?? '') === \App\Types\ModePaiement::ESPECE)
                            ESPECE
                        @elseif(($paiement->mode_paiement ?? '') === \App\Types\ModePaiement::CHEQUE)
                            CHEQUE
                        @else
                            N/A
                        @endif
                    </td>
                    <td>{{ $cheque->numero ?? 'N/A' }}</td>
                    <td>{{ number_format($cheque->montant ?? 0,0,',',' ') }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="2" style="text-align:right;"><b>Total payé :</b></td>
                <td colspan="3">{{ number_format($total,0,',',' ') }} FCFA</td>
            </tr>
        </tbody>
    </table>

    {{-- Montants supplémentaires --}}
    <p><strong>Montant scolarité :</strong> {{ number_format($montant_scolarite ?? 0,0,',',' ') }} FCFA</p>
    <p><strong>Montant déjà payé :</strong> {{ number_format($montant_deja_payer ?? 0,0,',',' ') }} FCFA</p>
    <p><strong>Reste à payer :</strong> {{ number_format($reste ?? 0,0,',',' ') }} FCFA</p>

    @if(!empty($paiement->inscription->taux_remise) && $paiement->inscription->taux_remise > 0)
        <p>Réduction appliquée : <strong>{{ $paiement->inscription->taux_remise }}%</strong>, montant final : <strong>{{ number_format($montant_scolarite - ($montant_scolarite * $paiement->inscription->taux_remise)/100,0,',',' ') }} FCFA</strong></p>
    @endif

    {{-- Responsables --}}
    <table width="100%" cellpadding="5" cellspacing="0" border="0" style="margin-top:20px;">
        <tr>
            <td><strong>Responsable saisie :</strong> {{ $paiement->utilisateur->nom ?? '' }}<br>Le {{ \Carbon\Carbon::parse($paiement->created_at ?? now())->format('d/m/Y H:i:s') }}</td>
            {{-- <td><strong>Responsable validation :</strong> {{ $utilisateur->nom ?? '' }} {{ $utilisateur->prenom ?? '' }}<br>Le {{ \Carbon\Carbon::parse($paiement->updated_at ?? now())->format('d/m/Y H:i:s') }}</td> --}}
        </tr>
    </table>

    <p style="margin-top:20px; font-weight:bold;">NB : Aucun remboursement n'est accepté après encaissement. Merci.</p>

</div>
</body>
</html>
