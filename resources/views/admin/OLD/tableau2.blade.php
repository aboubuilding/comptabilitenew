<div id="search_id">
    <div class="card counter">

        <form action="">
            <h4 style="text-align: center; margin:15px"> Détail du jour </h4>
            <div class="row">
                <div class="col-md-1"></div>
                <div class="col-md-10">
                    <input type="date" name="search_date" id="search_date" placeholder="Rechercher..."
                        value="{{ $search_date }}" class="form-control">
                </div>
                <div class="col-md-1"></div>
            </div>
        </form>
        <hr>
        <div class="card-body  ">
            <div class="basic-list-group">
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center"
                        style="color: black; font-size:16px">

                        Scolarité <span class="badge badge-primary badge-pill">
                            {{ number_format($total_jour_scolarite, 0, ',', ' ') }}

                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center"
                        style="color: black; font-size:16px">
                        Cantine <span class="badge badge-primary badge-pill">
                            {{ number_format($total_jour_cantine, 0, ',', ' ') }}

                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center"
                        style="color: black; font-size:16px">
                        Bus <span class="badge badge-primary badge-pill">


                            {{ number_format($total_jour_bus, 0, ',', ' ') }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center"
                        style="color: black; font-size:16px">
                        Inscriptions <span class="badge badge-primary badge-pill">

                            {{ number_format($total_jour_inscription, 0, ',', ' ') }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center"
                        style="color: black; font-size:16px">
                        Assurance <span class="badge badge-primary badge-pill">

                            {{ number_format($total_jour_assurance, 0, ',', ' ') }}
                        </span>
                    </li>

                    <li class="list-group-item d-flex justify-content-between align-items-center"
                        style="color: black; font-size:16px">
                        Examens <span class="badge badge-primary badge-pill">

                            {{ number_format($total_jour_frais_examen, 0, ',', ' ') }}
                        </span>
                    </li>

                    <li class="list-group-item d-flex justify-content-between align-items-center"
                        style="color: black; font-size:16px">
                        Produits <span class="badge badge-primary badge-pill">

                            {{ number_format($total_jour_produit, 0, ',', ' ') }}

                        </span>
                    </li>

                    <li class="list-group-item d-flex justify-content-between align-items-center"
                        style="color: black; font-size:16px">
                        Livres <span class="badge badge-primary badge-pill">
                            {{ number_format($total_jour_livre, 0, ',', ' ') }}



                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
