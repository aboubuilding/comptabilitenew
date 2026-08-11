
<!-- Modal -->
<div class="modal fade" id="detailSouscription" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
                            <label for="exampleFormControlInput1" class="form-label">   Eleve  </label>
                            <input type="text" class="form-control" id="eleve" name="eleve" ><br>


                        </div>



                    </div>

                     <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Cycle  </label>
                            <input type="text" class="form-control" id="cycle" name="cycle" ><br>


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
                                    <th>Reference</th>
                                    <th>Payeur   </th>
                                    <th>Montant   </th>

                                      <th>Date de paiement    </th>


                                </tr>
                                </thead>
                                <tbody id="liste_details">





                                </tbody>
                            </table>


                        </div>








                    </div>

                </div>
            </div>

            <input type="hidden" id="idInscription">
            <div class="modal-footer">

                <button type="button" class="btn btn-primary" id="annulerDetail">Fermer   </button>

            </div>
        </div>


    </form>
</div>
