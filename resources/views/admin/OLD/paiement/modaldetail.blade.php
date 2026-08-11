@php use App\Types\StatutValidation; @endphp
<!-- Modal -->
<div class="modal fade" id="detailPaiement" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <form class="modal-dialog  modal-xl modal-dialog-center" method="post" action="" enctype="multipart/form-data" id="form" >

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
                            <label for="exampleFormControlInput1" class="form-label">   Référence  </label>
                            <input type="text" class="form-control" id="reference" name="reference" ><br>


                        </div>



                    </div>

                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">   Eleve   </label>
                            <input type="text" class="form-control" id="eleve" name="eleve" ><br>


                        </div>



                    </div>

                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Niveau de l' élève  </label>
                            <input type="text" class="form-control" id="niveau" name="niveau" ><br>


                        </div>



                    </div>

                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Montant total </label>
                            <input type="text" class="form-control" id="montant_total" name="montant_total" ><br>


                        </div>



                    </div>







                </div>

                <div class="row">

                    <h5 class="mb-0">Détails paiements  </h5>
                    <hr>

                    <div class="col-xl-12">

                        <div class="container">
                            <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer" id="example-student">
                                <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" class="form-check-input" id="checkAll" required="">
                                    </th>
                                    <th>Libelle</th>
                                    <th>Montant  </th>
                                    <th>Type  </th>


                                </tr>
                                </thead>
                                <tbody id="liste_details">





                                </tbody>
                            </table>


                        </div>








                    </div>

                </div>
            </div>

            <input type="hidden" id="idPaiement">
            <div class="modal-footer">

                <button type="button" class="btn btn-primary" id="fermerDetail">Fermer   </button>

            </div>
        </div>


    </form>
</div>
