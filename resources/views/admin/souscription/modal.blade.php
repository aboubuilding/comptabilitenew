
<!-- Modal -->
<div class="modal fade" id="modifierOffre" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <form class="modal-dialog modal-dialog-center" method="post" action="" enctype="multipart/form-data" id="form" >

        @csrf


        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="defaultModalLabel">New Student Deatils</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">


                    <hr>

                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">   Eleve   </label>
                            <input type="text" class="form-control" id="eleve" name="eleve" ><br>


                        </div>



                    </div>

                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">   Niveau   </label>
                            <input type="text" class="form-control" id="niveau" name="niveau" ><br>


                        </div>



                    </div>

                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Cycle   </label>
                            <input type="text" class="form-control" id="cycle" name="cycle" ><br>


                        </div>



                    </div>

                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Offre  </label>


                            <select class="form-control bottom15" name="frais_ecole_id" id="frais_ecole_id">

                                
                    <option>Choisir une offre   </option>
                    @php

                                $offres = App\Models\FraisEcole::getListe();

                                @endphp

                                @foreach( $offres  as $offre )

                                    <option value="{{$offre->id}}" >{{$offre->libelle}}</option>


                                @endforeach
                  </select>
                 
                            




                            <br>


                        </div>



                    </div>

                    





                </div>
            </div>

            <input type="hidden" id="idInscription">
            <div class="modal-footer">
                <button type="button" class="btn btn-danger light" id="annulerOffre" >Annuler </button>
                <button type="button" class="btn btn-primary" id="validerModifier">Ajouter  </button>

            </div>
        </div>


    </form>
</div>
