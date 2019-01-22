<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang="es" class="no-js"> <!--<![endif]-->
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

  <title><?php echo $seo['titulo'];?></title>
  <meta name="description" content="<?php echo $seo['titulo'];?>">
  <meta name="viewport" content="width=device-width">

<?php
  if(isset($this->carabiner)){
    $this->carabiner->display('css');
    $this->carabiner->display('base_panel');
    $this->carabiner->display('js');
  }
?>

  <!-- The HTML5 shim, for IE6-8 support of HTML5 elements -->
  <!--[if lt IE 9]>
    <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
  <![endif]-->

<script type="text/javascript" charset="UTF-8">
  var base_url = "<?php echo base_url();?>",
      base_url_bascula = "<?php echo $this->config->item('base_url_bascula');?>",
      base_url_cam_salida_snapshot = "<?php echo $this->config->item('base_url_cam_salida_snapshot') ?> ";
</script>
</head>
<body>

  <div id="content" class="container-fluid" style="padding-right: 0;">
    <div class="row-fluid">
      <!--[if lt IE 7]>
        <div class="alert alert-info">
          <button type="button" class="close" data-dismiss="alert">×</button>
          <p>Usted está usando un navegador desactualizado. <a href="http://browsehappy.com/">Actualice su navegador</a> o <a href="http://www.google.com/chromeframe/?redirect=true">instale Google Chrome Frame</a> para experimentar mejor este sitio.</p>
        </div>
      <![endif]-->

      <?php
        $readonly = '';
        $show = true;
        $display = '';
        $action = base_url('panel/caja_chica/cargar/?'.MyString::getVarsLink(array('msg')));
        if (isset($caja['status']) && $caja['status'] === 'f' && ! $this->usuarios_model->tienePrivilegioDe('', 'caja_chica/modificar_caja/'))
        {
          $readonly = 'readonly';
          $display = 'display: none;';
          $action = '';
          $show = false;
        }

        $fecha = isset($_GET['ffecha']) ? $_GET['ffecha'] : date('Y-m-d');
      ?>

      <div class="span12">

        <select id="nomeclaturas_base" style="display: none;">
          <?php foreach ($nomenclaturas as $n) { ?>
            <option value="<?php echo $n->id ?>"><?php echo $n->nomenclatura ?></option>
          <?php } ?>
        </select>

        <form class="form-horizontal" action="<?php echo $action ?>" method="POST" id="frmcajachica">
          <?php $totalIngresos = 0; $totalSaldoIngresos = $caja['saldo_inicial']; ?>
          <!-- Header -->
          <div class="span12" style="margin: 10px 0 0 0;">
            <div class="row-fluid">
              <div class="span4" style="text-align: center;">
                <img alt="logo" src="<?php echo base_url(); ?>/application/images/logo.png" height="54">
              </div>
              <div class="span2" style="text-align: right;">
                <div class="row-fluid">
                  <div class="span12">Fecha <input type="date" name="fecha_caja_chica" value="<?php echo set_value('fecha_caja_chica', $fecha) ?>" id="fecha_caja" class="input-medium" readonly></div>
                </div>
                <div class="row-fluid" style="margin: 3px 0;">
                  <div class="span12">Saldo Inicial <input type="text" name="saldo_inicial" value="<?php echo set_value('saldo_inicial', $caja['saldo_inicial']) ?>" id="saldo_inicial" class="input-medium vpositive" <?php echo $readonly ?>></div>
                </div>
              </div>
              <div class="span4">
                <div class="row-fluid">
                  <input type="hidden" name="fno_caja" id="fno_caja" value="<?php echo $_GET['fno_caja']; ?>">

                  <?php if ($cajas_cerradas) { ?>
                    <div>Para modificar la caja no tiene que haber días cerrados mayores a esta fecha</div>
                  <?php } ?>

                  <?php if ($show && !$cajas_cerradas){ ?>
                    <div class="span4"><input type="submit" id="btnGuardar" class="btn btn-success btn-large span12" value="Guardar"></div>
                  <?php } ?>

                  <?php if (isset($caja['status']) && $caja['status'] === 't' && !$cajas_cerradas){ ?>
                    <div class="span4"><a href="<?php echo base_url('panel/caja_chica/cerrar_caja/?id='.$caja['id'].'&'.MyString::getVarsLink(array('msg', 'id'))) ?>" class="btn btn-success btn-large span12 btnCerrarCaja">Cerrar Caja</a></div>
                  <?php } ?>

                  <?php if (isset($caja['status']) && $caja['status'] === 'f') { ?>
                    <div class="span4"><a href="<?php echo base_url('panel/caja_chica/print_caja?'.MyString::getVarsLink(array('msg'))) ?>" class="btn btn-success btn-large span12" target="_blank">Imprimir</a></div>
                  <?php }  ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Ingresos -->
          <div class="row-fluid">
            <div class="span12">
              <div class="row-fluid">
                <div class="span12">

                    <!-- Ingresos por Reposicion-->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-ingresos">
                          <thead>
                            <tr>
                              <th colspan="6">INGRESOS POR REPOSICION
                                <?php if ($_GET['fno_caja'] == '4' || $_GET['fno_caja'] == '2' || $_GET['fno_caja'] == '1'): ?>
                                <button type="button" class="btn btn-success" id="btn-add-ingreso" style="padding: 2px 7px 2px; <?php echo $display ?>"><i class="icon-plus"></i></button>
                                <?php //if ($_GET['fno_caja'] == '4'): ?>
                                  <a href="#modal-movimientos" role="button" class="btn btn-info" data-toggle="modal" id="btn-show-movimientos" style="padding: 2px 7px 2px; float: right;<?php echo $display ?>">Movimientos</a>
                                <?php //endif ?>
                                <?php endif ?>
                              </th>
                              <th colspan="2">IMPORTE</th>
                            </tr>
                            <tr>
                              <th>EMPRESA</th>
                              <th>NOM</th>
                              <th>BANCO</th>
                              <th>POLIZA</th>
                              <th>NOMBRE</th>
                              <th>CONCEPTO</th>
                              <th>ABONO</th>
                              <th></th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                              $modificar_ingresos = $this->usuarios_model->tienePrivilegioDe('', 'caja_chica/modificar_ingresos/');
                              $mod_ing_readonly = !$modificar_ingresos && $readonly == ''? ' readonly': '';
                              if (isset($_POST['ingreso_concepto'])) {
                                foreach ($_POST['ingreso_concepto'] as $key => $concepto) {
                                    $totalIngresos += floatval($_POST['ingreso_monto'][$key]);
                                  ?>
                                  <tr>
                                    <td style="width: 100px;">
                                      <input type="hidden" name="ingreso_id_ingresos[]" value="" id="ingreso_id_ingresos">
                                      <input type="hidden" name="ingreso_del[]" value="" id="ingreso_del">
                                      <input type="text" name="ingreso_empresa[]" value="<?php echo $_POST['ingreso_empresa'][$key] ?>" class="input-small gasto-cargo" style="width: 150px;" required <?php echo $readonly ?>>
                                      <input type="hidden" name="ingreso_empresa_id[]" value="<?php echo $_POST['ingreso_empresa_id'][$key] ?>" class="input-small vpositive gasto-cargo-id">
                                    </td>
                                    <td style="width: 40px;">
                                      <select name="ingreso_nomenclatura[]" class="ingreso_nomenclatura" style="width: 70px;" <?php echo $readonly ?>>
                                        <?php foreach ($nomenclaturas as $n) { ?>
                                          <option value="<?php echo $n->id ?>" <?php echo $_POST['ingreso_nomenclatura'][$key] == $n->id ? 'selected' : '' ?>><?php echo $n->nomenclatura ?></option>
                                        <?php } ?>
                                      </select>
                                    </td>
                                    <td style=""><input type="text" name="ingreso_banco[]" value="<?php echo $_POST['ingreso_banco'][$key] ?>" class="ingreso_banco span12" maxlength="50" placeholder="Banco" <?php echo $readonly ?>></td>
                                    <td style=""><input type="text" name="ingreso_poliza[]" value="<?php echo $_POST['ingreso_poliza'][$key] ?>" class="ingreso_poliza span12" maxlength="100" placeholder="Poliza" style="" <?php echo $readonly ?>></td>
                                    <td>
                                      <input type="text" name="ingreso_nombre[]" value="<?php echo $_POST['ingreso_nombre'][$key] ?>" class="ingreso-nombre span12" maxlength="130" placeholder="Nombre" required <?php echo $readonly ?>>
                                    </td>
                                    <td>
                                      <input type="text" name="ingreso_concepto[]" value="<?php echo $concepto ?>" class="ingreso-concepto span12" maxlength="500" placeholder="Concepto" required <?php echo $readonly ?>>
                                      <input type="hidden" name="ingreso_concepto_id[]" value="<?php echo $_POST['ingreso_concepto_id'][$key] ?>" class="ingreso_concepto_id span12" placeholder="Concepto">
                                    </td>
                                    <td style=""><input type="text" name="ingreso_monto[]" value="<?php echo $_POST['ingreso_monto'][$key] ?>" class="ingreso-monto vpositive input-small" placeholder="Monto" required <?php echo $readonly ?>></td>
                                    <td style="width: 30px;">
                                      <button type="button" class="btn btn-danger btn-del-ingreso" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                    </td>
                                  </tr>
                            <?php }} else {
                                  foreach ($caja['ingresos'] as $ingreso) {
                                      $totalIngresos += floatval($ingreso->monto);
                                    ?>
                                    <tr>
                                      <td style="width: 100px;">
                                        <input type="hidden" name="ingreso_id_ingresos[]" value="<?php echo $ingreso->id_ingresos ?>" id="ingreso_id_ingresos">
                                        <input type="hidden" name="ingreso_del[]" value="" id="ingreso_del">
                                        <input type="text" name="ingreso_empresa[]" value="<?php echo $ingreso->categoria ?>" class="input-small gasto-cargo" style="width: 150px;" required <?php echo $readonly.$mod_ing_readonly ?>>
                                        <input type="hidden" name="ingreso_empresa_id[]" value="<?php echo $ingreso->id_categoria ?>" class="input-small vpositive gasto-cargo-id">
                                        <a href="<?php echo base_url('panel/caja_chica/print_vale_ipr/?id_ingresos='.$ingreso->id_ingresos.'&noCaja='.$ingreso->no_caja)?>" target="_blank" title="Imprimir Ingreso por reposicion">
                                          <i class="ico icon-print" style="cursor:pointer"></i></a>
                                      </td>
                                      <td style="width: 40px;">
                                        <select name="ingreso_nomenclatura[]" class="ingreso_nomenclatura" style="width: 70px;" <?php echo $readonly.$mod_ing_readonly ?>>
                                          <?php foreach ($nomenclaturas as $n) { ?>
                                            <option value="<?php echo $n->id ?>" <?php echo $ingreso->nomenclatura == $n->id ? 'selected' : '' ?>><?php echo $n->nomenclatura ?></option>
                                          <?php } ?>
                                        </select>
                                      </td>
                                      <td style=""><input type="text" name="ingreso_banco[]" value="<?php echo $ingreso->banco ?>" class="ingreso_banco span12" maxlength="50" placeholder="Banco" <?php echo $readonly.$mod_ing_readonly ?>></td>
                                      <td style=""><input type="text" name="ingreso_poliza[]" value="<?php echo $ingreso->poliza ?>" class="ingreso_poliza span12" maxlength="100" placeholder="Poliza" style="" <?php echo $readonly.$mod_ing_readonly ?>></td>
                                      <td>
                                        <input type="text" name="ingreso_nombre[]" value="<?php echo $ingreso->nombre ?>" class="ingreso-nombre span12" maxlength="130" placeholder="Nombre" required <?php echo $readonly.$mod_ing_readonly ?>>
                                      </td>
                                      <td>
                                        <input type="text" name="ingreso_concepto[]" value="<?php echo $ingreso->concepto ?>" class="ingreso-concepto span12" maxlength="500" placeholder="Concepto" required <?php echo $readonly.$mod_ing_readonly ?>>
                                        <input type="hidden" name="ingreso_concepto_id[]" value="<?php echo $ingreso->id_movimiento ?>" class="ingreso_concepto_id span12" placeholder="Concepto">
                                      </td>
                                      <td style=""><input type="text" name="ingreso_monto[]" value="<?php echo $ingreso->monto ?>" class="ingreso-monto vpositive input-small" placeholder="Monto" required <?php echo $readonly.$mod_ing_readonly ?>></td>
                                      <td style="width: 30px;">
                                        <?php if ($modificar_ingresos): ?>
                                          <button type="button" class="btn btn-danger btn-del-ingreso" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                        <?php endif ?>
                                      </td>
                                    </tr>
                            <?php }} ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Ingresos por Reposicion-->

                    <!-- Ingresos Clientes-->
                    <div class="row-fluid">
                      <!-- <div class="span2" style="font-weight: bold; text-align: center;margin-top: 1px;">OTROS <button type="button" class="btn btn-success" id="btn-add-otros" style="padding: 2px 7px 2px; <?php echo $display ?>"><i class="icon-plus"></i></button></div> -->
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-remisiones">
                          <?php if ($_GET['fno_caja'] == '4'): ?>
                          <thead>
                            <tr>
                              <th colspan="4">INGRESOS CLIENTES
                                <!-- <button type="button" class="btn btn-success" id="btn-add-otros" style="padding: 2px 7px 2px; <?php echo $display ?>"><i class="icon-plus"></i></button> -->
                                <a href="#modal-remisiones" role="button" class="btn btn-info" data-toggle="modal" id="btn-show-remisiones" style="padding: 2px 7px 2px; float: right; <?php echo $display ?>">Remisiones</a>
                              </th>
                              <th colspan="2">IMPORTE</th>
                            </tr>
                            <tr>
                              <th>EMPRESA</th>
                              <th>REMISION</th>
                              <th>FOLIO</th>
                              <th>NOMBRE</th>
                              <th>ABONO</th>
                              <th></th>
                            </tr>
                          </thead>
                          <?php endif ?>
                          <tbody>
                            <?php
                            $totalIngresos = 0;
                            if ($_GET['fno_caja'] == '4') {
                              if (isset($_POST['remision_concepto'])) {
                                foreach ($_POST['remision_concepto'] as $key => $concepto) {
                                  // $totalIngresos += floatval($_POST['otros_monto'][$key]);
                                ?>
                                  <tr>
                                    <td style="width: 100px;">
                                      <input type="text" name="remision_empresa[]" value="<?php echo $_POST['remision_empresa'][$key] ?>" class="input-small gasto-cargo" style="width: 150px;" required <?php echo $readonly ?>>
                                      <input type="hidden" name="remision_empresa_id[]" value="<?php echo $_POST['remision_empresa_id'][$key] ?>" class="input-small vpositive gasto-cargo-id">
                                      <input type="hidden" name="remision_row[]" value="" class="input-small vpositive remision_row">
                                    </td>
                                    <td style="width: 70px;"><input type="text" name="remision_numero[]" value="<?php echo $_POST['remision_numero'][$key] ?>" class="remision-numero vpositive input-small" placeholder="" readonly style="width: 70px;" <?php echo $readonly ?>></td>
                                    <td style="width: 100px;"><input type="text" name="remision_folio[]" value="<?php echo $_POST['remision_folio'][$key] ?>" class="remision_folio" placeholder="Folio" style="width: 100px;" <?php echo $readonly ?>></td>
                                    <td>
                                      <input type="text" name="remision_concepto[]" value="<?php echo $concepto ?>" class="remision-concepto span12" maxlength="500" placeholder="Concepto" required <?php echo $readonly ?>>
                                      <input type="hidden" name="remision_id[]" value="<?php echo $_POST['remision_id'][$key] ?>" class="remision-id span12" required>
                                    </td>
                                    <td style="width: 100px;"><input type="text" name="remision_importe[]" value="<?php echo $_POST['remision_importe'][$key] ?>" class="remision-importe vpositive input-small" placeholder="Importe" required <?php echo $readonly ?>></td>
                                    <td style="width: 30px;">
                                      <button type="button" class="btn btn-danger btn-del-otros" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                      <input type="hidden" name="remision_del[]" value="" id="remision_del">
                                    </td>
                                  </tr>
                              <?php }} else {
                                  foreach ($caja['remisiones'] as $remision) {
                                    // $totalIngresos += floatval($otro->monto);
                                  ?>
                                    <tr>
                                      <td style="width: 100px;">
                                        <input type="text" name="remision_empresa[]" value="<?php echo $remision->empresa ?>" class="input-small gasto-cargo" style="width: 150px;" required <?php echo $readonly ?>>
                                        <input type="hidden" name="remision_empresa_id[]" value="<?php echo $remision->id_categoria ?>" class="input-small vpositive gasto-cargo-id">
                                        <input type="hidden" name="remision_row[]" value="<?php echo $remision->row ?>" class="input-small vpositive remision_row">
                                        <a href="<?php echo base_url('panel/caja_chica/print_vale_rm/?fecha='.$remision->fecha.'&id_remision='.$remision->id_remision.'&row='.$remision->row.'&noCaja='.$remision->no_caja)?>" target="_blank" title="Imprimir VALE DE CAJA CHICA">
                                          <i class="ico icon-print" style="cursor:pointer"></i></a>
                                      </td>
                                      <td style="width: 70px;"><input type="text" name="remision_numero[]" value="<?php echo $remision->folio ?>" class="remision-numero vpositive input-small" placeholder="" readonly style="width: 70px;" <?php echo $readonly ?>></td>
                                      <td style="width: 100px;"><input type="text" name="remision_folio[]" value="<?php echo $remision->folio_factura ?>" class="remision_folio" placeholder="Folio" style="width: 100px;" <?php echo $readonly ?>></td>
                                      <td>
                                        <input type="text" name="remision_concepto[]" value="<?php echo $remision->observacion ?>" class="remision-concepto span12" maxlength="500" placeholder="Concepto" required <?php echo $readonly ?>>
                                        <input type="hidden" name="remision_id[]" value="<?php echo $remision->id_remision ?>" class="remision-id span12" required>
                                      </td>
                                      <td style="width: 100px;"><input type="text" name="remision_importe[]" value="<?php echo $remision->monto ?>" class="remision-importe vpositive input-small" placeholder="Importe" required <?php echo $readonly ?>></td>
                                      <td style="width: 30px;">
                                        <button type="button" class="btn btn-danger btn-del-otros" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                        <input type="hidden" name="remision_del[]" value="" id="remision_del">
                                      </td>
                                    </tr>
                            <?php }} ?>

                            <?php if (isset($_POST['remision_concepto'])) {
                              foreach ($_POST['remision_concepto'] as $key => $remision) {
                                  $totalIngresos += floatval($_POST['remision_importe'][$key]);
                                ?>
                            <?php }} else {
                              foreach ($caja['remisiones'] as $remision) {
                                  $totalIngresos += floatval($remision->monto);
                                ?>
                            <?php }} ?>

                            <tr class='row-total'>
                              <td colspan="4"></td>
                              <td style="width: 100px;"><input type="text" name="total_ingresos" value="<?php echo MyString::float(MyString::formatoNumero($totalIngresos, 2, '')) ?>" class="span12" id="total-ingresos" maxlength="500" readonly style="text-align: right;"></td>
                              <td></td>
                            </tr>

                          <?php } ?>

                            <tr>
                              <td colspan="6"></td>
                              <td style="">
                                <?php $totalReporteCaja = $totalSaldoIngresos + $totalIngresos ?>
                                <input type="text" name="tota_saldo_ingresos" value="<?php echo MyString::float(MyString::formatoNumero($totalReporteCaja, 2, '')) ?>" class="span12" id="total-saldo-ingresos" maxlength="500" readonly style="text-align: right;">
                              </td>
                              <td></td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Ingresos Clientes-->

                    <?php
                    $totalTraspasos = 0;
                    if ($_GET['fno_caja'] == '2' || $_GET['fno_caja'] == '4'): ?>
                    <!-- Traspasos -->
                    <div class="row-fluid" style="margin-top: 5px;">
                      <div class="span12">
                        <div class="row-fluid">
                          <div class="span12">
                            <div class="row-fluid">
                              <!-- <div class="span12" style="background-color: #DADADA; text-align: center; font-weight: bold; min-height: 20px;">GASTOS DEL DIA <button type="button" class="btn btn-success" id="btn-add-gasto" style="padding: 2px 7px 2px;float: right;margin-right: 2px;<?php echo $display ?>"><i class="icon-plus"></i></button></div> -->
                              <div class="row-fluid">
                                <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                                  <table class="table table-striped table-bordered table-hover table-condensed" id="table-traspasos">
                                    <thead>
                                      <tr>
                                        <th colspan="2">TRASPASOS <button type="button" class="btn btn-success" id="btn-add-traspaso" style="padding: 2px 7px 2px;margin-right: 2px;<?php echo $display ?>"><i class="icon-plus"></i></button></th>
                                        <th colspan="1"></th>
                                        <th colspan="2">IMPORTE</th>
                                      </tr>
                                      <tr>
                                        <th>TIPO</th>
                                        <th title="Afectar el fondo de la caja">AF. FONDO</th>
                                        <th>CONCEPTO</th>
                                        <th>CARGO</th>
                                        <th></th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <?php
                                        if (isset($_POST['traspaso_concepto'])) {
                                          foreach ($_POST['traspaso_concepto'] as $key => $concepto) {
                                            $totalTraspasos += ($_POST['traspaso_tipo'][$key] == 't'? 1: -1) * floatval($_POST['traspaso_importe'][$key]); ?>
                                          <tr>
                                            <td>
                                              <select name="traspaso_tipo[]" class="span12 ingreso_nomenclatura" <?php echo $readonly ?>>
                                                <option value="t" <?php echo $_POST['traspaso_tipo'][$key] == 't' ? 'selected' : '' ?>>Ingreso</option>
                                                <option value="f" <?php echo $_POST['traspaso_tipo'][$key] == 'f' ? 'selected' : '' ?>>Egreso</option>
                                              </select>
                                              <input type="hidden" name="traspaso_id_traspaso[]" value="" id="traspaso_id_traspaso">
                                              <input type="hidden" name="traspaso_del[]" value="" id="traspaso_del">
                                            </td>
                                            <td>
                                              <select name="traspaso_afectar_fondo[]" class="span12 traspaso_afectar_fondo">
                                                <option value="f" <?php echo $_POST['traspaso_afectar_fondo'][$key] == 'f' ? 'selected' : '' ?>>No</option>
                                                <option value="t" <?php echo $_POST['traspaso_afectar_fondo'][$key] == 't' ? 'selected' : '' ?>>Si</option>
                                              </select>
                                            </td>
                                            <td style="">
                                              <input type="text" name="traspaso_concepto[]" value="<?php echo $_POST['traspaso_concepto'][$key] ?>" class="span12 traspaso-concepto" <?php echo $readonly ?>>
                                            </td>
                                            <td style="width: 60px;"><input type="text" name="traspaso_importe[]" value="<?php echo $_POST['traspaso_importe'][$key] ?>" class="span12 vpositive traspaso-importe" <?php echo $readonly ?>></td>
                                            <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-traspaso" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                                          </tr>
                                      <?php }} else {
                                        if (isset($caja['traspasos']))
                                        foreach ($caja['traspasos'] as $traspaso) {
                                          $totalTraspasos += ($traspaso->tipo == 't'? 1: -1) * floatval($traspaso->monto);
                                        ?>
                                        <tr>
                                          <td>
                                            <select name="traspaso_tipo[]" class="span12 ingreso_nomenclatura" <?php echo $readonly ?>>
                                              <option value="t" <?php echo $traspaso->tipo == 't' ? 'selected' : '' ?>>Ingreso</option>
                                              <option value="f" <?php echo $traspaso->tipo == 'f' ? 'selected' : '' ?>>Egreso</option>
                                            </select>
                                            <input type="hidden" name="traspaso_id_traspaso[]" value="<?php echo $traspaso->id_traspaso ?>" id="traspaso_id_traspaso">
                                            <input type="hidden" name="traspaso_del[]" value="" id="traspaso_del">
                                          </td>
                                          <td>
                                            <select name="traspaso_afectar_fondo[]" class="span12 traspaso_afectar_fondo" <?php echo $readonly ?>>
                                              <option value="f" <?php echo $traspaso->afectar_fondo == 'f' ? 'selected' : '' ?>>No</option>
                                              <option value="t" <?php echo $traspaso->afectar_fondo == 't' ? 'selected' : '' ?>>Si</option>
                                            </select>
                                          </td>
                                          <td style="">
                                            <input type="text" name="traspaso_concepto[]" value="<?php echo $traspaso->concepto ?>" class="span12 traspaso-concepto" <?php echo $readonly ?>>
                                          </td>
                                          <td style="width: 60px;"><input type="text" name="traspaso_importe[]" value="<?php echo $traspaso->monto ?>" class="span12 vpositive traspaso-importe" <?php echo $readonly ?>></td>
                                          <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-traspaso" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                                        </tr>
                                      <?php }} ?>
                                      <tr class="row-total">
                                        <td colspan="3" style="text-align: right; font-weight: bolder;">TOTAL</td>
                                        <td><input type="text" value="<?php echo $totalTraspasos ?>" class="input-small vpositive" id="ttotal-traspasos" style="text-align: right;" readonly></td>
                                        <td></td>
                                      </tr>
                                    </tbody>
                                  </table>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <!-- /Traspasos -->
                    <?php endif ?>

                    <!-- Boletas Pesadas -->
                    <?php
                    $totalBoletasPagadas = $totalBoletasPendientes = $totalBoletas = 0;
                    if ($_GET['fno_caja'] == '1'): ?>
                    <div class="row-fluid" style="margin-top: 5px;">
                      <div class="span12">
                        <div class="row-fluid">

                          <div class="span12">
                            <div class="row-fluid">
                              <!-- <div class="span12" style="background-color: #DADADA; text-align: center; font-weight: bold; min-height: 20px;">REPORTE CAJA "COMPRAS LIMON"</div> -->
                              <div class="row-fluid">
                                <!-- <div class="span2" style="font-weight: bold; text-align: center;margin-top: 1px;">INGRESOS <button type="button" class="btn btn-success" id="btn-add-ingreso" style="padding: 2px 7px 2px;"><i class="icon-plus"></i></button></div> -->
                                <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                                  <table class="table table-striped table-bordered table-hover table-condensed" id="table-boletas">
                                    <thead>
                                      <tr>
                                        <th>BOLETA</th>
                                        <th>FECHA</th>
                                        <!-- <th>FOLIO</th> -->
                                        <th>FACTURADOR</th>
                                        <th>SUPERVISOR</th>
                                        <th>PAGADO</th>
                                        <th>PENDIENTE</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <?php
                                        foreach ($caja['boletas'] as $key => $boleta) {
                                          $totalBoletas           += floatval($boleta->importe);
                                          $totalBoletasPagadas    += floatval($boleta->importe_pagada);
                                          $totalBoletasPendientes += floatval($boleta->importe_pendiente);
                                        ?>
                                        <tr>
                                          <td>
                                            <?php echo $boleta->boleta ?>
                                            <input type="hidden" name="boletas_id[]" value="<?php echo $boleta->id_bascula ?>">
                                          </td>
                                          <td><?php echo $boleta->fecha ?></td>
                                          <!-- <td style="width: 150px;"><input type="text" name="boletas_folio[]" value="<?php echo isset($_POST['boletas_folio'][$key]) ? $_POST['boletas_folio'][$key] : $boleta->folio_caja_chica ?>" maxlength="20" style="width: 150px;"></td> -->
                                          <td><?php echo $boleta->proveedor ?></td>
                                          <td><?php echo $boleta->productor ?></td>
                                          <td style="text-align: right;"><?php echo MyString::formatoNumero($boleta->importe_pagada, 2, '$') ?></td>
                                          <td style="text-align: right;"><?php echo MyString::formatoNumero($boleta->importe_pendiente, 2, '$') ?></td>
                                        </tr>
                                      <?php } ?>
                                    </tbody>
                                    <tbody>
                                      <tr>
                                        <td colspan="4"><input type="hidden" value="<?php echo $totalBoletasPagadas ?>" id="total-boletas"></td>
                                        <td style="text-align: right; font-weight: bold;"><?php echo MyString::formatoNumero($totalBoletasPagadas, 2, '$') ?></td>
                                        <td style="text-align: right; font-weight: bold;"><?php echo MyString::formatoNumero($totalBoletasPendientes, 2, '$') ?></td>
                                      </tr>
                                    </tbody>
                                  </table>
                                </div>
                              </div>
                            </div>
                          </div>

                        </div>
                      </div>
                    </div>
                    <?php endif ?>
                    <!-- /Boletas Pesadas -->

                </div>
              </div>
            </div>

            <div class="span6">

              <!-- Gastos -->
              <div class="row-fluid" style="margin-top: 5px;">
                <div class="span12">
                  <div class="row-fluid">
                    <div class="span12">
                      <div class="row-fluid">
                        <!-- <div class="span12" style="background-color: #DADADA; text-align: center; font-weight: bold; min-height: 20px;">GASTOS DEL DIA <button type="button" class="btn btn-success" id="btn-add-gasto" style="padding: 2px 7px 2px;float: right;margin-right: 2px;<?php echo $display ?>"><i class="icon-plus"></i></button></div> -->
                        <div class="row-fluid">
                          <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                            <table class="table table-striped table-bordered table-hover table-condensed" id="table-gastos">
                              <thead>
                                <tr>
                                  <th colspan="6">GASTOS DEL DIA
                                    <?php //if ($_GET['fno_caja'] !== '1'): ?>
                                    <button type="button" class="btn btn-success" id="btn-add-gasto" style="padding: 2px 7px 2px;margin-right: 2px;<?php echo $display ?>"><i class="icon-plus"></i></button>
                                    <?php //endif ?>
                                  </th>
                                  <th colspan="2">IMPORTE</th>
                                </tr>
                                <tr>
                                  <th>COD AREA</th>
                                  <th>EMPRESA</th>
                                  <th>NOM</th>
                                  <th>FOLIO</th>
                                  <th>CONCEPTO</th>
                                  <th>REP</th>
                                  <th>CARGO</th>
                                  <th></th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php
                                  $modificar_gasto = $this->usuarios_model->tienePrivilegioDe('', 'caja_chica/modificar_gastos/');
                                  $mod_gas_readonly = !$modificar_gasto && $readonly == ''? ' readonly': '';
                                  $totalGastos = 0;
                                  if (count($caja['gastos']) == 0 && isset($_POST['gasto_concepto']) && count($_POST['gasto_concepto']) > 0) {
                                    foreach ($_POST['gasto_concepto'] as $key => $concepto) {
                                      $totalGastos += floatval($_POST['gasto_importe'][$key]); ?>
                                        <tr>
                                          <td style="width: 60px;">
                                            <input type="hidden" name="gasto_id_gasto[]" value="" id="gasto_id_gasto">
                                            <input type="hidden" name="gasto_del[]" value="" id="gasto_del">
                                            <input type="text" name="codigoArea[]" value="<?php echo $_POST['codigoArea'][$key] ?>" id="codigoArea" class="span12 showCodigoAreaAuto" required>
                                            <input type="hidden" name="codigoAreaId[]" value="<?php echo $_POST['codigoAreaId'][$key] ?>" id="codigoAreaId" class="span12" required>
                                            <input type="hidden" name="codigoCampo[]" value="<?php echo $_POST['codigoCampo'][$key] ?>" id="codigoCampo" class="span12">
                                            <i class="ico icon-list showCodigoArea" style="cursor:pointer"></i>
                                            <input type="hidden" name="area[]" value="<?php echo $_POST['area'][$key] ?>" class="area span12">
                                            <input type="hidden" name="areaId[]" value="<?php echo $_POST['areaId'][$key] ?>" class="areaId span12">
                                            <input type="hidden" name="rancho[]" value="<?php echo $_POST['rancho'][$key] ?>" class="rancho span12">
                                            <input type="hidden" name="ranchoId[]" value="<?php echo $_POST['ranchoId'][$key] ?>" class="ranchoId span12">
                                            <input type="hidden" name="centroCosto[]" value="<?php echo $_POST['centroCosto'][$key] ?>" class="centroCosto span12">
                                            <input type="hidden" name="centroCostoId[]" value="<?php echo $_POST['centroCostoId'][$key] ?>" class="centroCostoId span12">
                                            <input type="hidden" name="activos[]" value="<?php echo $_POST['activos'][$key] ?>" class="activos span12">
                                            <input type="hidden" name="activoId[]" value="<?php echo $_POST['activoId'][$key] ?>" class="activoId span12">
                                            <input type="hidden" name="empresaId[]" value="<?php echo $_POST['empresaId'][$key] ?>" class="empresaId span12">
                                          </td>
                                          <td style="width: 100px;">
                                            <input type="text" name="gasto_empresa[]" value="<?php echo $_POST['gasto_empresa'][$key] ?>" class="span12 gasto-cargo" required <?php echo $readonly ?>>
                                            <input type="hidden" name="gasto_empresa_id[]" value="<?php echo $_POST['gasto_empresa_id'][$key] ?>" class="input-small vpositive gasto-cargo-id">
                                          </td>
                                          <td style="width: 40px;">
                                            <select name="gasto_nomenclatura[]" class="span12 ingreso_nomenclatura" <?php echo $readonly ?>>
                                              <?php foreach ($nomenclaturas as $n) { ?>
                                                <option value="<?php echo $n->id ?>" <?php echo $_POST['gasto_nomenclatura'][$key] == $n->id ? 'selected' : '' ?>><?php echo $n->nomenclatura ?></option>
                                              <?php } ?>
                                            </select>
                                          </td>
                                          <td style="width: 40px;"><input type="text" name="gasto_folio[]" value="<?php echo $_POST['gasto_folio'][$key] ?>" class="span12 gasto-folio" <?php echo $readonly ?>></td>
                                          <td style="">
                                            <input type="text" name="gasto_concepto[]" value="<?php echo $_POST['gasto_concepto'][$key] ?>" class="span12 gasto-concepto"  <?php echo $readonly ?>>
                                          </td>
                                          <td style="width: 20px;">
                                            <input type="checkbox" value="si" class="gasto-reposicion" <?php echo $readonly; ?>>
                                            <input type="hidden" name="gasto_reposicion[]" value="<?php echo $_POST['gasto_reposicion'][$key] ?>" class="gasto-reposicionhid">
                                          </td>
                                          <td style="width: 60px;"><input type="text" name="gasto_importe[]" value="<?php echo $_POST['gasto_importe'][$key] ?>" class="span12 vpositive gasto-importe" <?php echo $readonly ?>></td>
                                          <td style="width: 30px;">
                                            <button type="button" class="btn btn-danger btn-del-gasto" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                            <button type="button" class="btn btn-info btn-show-cat" style="padding: 2px 7px 2px;"><i class="icon-edit"></i></button>
                                          </td>
                                        </tr>
                                <?php }} else {
                                  foreach ($caja['gastos'] as $gasto) {
                                    $totalGastos += floatval($gasto->monto);
                                  ?>
                                  <tr>
                                    <td style="width: 60px;">
                                      <input type="hidden" name="gasto_id_gasto[]" value="<?php echo $gasto->id_gasto ?>" id="gasto_id_gasto">
                                      <input type="hidden" name="gasto_del[]" value="" id="gasto_del">
                                      <input type="text" name="codigoArea[]" value="<?php echo $gasto->nombre_codigo ?>" id="codigoArea" class="span12 showCodigoAreaAuto" required <?php echo $readonly.$mod_gas_readonly ?>>
                                      <input type="hidden" name="codigoAreaId[]" value="<?php echo $gasto->id_area ?>" id="codigoAreaId" class="span12" required>
                                      <input type="hidden" name="codigoCampo[]" value="<?php echo $gasto->campo ?>" id="codigoCampo" class="span12">
                                      <i class="ico icon-list showCodigoArea" style="cursor:pointer"></i>
                                      <input type="hidden" name="area[]" value="<?php echo $gasto->area ?>" class="area span12">
                                      <input type="hidden" name="areaId[]" value="<?php echo $gasto->id_areac ?>" class="areaId span12">
                                      <input type="hidden" name="rancho[]" value="<?php echo $gasto->rancho ?>" class="rancho span12">
                                      <input type="hidden" name="ranchoId[]" value="<?php echo $gasto->id_rancho ?>" class="ranchoId span12">
                                      <input type="hidden" name="centroCosto[]" value="<?php echo $gasto->centro_costo ?>" class="centroCosto span12">
                                      <input type="hidden" name="centroCostoId[]" value="<?php echo $gasto->id_centro_costo ?>" class="centroCostoId span12">
                                      <input type="hidden" name="activos[]" value="<?php echo $gasto->activo ?>" class="activos span12">
                                      <input type="hidden" name="activoId[]" value="<?php echo $gasto->id_activo ?>" class="activoId span12">
                                      <input type="hidden" name="empresaId[]" value="<?php echo $gasto->id_empresa ?>" class="empresaId span12">
                                      <a href="<?php echo base_url('panel/caja_chica/print_vale/?id='.$gasto->id_gasto)?>" target="_blank" title="Imprimir VALE DE CAJA CHICA">
                                        <i class="ico icon-print" style="cursor:pointer"></i></a>
                                    </td>
                                    <td style="width: 100px;">
                                      <input type="text" name="gasto_empresa[]" value="<?php echo $gasto->empresa ?>" class="span12 gasto-cargo" required <?php echo $readonly.$mod_gas_readonly ?>>
                                      <input type="hidden" name="gasto_empresa_id[]" value="<?php echo $gasto->id_categoria ?>" class="input-small vpositive gasto-cargo-id">
                                    </td>
                                    <td style="width: 40px;">
                                      <select name="gasto_nomenclatura[]" class="span12 ingreso_nomenclatura" <?php echo $readonly.$mod_gas_readonly ?>>
                                        <?php foreach ($nomenclaturas as $n) { ?>
                                          <option value="<?php echo $n->id ?>" <?php echo $gasto->id_nomenclatura == $n->id ? 'selected' : '' ?>><?php echo $n->nomenclatura ?></option>
                                        <?php } ?>
                                      </select>
                                    </td>
                                    <td style="width: 40px;"><input type="text" name="gasto_folio[]" value="<?php echo $gasto->folio ?>" class="span12 gasto-folio" <?php echo $readonly.$mod_gas_readonly ?>></td>
                                    <td style="">
                                      <input type="text" name="gasto_concepto[]" value="<?php echo $gasto->concepto ?>" class="span12 gasto-concepto" <?php echo $readonly.$mod_gas_readonly ?>>
                                    </td>
                                    <td style="width: 20px;">
                                      <input type="checkbox" value="si" class="gasto-reposicion" <?php echo ($gasto->reposicion=='t'? 'checked ': ' ').$readonly.$mod_gas_readonly; ?>>
                                      <input type="hidden" name="gasto_reposicion[]" value="<?php echo $gasto->reposicion ?>" class="gasto-reposicionhid">
                                    </td>
                                    <td style="width: 60px;"><input type="text" name="gasto_importe[]" value="<?php echo $gasto->monto ?>" class="span12 vpositive gasto-importe" <?php echo $readonly.$mod_gas_readonly ?>></td>
                                    <td style="width: 30px;">
                                      <?php if ($modificar_gasto): ?>
                                        <button type="button" class="btn btn-danger btn-del-gasto" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                        <button type="button" class="btn btn-info btn-show-cat" style="padding: 2px 7px 2px;"><i class="icon-edit"></i></button>
                                      <?php endif ?>
                                    </td>
                                  </tr>
                                <?php }} ?>
                                <tr class="row-total">
                                  <td colspan="6" style="text-align: right; font-weight: bolder;">TOTAL</td>
                                  <td><input type="text" value="<?php echo $totalGastos ?>" class="input-small vpositive" id="ttotal-gastos" style="text-align: right;" readonly></td>
                                  <td></td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <!-- /Gastos -->

              <!-- Deudores -->
              <div class="row-fluid" style="margin-top: 5px;">
                <div class="span12">
                  <div class="row-fluid">
                    <div class="span12">
                      <div class="row-fluid">
                        <!-- <div class="span12" style="background-color: #DADADA; text-align: center; font-weight: bold; min-height: 20px;">GASTOS DEL DIA <button type="button" class="btn btn-success" id="btn-add-gasto" style="padding: 2px 7px 2px;float: right;margin-right: 2px;<?php echo $display ?>"><i class="icon-plus"></i></button></div> -->
                        <div class="row-fluid">
                          <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                            <table class="table table-striped table-bordered table-hover table-condensed" id="table-deudor">
                              <thead>
                                <tr>
                                  <th colspan="8">DEUDORES <button type="button" class="btn btn-success" id="btn-add-deudor" style="padding: 2px 7px 2px;margin-right: 2px;<?php echo $display ?>"><i class="icon-plus"></i></button></th>
                                </tr>
                                <tr>
                                  <th>FECHA</th>
                                  <th>TIPO</th>
                                  <th>NOMBRE</th>
                                  <th>CONCEPTO</th>
                                  <th>PRESTADO</th>
                                  <th>ABONOS</th>
                                  <th>SALDO</th>
                                  <th></th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php
                                  $modificar_gasto = $this->usuarios_model->tienePrivilegioDe('', 'caja_chica/modificar_gastos/');
                                  $mod_gas_readonly = !$modificar_gasto && $readonly == ''? ' readonly': '';
                                  $totalDeudores = 0;
                                  if (count($caja['deudores']) == 0 && isset($_POST['deudor_nombre']) && count($_POST['deudor_nombre']) > 0) {
                                    foreach ($_POST['deudor_nombre'] as $key => $concepto) {
                                      $totalDeudores += floatval($_POST['deudor_importe'][$key]); ?>
                                        <tr>
                                          <td style="">
                                            <input type="hidden" name="deudor_fecha[]" value="">
                                          </td>
                                          <td style="width: 80px;">
                                            <select name="deudor_tipo[]" style="width: 80px;">
                                              <option value="otros" <?php echo $_POST['deudor_tipo'][$key]=='otros'? 'selected': ''; ?>>Otros</option>
                                              <option value="caja_limon" <?php echo $_POST['deudor_tipo'][$key]=='caja_limon'? 'selected': ''; ?>>Caja limón</option>
                                              <option value="caja_gastos" <?php echo $_POST['deudor_tipo'][$key]=='caja_gastos'? 'selected': ''; ?>>Caja gastos</option>
                                              <option value="caja_general" <?php echo $_POST['deudor_tipo'][$key]=='caja_gastos'? 'selected': ''; ?>>Caja gastos</option>
                                            </select>
                                          </td>
                                          <td style="width: 200px;">
                                            <input type="text" name="deudor_nombre[]" value="<?php echo $_POST['deudor_nombre'][$key] ?>" class="span12 deudor_nombre" required autocomplete="off" <?php echo $readonly.$mod_gas_readonly ?>>
                                            <input type="hidden" name="deudor_id_deudor[]" value="<?php echo $_POST['deudor_id_deudor'][$key] ?>" id="deudor_id_gasto">
                                            <input type="hidden" name="deudor_del[]" value="" id="deudor_del">
                                          </td>
                                          <td style="width: 200px;">
                                            <input type="text" name="deudor_concepto[]" value="<?php echo $_POST['deudor_concepto'][$key] ?>" class="span12 deudor-cargo" required <?php echo $readonly.$mod_gas_readonly ?>>
                                          </td>
                                          <td style="width: 80px;">
                                            <input type="text" name="deudor_importe[]" value="<?php echo $_POST['deudor_importe'][$key] ?>" class="span12 vpositive deudor-importe" <?php echo $readonly.$mod_gas_readonly ?>>
                                          </td>
                                          <td style="width: 80px;" class="deudor_abonos" data-abonos="0">
                                          </td>
                                          <td style="width: 80px;" class="deudor_saldo" data-saldo="0">
                                          </td>
                                          <td style="width: 30px;">
                                            <?php if ($modificar_gasto): ?>
                                              <button type="button" class="btn btn-danger btn-del-deudor" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                            <?php endif ?>
                                          </td>
                                        </tr>
                                <?php }} else {
                                  foreach ($caja['deudores'] as $deudor) {
                                    $totalDeudores += floatval($deudor->saldo);
                                  ?>
                                  <tr>
                                    <td style="width: 80px;">
                                      <?php echo $deudor->fecha ?>
                                      <input type="hidden" name="deudor_fecha[]" value="<?php echo $deudor->fecha ?>">
                                    </td>
                                    <td style="width: 80px;">
                                      <?php echo str_replace('_', ' ', $deudor->tipo); ?>
                                      <input type="hidden" name="deudor_tipo[]" value="<?php echo $deudor->tipo ?>">
                                    </td>
                                    <td style="width: 200px;">
                                      <input type="text" name="deudor_nombre[]" value="<?php echo $deudor->nombre ?>" class="span12 deudor_nombre" required autocomplete="off" <?php echo $deudor->mismo_dia.$readonly.$mod_gas_readonly ?>>
                                      <input type="hidden" name="deudor_id_deudor[]" value="<?php echo $deudor->id_deudor ?>" id="deudor_id_gasto">
                                      <input type="hidden" name="deudor_del[]" value="" id="deudor_del">
                                      <!-- <a href="<?php echo base_url('panel/caja_chica/print_vale/?id='.$deudor->id_deudor)?>" target="_blank" title="Imprimir vale prestamo">
                                        <i class="ico icon-print" style="cursor:pointer"></i></a> -->
                                    </td>
                                    <td style="width: 200px;">
                                      <input type="text" name="deudor_concepto[]" value="<?php echo $deudor->concepto ?>" class="span12 deudor-cargo" required <?php echo $deudor->mismo_dia.$readonly.$mod_gas_readonly ?>>
                                    </td>
                                    <td style="width: 80px;">
                                      <input type="text" name="deudor_importe[]" value="<?php echo $deudor->monto ?>" class="span12 vpositive deudor-importe" <?php echo $deudor->mismo_dia.$readonly.$mod_gas_readonly ?>>
                                    </td>
                                    <td style="width: 80px;" class="deudor_abonos" data-abonos="<?php echo $deudor->abonos ?>">
                                      <?php echo $deudor->abonos ?>
                                    </td>
                                    <td style="width: 80px;" class="deudor_saldo" data-saldo="<?php echo $deudor->saldo ?>" data-mismo="<?php echo $deudor->mismo_dia ?>">
                                      <?php if (!isset($caja['status']) || $caja['status'] === 't'): ?>
                                      <a class="btn_abonos_deudores" href="<?php echo base_url('panel/caja_chica/agregar_abono_deudor/')."?id={$deudor->id_deudor}&fecha={$fecha}&no_caja={$_GET['fno_caja']}&monto={$deudor->saldo}" ?>" style="" rel="superbox-50x500" title="Abonar">
                                        <?php echo $deudor->saldo ?></a>
                                      <?php else: ?>
                                        <?php echo $deudor->saldo ?>
                                      <?php endif ?>
                                    </td>
                                    <td style="width: 30px;">
                                      <?php if ($modificar_gasto && $deudor->mismo_dia == ''): ?>
                                        <button type="button" class="btn btn-danger btn-del-deudor" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                      <?php endif ?>
                                    </td>
                                  </tr>
                                <?php }} ?>
                                <tr class="row-total">
                                  <td></td>
                                  <td style="text-align: right; font-weight: bolder;">PRESTAMOS DEL DIA</td>
                                  <td style="text-align: right; font-weight: bolder;">
                                    <input type="text" value="<?php echo $caja['deudores_prest_dia'] ?>" class="input-small vpositive" id="total-deudores-pres-dia" style="text-align: right;" readonly>
                                  </td>
                                  <td style="text-align: right; font-weight: bolder;">ABONOS DEL DIA</td>
                                  <td style="text-align: right; font-weight: bolder;">
                                    <input type="text" value="<?php echo $caja['deudores_abonos_dia'] ?>" class="input-small vpositive" id="total-deudores-abono-dia" style="text-align: right;" readonly>
                                  </td>
                                  <td style="text-align: right; font-weight: bolder;">TOTAL</td>
                                  <td><input type="text" value="<?php echo $totalDeudores ?>" class="input-small vpositive" id="total-deudores" style="text-align: right;" readonly></td>
                                  <td></td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <!-- /Deudores -->

              <?php
              $totalAcreedores = $totalAcreedoresHoy = 0;
              if (($_GET['fno_caja'] == '1' || $_GET['fno_caja'] == '2' || $_GET['fno_caja'] == '4')) { ?>
              <!-- Acreedores -->
              <div class="row-fluid" style="margin-top: 5px;">
                <div class="span12">
                  <div class="row-fluid">
                    <div class="span12">
                      <div class="row-fluid">
                        <!-- <div class="span12" style="background-color: #DADADA; text-align: center; font-weight: bold; min-height: 20px;">GASTOS DEL DIA <button type="button" class="btn btn-success" id="btn-add-gasto" style="padding: 2px 7px 2px;float: right;margin-right: 2px;<?php echo $display ?>"><i class="icon-plus"></i></button></div> -->
                        <div class="row-fluid">
                          <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                            <table class="table table-striped table-bordered table-hover table-condensed" id="table-acreedor">
                              <thead>
                                <tr>
                                  <th colspan="8">ACREEDOR CAJA <?php echo ($_GET['fno_caja'] == '1'? 'GASTOS': 'LIMON') ?></th>
                                </tr>
                                <tr>
                                  <th>FECHA</th>
                                  <th>TIPO</th>
                                  <th>NOMBRE</th>
                                  <th>CONCEPTO</th>
                                  <th>PRESTADO</th>
                                  <th>ABONOS</th>
                                  <th>SALDO</th>
                                  <th></th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php
                                if (count($caja['acreedores']) > 0) {
                                  foreach ($caja['acreedores'] as $acreedor) {
                                    $totalAcreedores += floatval($acreedor->saldo);
                                    // if ($acreedor->mismo_dia) {
                                    //   $totalAcreedoresHoy += floatval($acreedor->saldo);
                                    // }
                                  ?>
                                  <tr>
                                    <td style="width: 80px;">
                                      <?php echo $acreedor->fecha ?>
                                    </td>
                                    <td style="width: 80px;">
                                      <?php echo str_replace('_', ' ', $acreedor->tipo); ?>
                                    </td>
                                    <td style="width: 200px;">
                                      <?php echo $acreedor->nombre ?>
                                    </td>
                                    <td style="width: 200px;">
                                      <?php echo $acreedor->concepto ?>
                                    </td>
                                    <td style="width: 80px;">
                                      <?php echo $acreedor->monto ?>
                                    </td>
                                    <td style="width: 80px;">
                                      <?php echo $acreedor->abonos ?>
                                    </td>
                                    <td style="width: 80px;">
                                      <?php echo $acreedor->saldo ?>
                                    </td>
                                    <td style="width: 30px;">
                                    </td>
                                  </tr>
                                <?php }
                                } ?>
                                <tr class="row-total">
                                  <td></td>
                                  <td style="text-align: right; font-weight: bolder;">PRESTAMOS DEL DIA</td>
                                  <td style="text-align: right; font-weight: bolder;">
                                    <input type="text" value="<?php echo $caja['acreedor_prest_dia'] ?>" class="input-small vpositive" id="total-acreddor-pres-dia" style="text-align: right;" readonly>
                                  </td>
                                  <td style="text-align: right; font-weight: bolder;">ABONOS DEL DIA</td>
                                  <td style="text-align: right; font-weight: bolder;">
                                    <input type="text" value="<?php echo $caja['acreedor_abonos_dia'] ?>" class="input-small vpositive" id="total-acreddor-abono-dia" style="text-align: right;" readonly>
                                  </td>
                                  <td style="text-align: right; font-weight: bolder;">TOTAL</td>
                                  <td><input type="text" value="<?php echo $totalAcreedores ?>" class="input-small vpositive" id="total-acreddor" style="text-align: right;" readonly></td>
                                  <td></td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <!-- /Acreedores -->
              <?php } ?>

              <!-- Tabulacion -->
              <div class="row-fluid">
                <div class="span12">
                  <div class="span12" style="text-align: center; font-weight: bold; min-height: 20px;">TABULACION DE EFECTIVO</div>
                  <div class="row-fluid">

                    <div class="span6" style="margin-top: 1px;">
                      <table class="table table-striped table-bordered table-hover table-condensed" id="table-tabulaciones">
                        <thead>
                          <tr>
                            <th>NUMERO</th>
                            <th>DENOMINACION</th>
                            <th>TOTAL</th>
                          </tr>
                        </thead>
                        <tbody>
                        </tbody>

                        <?php
                          $totalEfectivo = 0;
                          if (isset($_POST['denominacion_cantidad'])) {
                            foreach ($_POST['denominacion_cantidad'] as $key => $cantidad) {
                              $totalEfectivo += floatval($_POST['denominacion_total'][$key]); ?>
                                <tr>
                                  <td>
                                    <input type="text" name="denominacion_cantidad[]" value="<?php echo $cantidad ?>" class="input-small vpositive denom-num" data-denominacion="<?php echo $_POST['denominacion_denom'][$key] ?>" <?php echo $readonly ?>>
                                    <input type="hidden" name="denominacion_denom[]" value="<?php echo $_POST['denominacion_denom'][$key] ?>" class="input-small vpositive denom-num" <?php echo $readonly ?>>
                                    <input type="hidden" name="denom_abrev[]" value="<?php echo $_POST['denom_abrev'][$key] ?>" class="input-small vpositive denom-num" <?php echo $readonly ?>>
                                  </td>
                                  <td style="text-align: right;"><?php echo MyString::formatoNumero($_POST['denominacion_denom'][$key], 2, '$') ?></td>
                                  <td><input type="text" name="denominacion_total[]" value="<?php echo MyString::float($_POST['denominacion_total'][$key]) ?>" class="input-small vpositive denom-total" style="text-align: right;" <?php echo $readonly ?>></td>
                                </tr>
                        <?php }} else {
                          foreach ($caja['denominaciones'] as $denominacion) {
                            $totalEfectivo += floatval($denominacion['total']);
                          ?>
                          <tr>
                            <td>
                              <input type="text" name="denominacion_cantidad[]" value="<?php echo $denominacion['cantidad'] ?>" class="input-small vpositive denom-num" data-denominacion="<?php echo $denominacion['denominacion'] ?>" <?php echo $readonly ?>>
                              <input type="hidden" name="denominacion_denom[]" value="<?php echo $denominacion['denominacion'] ?>" class="input-small vpositive denom-num" <?php echo $readonly ?>>
                              <input type="hidden" name="denom_abrev[]" value="<?php echo $denominacion['denom_abrev'] ?>" class="input-small vpositive denom-num" <?php echo $readonly ?>>
                            </td>
                            <td style="text-align: right;"><?php echo MyString::formatoNumero($denominacion['denominacion'], 2, '$') ?></td>
                            <td><input type="text" name="denominacion_total[]" value="<?php echo MyString::float($denominacion['total']) ?>" class="input-small vpositive denom-total" style="text-align: right;" <?php echo $readonly ?>></td>
                          </tr>
                        <?php }} ?>
                        <tbody>
                          <tr>
                            <td colspan="2">TOTAL EFECTIVO</td>
                            <td id="total-efectivo-den" style="text-align: right; font-weight: bold;"><?php echo MyString::formatoNumero($totalEfectivo, 2, '$') ?></td>
                          </tr>
                          <tr>
                            <td colspan="2">TOTAL DIFERENCIA</td>
                            <td id="total-efectivo-diferencia" style="text-align: right; font-weight: bold;"><?php echo MyString::formatoNumero($totalEfectivo, 2, '$') ?></td>
                          </tr>
                        </tbody>
                      </table>
                    </div>

                    <!--Totales -->
                    <div class="span4 pull-right">
                      <div class="row-fluid">
                        <table class="table table-striped table-bordered table-hover table-condensed">
                          <thead>
                            <tr>
                              <th></th>
                              <th>TOTALES</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr>
                              <td>SALDO INICIAL:</td>
                              <td><input type="text" name="" value="<?php echo $caja['saldo_inicial'] ?>" class="input-small vpositive" id="" style="text-align: right;" readonly></td>
                            </tr>
                            <tr>
                              <td>TOTAL INGRESOS:</td>
                              <td><input type="text" name="" value="<?php echo $totalIngresos ?>" class="input-small vpositive" id="total-saldo-ingresos" style="text-align: right;" readonly></td>
                            </tr>
                            <tr>
                              <td>PAGO TOTAL LIMON:</td>
                              <td><input type="text" name="" value="<?php echo $totalBoletasPagadas ?>" class="input-small vpositive" id="" style="text-align: right;" readonly></td>
                            </tr>
                            <tr>
                              <td>PAGO TOTAL GASTOS:</td>
                              <td><input type="text" name="" value="<?php echo $totalGastos ?>" class="input-small vpositive" id="ttotal-gastos" style="text-align: right;" readonly></td>
                            </tr>
                            <tr>
                              <td>TOTAL TRASPASOS:</td>
                              <td><input type="text" name="" value="<?php echo $totalTraspasos ?>" class="input-small vpositive" id="ttotal-traspasos" style="text-align: right;" readonly></td>
                            </tr>
                            <tr>
                              <td>TOTAL DEUDORES:</td>
                              <td><input type="text" name="" value="<?php echo ($caja['deudores_prest_dia']-$caja['deudores_abonos_dia']) ?>" class="input-small vpositive" id="ttotal-deudores" style="text-align: right;" readonly></td>
                            </tr>
                            <tr>
                              <td>TOTAL ACREEDORES:</td>
                              <td><input type="text" name="" value="<?php echo ($caja['acreedor_prest_dia']-$caja['acreedor_abonos_dia']) ?>" class="input-small vpositive" id="ttotal-acreedores" style="text-align: right;" readonly></td>
                            </tr>
                            <tr>
                              <td>SALDO DEL CORTE:</td>
                              <td><input type="text" name="saldo_corte" value="<?php echo $totalReporteCaja + ($caja['acreedor_prest_dia']-$caja['acreedor_abonos_dia']) - $totalBoletasPagadas - $totalGastos + $totalTraspasos - ($caja['deudores_prest_dia']-$caja['deudores_abonos_dia']) ?>" class="input-small vpositive" id="ttotal-corte" style="text-align: right;" readonly></td>
                              <input type="hidden" name="total_diferencia" value="<?php echo $totalEfectivo - ($totalReporteCaja + ($caja['acreedor_prest_dia']-$caja['acreedor_abonos_dia']) - $totalBoletasPagadas - $totalGastos + $totalTraspasos - ($caja['deudores_prest_dia']-$caja['deudores_abonos_dia'])) ?>" class="input-small vpositive" id="ttotal-diferencia" style="text-align: right;" readonly>
                            </tr>
                          </tbody>
                        </table>

                        <div class="span12" style="margin-left: 0;"> <br>
                          <?php if ($show && !$cajas_cerradas){ ?>
                            <div class="span5"><input type="submit" class="btn btn-success btn-large span12" value="Guardar"></div>
                          <?php } ?>

                          <?php if (isset($caja['status']) && $caja['status'] === 't' && !$cajas_cerradas){ ?>
                            <div class="span5"><a href="<?php echo base_url('panel/caja_chica/cerrar_caja/?id='.$caja['id'].'&'.MyString::getVarsLink(array('msg'))) ?>" class="btn btn-success btn-large span12 btnCerrarCaja">Cerrar Caja</a></div>
                          <?php } ?>

                          <?php if (isset($caja['status']) && $caja['status'] === 'f') { ?>
                            <div class="span5"><a href="<?php echo base_url('panel/caja_chica/print_caja?'.MyString::getVarsLink(array('msg'))) ?>" class="btn btn-success btn-large span12" target="_blank">Imprimir</a></div>
                          <?php }  ?>
                        </div>
                      </div>
                    </div>
                    <!--/Totales -->

                  </div>
                </div>
              </div>
              <!--/Tabulacion -->
            </div>
          </div>
          <!-- /Ingresos por Reposicion -->
        </form>
      </div>

    </div><!--/#content.span10-->
  </div><!--/fluid-row-->

  <div class="clear"></div>

  <!-- Modal -->
  <div id="modal-remisiones" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="width: 700px;left: 45%;">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="myModalLabel">Remisiones</h3>
    </div>
    <div class="modal-body" style="max-height: 370px;">
      <table id="lista_remisiones_modal" class="table table-striped table-bordered table-hover table-condensed">
        <caption></caption>
        <thead>
          <tr>
            <th></th>
            <th>Fecha</th>
            <th>Folio</th>
            <th>Cliente</th>
            <th>Empresa</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
      <button class="btn btn-primary" id="carga-remisiones">Cargar</button>
    </div>
  </div>

    <!-- Modal movimientos -->

  <div id="modal-movimientos" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="width: 700px;left: 45%;">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <br>
      <h3 id="myModalLabel">Movimientos <!-- <input type="text" id="search-movimientos" placeholder="filtro"></input> --></h3>
    </div>
    <div class="modal-body" style="max-height: 370px;">
      <table id="lista_movimientos_modal" class="table table-striped table-bordered table-hover table-condensed" id="table-modal-movimientos">
        <caption></caption>
        <thead>
          <tr>
            <th></th>
            <th>Fecha</th>
            <th>Proveedor</th>
            <th>Poliza</th>
            <th>Monto</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($movimientos as $movi) { ?>
            <tr>
              <td><input type="checkbox" class="chk-movimiento"
                data-id="<?php echo $movi->id_movimiento ?>" data-total="<?php echo $movi->monto ?>"
                data-proveedor="<?php echo $movi->proveedor ?>"
                data-poliza="<?php echo $movi->numero_ref ?>"
                data-banco="<?php echo $movi->banco ?>"
                ></td>
              <td style="width: 66px;"><?php echo $movi->fecha ?></td>
              <td class="search-field"><?php echo $movi->proveedor ?></td>
              <td><?php echo $movi->numero_ref." ".$movi->banco ?></td>
              <td style="text-align: right;"><?php echo MyString::formatoNumero(MyString::float($movi->monto), 2, '$') ?></td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
      <button class="btn btn-primary" id="carga-movimientos">Cargar</button>
    </div>
  </div>

  <!-- Modal -->
  <div id="modalAreas" class="modal modal-w70 hide fade" tabindex="-1" role="dialog" aria-labelledby="modalAreasLavel" aria-hidden="true">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="modalAreasLavel">Catalogo de maquinaria, equipos e instalaciones</h3>
    </div>
    <div class="modal-body">

      <div class="row-fluid">

        <div>

      <?php foreach ($areas as $key => $value)
      { ?>
          <div class="span3" id="tblAreasDiv<?php echo $value->id_tipo ?>" style="display: none;">
            <table class="table table-hover table-condensed <?php echo ($key==0? 'tblAreasFirs': ''); ?>"
                id="tblAreas<?php echo $value->id_tipo ?>" data-id="<?php echo $value->id_tipo ?>">
              <thead>
                <tr>
                  <th style="width:10px;"></th>
                  <th>Codigo</th>
                  <th><?php echo $value->nombre ?></th>
                </tr>
              </thead>
              <tbody>
                <!-- <tr class="areaClick" data-id="" data-sig="">
                  <td><input type="radio" name="modalRadioSel" value="" data-uniform="false"></td>
                  <td>9</td>
                  <td>EMPAQUE</td>
                </tr> -->
              </tbody>
            </table>
          </div>
      <?php
      } ?>

        </div>

      </div>

    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
      <button class="btn btn-primary" id="btnModalAreasSel">Seleccionar</button>
    </div>
  </div>

  <!-- Modal -->
  <div id="modalCatalogos" class="modal modal-w70 hide fade" tabindex="-1" role="dialog" aria-labelledby="modalCatalogosLavel" aria-hidden="true">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="modalCatalogosLavel">Catálogos</h3>
    </div>
    <div class="modal-body">

      <div class="row-fluid">
        <div class="span6">
          <input type="hidden" id="accion_catalogos" value="true">
          <div class="control-group">
            <label class="control-label" for="dempresa">Empresa</label>
            <div class="controls">
              <input type="text" name="dempresa" class="span11" id="dempresa" value="" size="">
              <input type="hidden" name="did_empresa" id="did_empresa" value="">
              <input type="hidden" name="did_categoria" id="did_categoria" value="">
            </div>
          </div>

          <div class="control-group" id="cultivosGrup">
            <label class="control-label" for="area">Cultivo / Actividad / Producto </label>
            <div class="controls">
              <div class="input-append span12">
                <input type="text" name="area" class="span11" id="area" value="<?php echo set_value('area') ?>" placeholder="Limon, Piña">
              </div>
              <input type="hidden" name="areaId" id="areaId" value="<?php echo set_value('areaId') ?>">
            </div>
          </div><!--/control-group -->

          <div class="control-group" id="ranchosGrup">
            <label class="control-label" for="rancho">Areas / Ranchos / Lineas </label>
            <div class="controls">
              <div class="input-append span12">
                <input type="text" name="rancho" class="span11" id="rancho" value="<?php echo set_value('rancho') ?>" placeholder="Milagro A, Linea 1">
              </div>
              <input type="hidden" name="ranchoId" id="ranchoId" value="<?php echo set_value('ranchoId') ?>">
            </div>
          </div><!--/control-group -->

        </div>

        <div class="span6">
          <div class="control-group" id="centrosCostosGrup">
            <label class="control-label" for="centroCosto">Centro de costo </label>
            <div class="controls">
              <div class="input-append span12">
                <input type="text" name="centroCosto" class="span11" id="centroCosto" value="<?php echo set_value('centroCosto') ?>" placeholder="Mantenimiento, Gasto general">
              </div>
              <input type="hidden" name="centroCostoId" id="centroCostoId" value="<?php echo set_value('centroCostoId') ?>">
            </div>
          </div><!--/control-group -->

          <div class="control-group" id="activosGrup">
            <label class="control-label" for="activos">Activos </label>
            <div class="controls">
              <div class="input-append span12">
                <input type="text" name="activos" class="span11" id="activos" value="<?php echo set_value('activos') ?>" placeholder="Nissan FRX, Maquina limon">
              </div>
              <input type="hidden" name="activoId" id="activoId" value="<?php echo set_value('activoId') ?>">
            </div>
          </div><!--/control-group -->
        </div>

      </div>

    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
      <button class="btn btn-primary" id="btnModalCatalogosSel">Guardar</button>
    </div>
  </div>


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
</body>
</html>