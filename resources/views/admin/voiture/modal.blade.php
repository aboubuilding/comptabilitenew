
<div class="modal fade" id="addVoiture" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
                            <label for="exampleFormControlInput1" class="form-label">Plaque </label>
                            <input type="text" class="form-control" id="plaque" name="plaque" ><br>




                        </div>

                        <span class="text-danger error-text plaque_error"> </span>

                    </div>

                    <div class="col-xl-12">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Marque  </label>
                            <input type="text" class="form-control" id="marque" name="marque" ><br>




                        </div>


                    </div>


                    <div class="col-xl-12">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Nombre de place  </label>
                            <input type="number" class="form-control" id="nombre_place" name="nombre_place" ><br>




                        </div>


                    </div>





                </div>
            </div>

            <input type="hidden" id="idVoiture">
            <div class="modal-footer">
                <button type="button" class="btn btn-danger light" id="annulerVoiture" >Annuler </button>
                <button type="button" class="btn btn-primary" id="ajouterVoiture">Ajouter  </button>
                <button type="button" class="btn btn-primary" id="updateVoiture">Valider </button>
            </div>
        </div>


    </form>
</div>
