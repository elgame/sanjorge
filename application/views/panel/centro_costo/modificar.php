    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <a href="<?php echo base_url('panel/centro_costo/'); ?>">Centros de costos</a> <span class="divider">/</span>
          </li>
          <li>Modificar</li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-edit"></i> Modificar centro de costo</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/centro_costo/modificar/?id='.$_GET['id']); ?>" method="post" class="form-horizontal">
              <fieldset>
                <legend></legend>

                <?php $data = $centro_costo['info']; ?>

                <div class="span12">

                  <div class="control-group">
                    <label class="control-label" for="nombre">Nombre </label>
                    <div class="controls">
                      <input type="text" name="nombre" id="nombre" class="span10" maxlength="100"
                      value="<?php echo isset($data->nombre)? $data->nombre:''; ?>" required placeholder="centro de costo">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="tipo">Tipo </label>
                    <div class="controls">
                      <select name="tipo" id="tipo">
                        <option value="gasto" <?php echo set_select('tipo', 'gasto', false, (isset($data->tipo)? $data->tipo: '')) ?>>Gasto</option>
                        <option value="servicio" <?php echo set_select('tipo', 'servicio', false, (isset($data->tipo)? $data->tipo: '')) ?>>Servicio</option>
                        <option value="banco" <?php echo set_select('tipo', 'banco', false, (isset($data->tipo)? $data->tipo: '')) ?>>Banco</option>
                        <option value="melga" <?php echo set_select('tipo', 'melga', false, (isset($data->tipo)? $data->tipo: '')) ?>>Melga</option>
                        <option value="tabla" <?php echo set_select('tipo', 'tabla', false, (isset($data->tipo)? $data->tipo: '')) ?>>Tabla</option>
                        <option value="seccion" <?php echo set_select('tipo', 'seccion', false, (isset($data->tipo)? $data->tipo: '')) ?>>Sección</option>
                      </select>
                    </div>
                  </div>

                  <?php
                    $show_lote = 'hide';
                    if ($data->tipo == 'melga' || $data->tipo == 'tabla' || $data->tipo == 'seccion') {
                      $show_lote = '';
                    }
                  ?>
                  <div id="is_lotes" class="<?php echo $show_lote ?>">
                    <div class="control-group">
                      <label class="control-label" for="farea">Cultivo </label>
                      <div class="controls">
                      <input type="text" name="farea" id="farea" class="span10" value="<?php echo isset($data->area)? $data->area->nombre:''; ?>" placeholder="Limon, Piña">
                      <input type="hidden" name="did_area" value="<?php echo isset($data->area)? $data->area->id_area:''; ?>" id="did_area">
                      </div>
                    </div>

                    <div class="control-group">
                      <label class="control-label" for="hectareas">Hectáreas </label>
                      <div class="controls">
                        <input type="number" step="any" name="hectareas" id="hectareas" class="span10" maxlength="100"
                        value="<?php echo isset($data->hectareas)? $data->hectareas:''; ?>" placeholder="5, 6">
                      </div>
                    </div>

                    <div class="control-group">
                      <label class="control-label" for="no_plantas">No de plantas </label>
                      <div class="controls">
                        <input type="number" step="any" name="no_plantas" id="no_plantas" class="span10" maxlength="100"
                        value="<?php echo isset($data->no_plantas)? $data->no_plantas:''; ?>" placeholder="100, 500">
                      </div>
                    </div>
                  </div>

                </div> <!--/span-->

                <div class="clearfix"></div>

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary">Guardar</button>
                  <a href="<?php echo base_url('panel/centro_costo/'); ?>" class="btn">Cancelar</a>
                </div>
              </fieldset>

            </form>

          </div>
        </div><!--/span-->

      </div><!--/row-->


          <!-- content ends -->
    </div><!--/#content.span10-->



<!-- Bloque de alertas -->
<?php if(isset($frm_errors)){
  if($frm_errors['msg'] != ''){
?>
<script type="text/javascript" charset="UTF-8">
  $(document).ready(function(){
    noty({"text":"<?php echo $frm_errors['msg']; ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>"});
  });
</script>
<?php }
}?>
<!-- Bloque de alertas -->


