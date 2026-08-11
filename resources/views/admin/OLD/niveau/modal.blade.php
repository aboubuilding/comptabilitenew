

<div class="modal fade modal-m" tabindex="-1" role="dialog"  id="addNiveau">
                        <div class="modal-dialog ">
                          <div class="modal-content">
                            <div class="modal-header">
                              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                              <h4 class="modal-title" id="defaultModalLabel">Modal title</h4>
                            </div>
                            <div class="modal-body">


                              <form  class="form-horizontal m-t-xs"  id="form">


        @csrf



                                 <div class="form-group">
                <label for="inputEmail3" class="col-sm-2 control-label">Libelle</label>
                <div class="col-sm-10">
                  <input class="form-control" id="libelle" name="libelle"  placeholder="" type="texte">
                  <p class="text-danger error-text libelle_error">Example block-level help text here.</p>
                </div>
              </div>


               <div class="form-group">
                <label for="inputEmail3" class="col-sm-2 control-label">Cycle  </label>
                <div class="col-sm-10">


                   <select class="form-control bottom15" name="cycle_id" id="cycle_id">
                    <option>Choisir un  cycle  </option>
                    @php

                                $cycles = App\Models\Cycle::getListe();

                                @endphp

                                @foreach( $cycles  as $cycle )

                                    <option value="{{$cycle->id}}" >{{$cycle->libelle}}</option>


                                @endforeach
                  </select>
                 



                  <p class="text-danger error-text cycle_id_error">Example block-level help text here.</p>
                </div>
              </div>



             
                                


                              </form>


                                 

                   


                </div>


                           
            <input type="hidden" id="idNiveau">
                            <div class="modal-footer">
                              <button type="button" class="btn btn-danger light" id="annulerNiveau" >Annuler </button>
                <button type="button" class="btn btn-primary" id="ajouterNiveau">Ajouter  </button>
                <button type="button" class="btn btn-primary" id="updateNiveau">Valider </button>
                            </div>
                          </div>
                        </div>
                      </div>