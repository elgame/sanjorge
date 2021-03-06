    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <a href="<?php echo base_url('panel/areas/'); ?>">Areas</a> <span class="divider">/</span>
          </li>
          <li>Modificar</li>
        </ul>
      </div>


      <div class="row-fluid">

        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-plus"></i> Datos area</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">

            <form action="<?php echo base_url('panel/areas/modificar/?'.MyString::getVarsLink(array('msg'))); ?>" method="post" class="form-horizontal">
              <div class="control-group">
                <label class="control-label" for="fnombre">Nombre </label>
                <div class="controls">
                  <input type="text" name="fnombre" id="fnombre" class="span6" maxlength="140"
                  value="<?php echo (isset($data['info']->nombre)? $data['info']->nombre: ''); ?>" required autofocus placeholder="Limon, Piña, Insumo">
                </div>
              </div>

              <div class="control-group tipo3">
                <label class="control-label" for="ftipo">Tipo de proveedor </label>
                <div class="controls">
                  <select name="ftipo" id="ftipo">
                    <option value="fr" <?php echo set_select('ftipo', 'fr', false, (isset($data['info']->tipo)? $data['info']->tipo: '') ); ?>>Fruta</option>
                    <option value="in" <?php echo set_select('ftipo', 'in', false, (isset($data['info']->tipo)? $data['info']->tipo: '') ); ?>>Insumos</option>
                    <option value="ot" <?php echo set_select('ftipo', 'ot', false, (isset($data['info']->tipo)? $data['info']->tipo: '') ); ?>>Otros</option>
                  </select>
                </div>
              </div>

              <div class="control-group tipo3">
                <label class="control-label" for="fempresas">Empresas </label>
                <div class="controls">
                  <select name="fempresas[]" id="fempresas" multiple style="width: 50%; height: 150px">
                    <?php foreach ($empresas as $key => $value):
                      $select = false;
                      foreach ($data['empresas'] as $keye => $emp) {
                        if ($emp->id_empresa == $value->id_empresa) {
                          $select = true;
                        }
                      }
                    ?>
                    <option value="<?php echo $value->id_empresa ?>" <?php echo set_select('fempresas', $value->id_empresa, $select); ?>><?php echo $value->nombre_fiscal ?></option>
                    <?php endforeach ?>
                  </select>
                </div>
              </div>

              <div class="form-actions">
                <button type="submit" class="btn btn-primary">Guardar</button>
                <a href="<?php echo base_url('panel/areas/'); ?>" class="btn">Cancelar</a>
              </div>
            </form>

          </div>
        </div><!--/span-->

      </div><!--/row-->

      <input type="hidden" id="id_calidad" name="id_calidad" value="<?php echo $this->input->get('id'); ?>">

      <div class="row-fluid">

        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-leaf"></i> Calidades Bascula / Clasificaciones</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-down"></i></a>
            </div>
          </div>
          <div class="box-content" style="display: none;">
            <fieldset class="span6">
              <legend>Calidades Bascula <?php echo $this->usuarios_model->getLinkPrivSm('areas/agregar_calidad/', array(
                                          'params'   => 'id='.$this->input->get('id'),
                                          'btn_type' => 'btn-success pull-right')
                                      ); ?></legend>

              <form id="frm_fcalidades" action="<?php echo base_url('panel/areas/'); ?>" method="get" class="form-search">
                <div class="row-fluid">
                  <p class="span5">
                    <label for="calidades_fnombre">Buscar</label>
                    <input type="text" name="fnombre" id="calidades_fnombre" value="<?php echo set_value_get('fnombre'); ?>"
                      class="input-large search-query" placeholder="Limon, limon verde" autofocus>
                  </p>
                  <p class="span4">
                    <label for="calidades_fstatus">Estado</label>
                    <select name="fstatus" id="calidades_fstatus" class="span11">
                      <option value="t" <?php echo set_select('fstatus', 't', false, $this->input->get('fstatus')); ?>>ACTIVOS</option>
                      <option value="f" <?php echo set_select('fstatus', 'f', false, $this->input->get('fstatus')); ?>>ELIMINADOS</option>
                      <option value="todos" <?php echo set_select('fstatus', 'todos', false, $this->input->get('fstatus')); ?>>TODOS</option>
                    </select>
                  </p>
                  <input type="submit" name="enviar" value="Buscar" class="btn">
                </div>
              </form>

              <div id="content_calidades">
                <?php echo $html_calidades; ?>
              </div> <!-- /calidades -->
            </fieldset>

            <fieldset class="span6">
              <legend>Clasificaciones <?php echo $this->usuarios_model->getLinkPrivSm('areas/agregar_clasificacion/', array(
                                          'params'   => 'id='.$this->input->get('id'),
                                          'btn_type' => 'btn-success pull-right')
                                      ); ?>
                <a href="<?php echo base_url('panel/areas/clasificaciones_xls/?id='.$this->input->get('id')); ?>"
                    class="pull-right" style="font-size:12px;margin-right:3px;">
                  <i class="icon-table"></i> Catalogo</a>
              </legend>

              <form id="frm_clasificaciones" action="<?php echo base_url('panel/areas/'); ?>" method="get" class="form-search">
                <div class="row-fluid">
                  <p class="span5">
                    <label for="fnombre">Buscar</label>
                    <input type="text" name="fnombre" id="clasificaciones_fnombre" value="<?php echo set_value_get('fnombre'); ?>"
                      class="input-large search-query" placeholder="Limon, limon verde" autofocus>
                  </p>
                  <p class="span4">
                    <label for="fstatus">Estado</label>
                    <select name="fstatus" id="clasificaciones_fstatus" class="span11">
                      <option value="t" <?php echo set_select('fstatus', 't', false, $this->input->get('fstatus')); ?>>ACTIVOS</option>
                      <option value="f" <?php echo set_select('fstatus', 'f', false, $this->input->get('fstatus')); ?>>ELIMINADOS</option>
                      <option value="todos" <?php echo set_select('fstatus', 'todos', false, $this->input->get('fstatus')); ?>>TODOS</option>
                    </select>
                  </p>
                  <input type="submit" name="enviar" value="Buscar" class="btn">
                </div>
              </form>

              <div id="content_clasificacion">
                <?php echo $html_clasificaciones; ?>
              </div> <!-- /clasificacion -->
            </fieldset>

          </div>
        </div><!--/span-->

      </div><!--/row-->

      <div class="row-fluid">

        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-leaf"></i> Calidades Ventas / Tamaños</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <fieldset class="span6">
              <legend>Calidades Ventas <?php echo $this->usuarios_model->getLinkPrivSm('areas_otros/agregar_calidad/', array(
                                          'params'   => 'id='.$this->input->get('id'),
                                          'btn_type' => 'btn-success pull-right')
                                      ); ?></legend>

              <form id="frm_fcalidades_ventas" action="<?php echo base_url('panel/areas/'); ?>" method="get" class="form-search">
                <div class="row-fluid">
                  <p class="span5">
                    <label for="calidades_fnombre">Buscar</label>
                    <input type="text" name="fnombre" id="calidades_ventas_fnombre" value="<?php echo set_value_get('fnombre'); ?>"
                      class="input-large search-query" placeholder="300, PREM, EXT" autofocus>
                  </p>
                  <p class="span4">
                    <label for="calidades_fstatus">Estado</label>
                    <select name="fstatus" id="calidades_ventas_fstatus" class="span11">
                      <option value="t" <?php echo set_select('fstatus', 't', false, $this->input->get('fstatus')); ?>>ACTIVOS</option>
                      <option value="f" <?php echo set_select('fstatus', 'f', false, $this->input->get('fstatus')); ?>>ELIMINADOS</option>
                      <option value="todos" <?php echo set_select('fstatus', 'todos', false, $this->input->get('fstatus')); ?>>TODOS</option>
                    </select>
                  </p>
                  <input type="submit" name="enviar" value="Buscar" class="btn">
                </div>
              </form>

              <div id="content_calidades_ventas">
                <?php echo $html_calidades_ventas; ?>
              </div> <!-- /calidades -->
            </fieldset>

            <fieldset class="span6">
              <legend>Tamaños <?php echo $this->usuarios_model->getLinkPrivSm('areas_otros/agregar_tamano/', array(
                                          'params'   => 'id='.$this->input->get('id'),
                                          'btn_type' => 'btn-success pull-right')
                                      ); ?>
                <!-- <a href="<?php echo base_url('panel/areas_otros/clasificaciones_xls/?id='.$this->input->get('id')); ?>"
                    class="pull-right" style="font-size:12px;margin-right:3px;">
                  <i class="icon-table"></i> Catalogo</a> -->
              </legend>

              <form id="frm_tamanios" action="<?php echo base_url('panel/areas/'); ?>" method="get" class="form-search">
                <div class="row-fluid">
                  <p class="span5">
                    <label for="fnombre">Buscar</label>
                    <input type="text" name="fnombre" id="tamanios_fnombre" value="<?php echo set_value_get('fnombre'); ?>"
                      class="input-large search-query" placeholder="VDE # 5G, ECO-VDE # 2, No.5" autofocus>
                  </p>
                  <p class="span4">
                    <label for="fstatus">Estado</label>
                    <select name="fstatus" id="tamanios_fstatus" class="span11">
                      <option value="t" <?php echo set_select('fstatus', 't', false, $this->input->get('fstatus')); ?>>ACTIVOS</option>
                      <option value="f" <?php echo set_select('fstatus', 'f', false, $this->input->get('fstatus')); ?>>ELIMINADOS</option>
                      <option value="todos" <?php echo set_select('fstatus', 'todos', false, $this->input->get('fstatus')); ?>>TODOS</option>
                    </select>
                  </p>
                  <input type="submit" name="enviar" value="Buscar" class="btn">
                </div>
              </form>

              <div id="content_tamanios">
                <?php echo $html_tamanos_ventas; ?>
              </div> <!-- /clasificacion -->
            </fieldset>

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


