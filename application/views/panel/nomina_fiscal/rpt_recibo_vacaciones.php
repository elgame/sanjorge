    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/nomina_fiscal/recibo_vacaciones_pdf/'); ?>" method="GET" id="form-search" class="form-search" target="rdeReporte">
              <div class="form-actions form-filters">

                <div class="control-group">
                  <label class="control-label" for="ftrabajador">Trabajador</label>
                  <div class="controls">
                    <input type="text" name="ftrabajador" class="span12" id="ftrabajador" value="" required>
                    <input type="hidden" name="fid_trabajador" class="span12" id="fid_trabajador" value="" required>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fsalario_real">Salario Diaro</label>
                  <div class="controls">
                    <input type="text" name="fsalario_real" class="span12 vpositive" id="fsalario_real" value="">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fdias">Dias</label>
                  <div class="controls">
                    <input type="text" name="fdias" class="span12 vpositive" id="fdias" value="0">
                  </div>
                </div>

                <div class="form-actions">
                  <button type="submit" id="btn_submit" class="btn btn-primary btn-large span12">Enviar</button>
                </div>

              </div>
            </form> <!-- /form -->
          </div>
        </div><!--/span12 -->
      </div><!--/row-fluid -->
    </div><!-- /span2 -->

    <div id="content" class="span9">
      <!-- content starts -->

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <div class="row-fluid">
              <iframe name="rdeReporte" id="iframe-reporte" class="span12" src="<?php echo base_url('panel/nomina_fiscal/recibo_vacaciones_pdf/')?>" style="height:520px;"></iframe>
            </div>
          </div>
        </div><!--/span-->
      </div><!--/row-->
          <!-- content ends -->
    </div><!--/#content.span10-->