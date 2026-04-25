<!-- Modal -->
<div class="modal fade" id="addClasse" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
                            <label  class="form-label d-block">Cycle </label>
                            <select class="default-select col-xl-12"  id="cycle_id" name="cycle_id">
                                <option selected value="0">Choisir un  cycle  </option>
                                @php

                                $cycles = App\Models\Cycle::getListe();

                                @endphp

                                @foreach( $cycles  as $cycle )

                                    <option value="{{$cycle->id}}" >{{$cycle->libelle}}</option>


                                @endforeach

                            </select>

                        </div>

                        <span class="text-danger error-text cycle_id_error"> </span>

                    </div>

                    <div class="col-xl-12">
                        <div class="mb-3">
                            <label  class="form-label d-block">Niveau  </label>
                            <select class="default-select col-xl-12"  id="niveau_id" name="niveau_id">
                                <option selected value="0">Choisir un  niveau   </option>

                                @php

                                $niveaus = App\Models\Niveau::getListe();

                                @endphp

                                @foreach( $niveaus  as $niveau )

                                    <option value="{{$niveau->id}}" >{{$niveau->libelle}}</option>


                                @endforeach

                            </select>

                        </div>

                        <span class="text-danger error-text niveau_id_error"> </span>

                    </div>
                    <div class="col-xl-12">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Libelle </label>
                            <input type="text" class="form-control" id="libelle" name="libelle" ><br>




                        </div>

                        <span class="text-danger error-text libelle_error"> </span>

                    </div>

                    <div class="col-xl-12">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Emplacement </label>


                            <textarea class="form-control" cols="30" rows="10" id="emplacement" name="emplacement"></textarea>




                        </div>

                        <span class="text-danger error-text description_error"> </span>

                    </div>


                </div>
            </div>

            <input type="hidden" id="idClasse">
            <div class="modal-footer">
                <button type="button" class="btn btn-danger light" id="annulerClasse" >Annuler </button>
                <button type="button" class="btn btn-primary" id="ajouterClasse">Ajouter  </button>
                <button type="button" class="btn btn-primary" id="updateClasse">Valider </button>
            </div>
        </div>


    </form>
</div>
