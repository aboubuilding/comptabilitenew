
<div class="modal fade" id="addZone" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <form class="modal-dialog modal-dialog-center" method="post" action="#" enctype="multipart/form-data" id="form">

        @csrf


        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="defaultModalLabel">New Student Deatils</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">




                    <div class="col-xl-12">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Libellé  </label>
                            <input type="text" class="form-control" id="libelle" name="libelle" ><br>




                        </div>

                        <span class="text-danger error-text libelle_error"> </span>

                    </div>

                    <div class="col-xl-12">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Voiture  </label>
                            <select class="form-control bottom15" name="voiture_id" id="voiture_id">
                                <option>Choisir un  cycle  </option>
                                @php

                                            $voitures = App\Models\Voiture::getListe();

                                            @endphp

                                            @foreach( $voitures  as $voiture )

                                                <option value="{{$voiture->id}}" >{{$voiture->plaque}}</option>


                                            @endforeach
                              </select>



                        </div>

                        <span class="text-danger error-text voiture_id_error"> </span>


                    </div>


                    <div class="col-xl-12">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Voiture  </label>
                            <select class="form-control bottom15" name="chauffeur_id" id="chauffeur_id">
                                <option>Choisir un  cycle  </option>
                                @php

                                            $chauffeurs = App\Models\Chauffeur::getListe();

                                            @endphp

                                            @foreach( $chauffeurs  as $chauffeur )

                                                <option value="{{$chauffeur->id}}" >{{$chauffeur->nom.' '.}}</option>


                                            @endforeach
                              </select>



                        </div>

                        <span class="text-danger error-text voiture_id_error"> </span>


                    </div>







                </div>
            </div>

            <input type="hidden" id="idZone">
            <div class="modal-footer">
                <button type="button" class="btn btn-danger light" id="annulerZone" >Annuler </button>
                <button type="button" class="btn btn-primary" id="ajouterZone">Ajouter  </button>
                <button type="button" class="btn btn-primary" id="updateZone">Valider </button>
            </div>
        </div>


    </form>
</div>
