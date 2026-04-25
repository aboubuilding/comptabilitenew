<!-- Modal -->
<div class="modal fade" id="addUtilisateur" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <form class="modal-dialog modal-dialog-center" method="post" action="{{route('admin_utilisateur_store')}}" enctype="multipart/form-data" id="form">

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
                            <label for="exampleFormControlInput1" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="nom" name="nom" ><br>




                        </div>

                        <span class="text-danger error-text nom_error"> </span>

                    </div>

                    <div class="col-xl-12">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="prenom" name="prenom" ><br>




                        </div>

                        <span class="text-danger error-text prenom_error"> </span>

                    </div>

                    <div class="col-xl-12">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Login</label>
                            <input type="text" class="form-control" id="login" name="login" ><br>




                        </div>

                        <span class="text-danger error-text login_error"> </span>

                    </div>


                    <div class="col-xl-12">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Mot de passe </label>
                            <input type="password" class="form-control" id="mot_passe" name="mot_passe" ><br>

                            <span class="text-danger error-text mot_passe_error"> </span>


                        </div>

                    </div>


                    <div class="col-xl-12">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Email </label>
                            <input type="email" class="form-control" id="email" name="email" ><br>




                        </div>

                        <span class="text-danger error-text email_error"> </span>

                    </div>


                    <div class="col-xl-12">
                        <div class="mb-3">
                            <label  class="form-label d-block">Role</label>
                            <select class="default-select col-xl-12"  id="role" name="role">
                                <option selected>Choisir un  role </option>role
                                <option value="{{\App\Types\Role::ADMIN}}">Administrateur </option>
                                <option value="{{\App\Types\Role::CAISSIER}}">Caissier </option>
                                <option value="{{\App\Types\Role::DIRECTEUR}}">Directeur  </option>

                                <option value="{{\App\Types\Role::COMPTABLE}}">Comptable </option>
                                <option value="{{\App\Types\Role::CHEFCOMPTABLE}}">Chef comptable </option>
                                <option value="{{\App\Types\Role::ECONOME}}">Econome </option>

                            </select>

                        </div>

                        <span class="text-danger error-text role_error"> </span>

                    </div>
                </div>
            </div>

            <input type="hidden" id="idUtilisateur">
            <div class="modal-footer">
                <button type="button" class="btn btn-danger light" id="annulerUtilisateur" >Annuler </button>
                <button type="button" class="btn btn-primary" id="ajouterUtilisateur">Ajouter  </button>
                <button type="button" class="btn btn-primary" id="updateUtilisateur">Valider </button>
            </div>
        </div>


    </form>
</div>
