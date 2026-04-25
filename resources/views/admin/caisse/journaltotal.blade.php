<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Reçu de caisse </title>
</head>

<body>
    <br><br><br>

    <style>
        * {
            margin: 0;
            padding: 0;
            border: 0;

        }

        table {
            border-collapse: collapse;
            border-spacing: 0
        }

        body {

            font-family: Courier, sans-serif;
            font-size: 10px
        }

        #container {


            width: 95%;
            margin: auto;
        }

        #header {



            padding: 10px;


        }

        #ministere,
        #logo,
        #republique {

            display: inline-block;
            vertical-align: top;



        }

        #ministere {

            width: 30%;

        }

        #logo {

            width: 35%;
        }

        #republique {

            width: 30%;

        }

        #logo p {

            text-align: center;
            margin-bottom: 5px;

        }

        #logo p img {

            height: 80px;

        }

        #logo h2 {

            text-align: center;
            font-size: 1.8em;
            margin: 5px;
            font-weight: 900;
            color: red;

        }

        #logo h4 {

            text-align: center;
            font-size: 1.3em;
            font-weight: 500;


        }

        #ministere p {
            text-transform: uppercase;
            font-size: 1.2em;
            text-align: center;
            line-height: 1.3em;
            font-weight: 900;
        }

        #republique h3,
        #republique h4 {

            text-align: right;
            line-height: 1.8em;
            font-weight: 900;
            font-size: 1.2em;
        }

        #bulletin,
        #tab_annee {

            display: inline-block;
            vertical-align: top;



        }

        #bulletin {

            width: 50%;
        }

        #tab_annee {

            width: 45%;
        }

        #bulletin h2 {

            padding: 5px;

            line-height: 1.2em;
            font-weight: 900;
            font-size: 1.6em;
            text-align: center;

            text-transform: uppercase;

            border: 2px solid red;
            width: 80%;
            margin-left: 50px;
        }

        #tab_annee>table {
            width: 60%;
            float: right;

            border: solid grey 1px
        }

        #tab_annee>table th {
            font-weight: 600;
            padding: 5px;

            border: solid grey 1px
        }

        #tab_annee>table td {

            padding: 4px;
            text-align: center;
            font-weight: 600;
            border: solid grey 1px
        }

        #eleve h2 {

            padding: 22px;
            font-weight: 900;
            font-size: 2em;
            margin-left: 20px;
            text-transform: uppercase;


        }

        #eleve h2 span {

            color: red;
            font-weight: 900;


        }

        #note>table {
            width: 95%;

            border: solid grey 1px
        }

        #note>table th {
            font-weight: 800;
            padding: 5px;

            font-size: 1.5em;

            background-color: #ddebf6;

            border: solid grey 1px
        }

        #note>table td {

            padding: 4px;
            text-align: center;
            font-weight: 600;
            font-size: 1.1em;
            border: solid grey 1px
        }

        .matiere {

            font-weight: 900;
            font-size: 1.4em;
            text-transform: uppercase;
        }

        .coefficient {

            background-color: #f8c400;
        }

        .moyenne_generale {

            color: red;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 1.8em !important;
        }


        .rang_eleve {

            color: red;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 1.5em !important;
            width: 80px;
        }



        #footer {
            padding: 10px;
        }

        #footer_1,
        #footer_2 {

            display: inline-block;
            vertical-align: top;



        }

        #footer_1 {

            width: 45%;
            font-size: 1.1em;
            font-weight: 600;
        }


        #footer_2 {

            width: 50%;
            font-size: 1.1em;
            font-weight: 600;

        }

        #footer_1 table {


            border: solid grey 1px
        }



        #footer_1 table td {

            padding: 5px;

            text-transform: uppercase;
            border: solid grey 1px
        }

        #ponctualite {

            width: 60%;

            margin-bottom: 5px;
        }

        #ponctualite td {

            width: 50%;


        }

        #appreciation td {

            width: 50%;


        }


        #appreciation {

            width: 60%;

            margin-bottom: 5px;
        }

        #discipline {

            width: 60%;

            margin-bottom: 5px;
        }

        #discipline td {

            width: 50%;


        }

        #discipline p {


            margin-bottom: 1px;
        }


        #titulaire {



            margin-bottom: 10px;
            font-weight: 900;
        }


        #footer_2 table {


            border: solid grey 1px
        }



        #footer_2 table td {

            padding: 5px;

            text-transform: uppercase;
            border: solid grey 1px
        }

        #moyenne {

            width: 85%;

            margin-bottom: 5px;
        }

        #moyenne td {

            width: 13%;


        }





        #forte_moyenne {

            width: 85%;

            margin-bottom: 5px;
        }

        #forte_moyenne td {

            width: 25%;


        }

        #footer_2 p {



            margin-top: 20px;
            font-weight: 600;
        }
    </style>
    <div id="container">
        @php
            $user_value = session()->get('LoginUser');
            $compte_id = $user_value['compte_id'];

            $utilisateur = App\Models\User::rechercheUserById($compte_id);

        @endphp
        <div id="bloc1">

            <div id="header">
                <div id="ministere">

                    <p>ECOLE INTERNATIONALE </p>
                    <p> MARIAM </p>
                    <p>---------------------------------------------</p>


                </div>
                <div id="logo">

                    <p> <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('admin/images/logo_mariam.png'))) }}"
                            alt="">
                    </p>
                    <h4> TEL:(00228 ) 70 70 93 93 / 22 55 19 30 </h4>
                    <h4> AGOE LOME </h4>
                </div>
                <div id="republique">
                    <h3>REPUBLIQUE TOGOGLAISE</h3>
                    <h4>Travail - Liberté - Patrie</h4>
                </div>
            </div>




            <div id="eleve">
                <h2>Caisse  :
                    <span>{{ $caisse->libelle  }} </span>
                </h2>

            </div>
            <div id="note">
                <table>
                    <thead>
                        <tr>
                            <th>Type de paiement  </th>
                            <th> Montant  </th>


                        </tr>

                    </thead>
                    @php
                        $total = 0;
                    @endphp
                    <tbody>

                            <tr>

                                <td style="text-align: left">Total cantine</td>
                                <td style="text-align: left">{{ $total_cantine }}</td>


                            </tr>


                            <tr>

                                <td style="text-align: left">Total bus</td>
                                <td style="text-align: left">{{ $total_bus }}</td>


                            </tr>


                            <tr>

                                <td style="text-align: left">Total scolarite</td>
                                <td style="text-align: left">{{ $total_scolarite }}</td>


                            </tr>

                            <tr>

                                <td style="text-align: left">Total inscription</td>
                                <td style="text-align: left">{{ $total_inscription }}</td>


                            </tr>


                            <tr>

                                <td style="text-align: left">Total assurance </td>
                                <td style="text-align: left">{{ $total_assurance }}</td>


                            </tr>


                            <tr>

                                <td style="text-align: left">Total location livre  </td>
                                <td style="text-align: left">{{ $total_livre }}</td>


                            </tr>


                            <tr>

                                <td style="text-align: left">Total vente d articles   </td>
                                <td style="text-align: left">{{ $total_produit }}</td>


                            </tr>



                            <tr>

                                <td style="text-align: left">Total frais d examen    </td>
                                <td style="text-align: left">{{ $total_frais_examen }}</td>


                            </tr>


                            <tr>

                                <td style="text-align: left">Total encaissements   </td>
                                <td style="text-align: left">{{ $total_encaissements }}</td>


                            </tr>





                    </tbody>
                </table>

            </div>
    </div>
    </div>



</body>

</html>
