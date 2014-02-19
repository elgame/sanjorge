    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <a href="<?php echo base_url('panel/nomina_fiscal/'); ?>">Nomina Fiscal</a> <span class="divider">/</span>
          </li>
          <li>
            Asistencia
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-file"></i> Asistencia</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/nomina_fiscal/asistencia'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters">
                <label for="ffecha1" style="margin-top: 15px;">Semana</label>
                <select name="semana" class="input-xlarge">
                  <?php foreach ($semanasDelAno as $semana) {
                      if ($semana['semana'] == $numSemanaSelected) {
                        $semana2 =  $semana;
                      }
                    ?>
                    <option value="<?php echo $semana['semana'] ?>" <?php echo $semana['semana'] == $numSemanaSelected ? 'selected' : '' ?>><?php echo "{$semana['semana']} - Del {$semana['fecha_inicio']} Al {$semana['fecha_final']}" ?></option>
                  <?php } ?>
                </select>

                <label for="empresa">Empresa</label>
                <input type="text" name="empresa" class="input-xlarge search-query" id="empresa" value="<?php echo set_value_get('empresa', $empresaDefault->nombre_fiscal); ?>" size="73">
                <input type="hidden" name="empresaId" id="empresaId" value="<?php echo set_value_get('empresaId', $empresaDefault->id_empresa); ?>">

                <label for="ffecha1" style="margin-top: 15px;">Departamento</label>
                  <select name="puestoId" class="input-large">
                    <option value=""></option>
                  <?php foreach ($puestos as $puesto) { ?>
                    <option value="<?php echo $puesto->id_departamento ?>" <?php echo set_select_get('puestoId', $puesto->id_departamento) ?>><?php echo $puesto->nombre ?></option>
                  <?php } ?>
                </select>

                <input type="submit" name="enviar" value="Buscar" class="btn">
              </div>
            </form>


            <form action="<?php echo base_url('panel/nomina_fiscal/addAsistencias/?'.String::getVarsLink(array('msg'))); ?>" method="POST" class="form">

              <div class="row-fluid">
                <div class="span4">
                  <span class="label" style="background-color: green; text-shadow: none; border: 1px rgb(114, 114, 99) solid;">Asistencia</span>
                  <span class="label" style="background-color: red; text-shadow: none; border: 1px rgb(114, 114, 99) solid;">Falta</span>
                  <span class="label" style="background-color: yellow; color: black; text-shadow: none; border: 1px rgb(114, 114, 99) solid;">Incapacidad</span>
                </div>
                <div class="span6">
                  <div style="font-size: 1.5em;"><?php echo "Semana <span class=\"label\" style=\"font-size: 1em;\">{$semana2['semana']}</span> - Del <span style=\"font-weight: bold;\">{$semana2['fecha_inicio']}</span> Al <span style=\"font-weight: bold;\">{$semana2['fecha_final']}</span>" ?></div>
                </div>
                <div class="span2">
                  <button type="submit" name="guardar" class="btn btn-success" style="float: right;">Guardar</button>
                </div>
              </div>

              <input type="hidden" value="<?php echo $numSemanaSelected?>" name="numSemana">
              <?php
                foreach ($puestos as $puesto) {
                  $tuvoEmpleados = false;
                  if ( ! isset($_GET['puestoId']) || ($_GET['puestoId'] == $puesto->id_departamento || $_GET['puestoId'] == '')) {
                ?>
                    <table class="table table-striped table-bordered bootstrap-datatable">
                      <caption style="text-align: left;"><?php echo $puesto->nombre; ?></caption>
                      <thead>
                        <tr>
                          <th>Nombre</th>
                          <th>VI</th>
                          <th>SA</th>
                          <th>DO</th>
                          <th>LU</th>
                          <th>MA</th>
                          <th>MI</th>
                          <th>JU</th>
                        </tr>
                      </thead>
                      <tbody>
                    <?php foreach($empleados as $e) {
                            if ($puesto->id_departamento === $e->id_departamente) {
                              $tuvoEmpleados = true;
                    ?>
                          <tr>
                            <td class="empleado-dbl-click"><a href="<?php echo base_url('panel/nomina_fiscal/show_otros/?eid='.$e->id.'&sem='.$numSemanaSelected) ?>" class="btn btn-info" rel="superbox-50x450" title="Bonos y Otros"><i class="icon-cogs"></i></a><?php echo $e->nombre; ?></td>

                            <?php foreach ($dias as $dia => $fecha) { ?>
                              <td style="width: 100px;padding: 0;">

                                <?php
                                    $selected_a = '';
                                    $selected_f = '';
                                    $selected_in = '';
                                    $select_color = 'green';
                                    if (isset($e->dias_faltantes) && count($e->dias_faltantes) > 0) {
                                      foreach ($e->dias_faltantes as $key => $fi) {
                                        if ($fi['tipo'] === 'f' && $fi['fecha'] == $fecha)
                                        {
                                          $selected_f = 'selected';
                                          $select_color = 'red';
                                        }
                                        else if ($fi['tipo'] === 'in' && $fi['fecha'] == $fecha)
                                        {
                                          $selected_in = $fi['id_clave'];
                                          $select_color = 'yellow';
                                        }
                                     }
                                    }
                                ?>

                                <select name="empleados[<?php echo $e->id ?>][<?php echo $fecha ?>]" class="span12 select-tipo" style="margin-bottom: 0px;background-color: <?php echo $select_color ?>;" title="<?php echo $fecha ?>">
                                  <option value="a" style="background-color: green;" <?php echo $selected_a ?>></option>
                                  <option value="f" style="background-color: red;" <?php echo $selected_f ?> <?php echo $dia === 2 ? '' : '' ?>></option>

                                  <?php foreach ($sat_incapacidades as $key => $tipo) { ?>
                                    <option value="in-<?php echo $tipo->id_clave ?>" style="background-color: yellow;" <?php echo $tipo->id_clave == $selected_in ? 'selected' : '' ?> <?php echo $dia === 2 ? '' : '' ?>><?php echo $tipo->nombre ?></option>
                                  <?php } ?>

                                </select>
                              </td>
                            <?php } ?>
                          </tr>
                      <?php }} ?>

                    <?php if ( ! $tuvoEmpleados){ ?>
                        <tr style="color: red;">
                          <td>Sin Registros</td>
                          <td style="width: 100px;"></td>
                          <td style="width: 100px;"></td>
                          <td style="width: 100px;"></td>
                          <td style="width: 100px;"></td>
                          <td style="width: 100px;"></td>
                          <td style="width: 100px;"></td>
                          <td style="width: 100px;"></td>
                        </tr>
                    <?php } ?>
                      </tbody>
                    </table>
              <?php }} ?>
            </form>
          </div>
        </div><!--/span-->

      </div><!--/row-->
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
