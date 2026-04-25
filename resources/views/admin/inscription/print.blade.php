<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Étiquette Élève</title>
    <style>
        @page {
            size: 50mm 25mm;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
        }

        .label {
            width: 50mm;
            height: 25mm;
            box-sizing: border-box;
            padding: 2mm;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-family: Arial, sans-serif;
        }

        /* .label {
            transform: rotate(-90deg);
            transform-origin: left top;
            position: absolute;
            top: 0;
            left: 0;
        } */

        .text {
            /* font-size: 9pt; */
            max-width: 28mm;
        }

        .text span {
            display: inline-block;
            width: 100%;
        }

        .qrcode {
            width: 18mm;
            height: 18mm;
        }
    </style>
</head>

<body onload="printAndClose()">

    <div class="label">
        <div class="text">
            <span style="font-size: x-small"><b>{{ $inscription->eleve->nom }}</b></span><br>
            <span style="font-size: x-small; font-style: italic;">{{ $inscription->eleve->prenom }}</span><br>
            <span style="font-size: x-small">{{ $inscription->niveau->libelle }} <br><b>EIM /
                    {{ $inscription->annee->libelle }}</b></span>

        </div>
        {!! QrCode::size(70)->generate($inscription->eleve->matricule . '/' . $inscription->annee->libelle) !!}
    </div>
    <script>
        function printAndClose() {
            window.print();
            // Certains navigateurs supportent l'événement `onafterprint`
            window.onafterprint = () => {
                window.close();
            };

            // Fallback pour les navigateurs qui ne déclenchent pas onafterprint
            setTimeout(() => {
                window.close();
            }, 3000); // attendre 3 secondes avant de fermer
        }
    </script>
</body>

</html>
