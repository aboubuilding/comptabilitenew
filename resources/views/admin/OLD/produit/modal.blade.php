<!-- Modal -->
<div class="modal fade" id="addProduit" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
                            <label for="exampleFormControlInput1" class="form-label">Libelle </label>
                            <input type="text" class="form-control" id="libelle" name="libelle" ><br>




                        </div>

                        <span class="text-danger error-text libelle_error"> </span>

                    </div>

                    <div class="col-xl-12">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Prix   unitaire </label>
                            <input type="number" class="form-control" id="prix_unitaire" name="prix_unitaire" ><br>


                        </div>

                        <span class="text-danger error-text prix_unitaire_error"> </span>

                    </div>



                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Type de produit</label>

                            <select class="form-control bottom15" name="type_produit" id="type_produit">
                                <option value=0>Choisir un  type de produit   </option>
                                <option value=1>Produits stockables   </option>
                                <option value=2>Services </option>

                              </select>

                        </div>

                        <span class="text-danger error-text type_produit_error"> </span>

                    </div>


                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Unité d achat </label>

                            <input type="text" class="form-control" id="unite_achat" name="unite_achat" ><br>

                        </div>

                        <span class="text-danger error-text unite_achat_error"> </span>

                    </div>


                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Unité de stock </label>

                            <input type="text" class="form-control" id="unite_stock" name="unite_stock" ><br>

                        </div>

                        <span class="text-danger error-text unite_stock_error"> </span>

                    </div>


                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Equivalence </label>

                            <input type="text" class="form-control" id="equivalence" name="equivalence" ><br>

                        </div>

                        <span class="text-danger error-text equivalence_error"> </span>

                    </div>









                </div>
            </div>

            <input type="hidden" id="idProduit">
            <div class="modal-footer">
                <button type="button" class="btn btn-danger light" id="annulerProduit" >Annuler </button>
                <button type="button" class="btn btn-primary" id="ajouterProduit">Ajouter  </button>
                <button type="button" class="btn btn-primary" id="updateProduit">Valider </button>
            </div>
        </div>


    </form>
</div>
