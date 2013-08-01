    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            Cuentas por Cobrar
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-file"></i> Cuentas por Cobrar</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <a href="<?php echo base_url('panel/cuentas_cobrar/saldos_pdf/?'.String::getVarsLink(array('msg'))); ?>" class="linksm" target="_blank">
              <i class="icon-print"></i> Imprimir</a> | 
            <a href="<?php echo base_url('panel/cuentas_cobrar/saldos_xls/?'.String::getVarsLink(array('msg'))); ?>" class="linksm" target="_blank">
              <i class="icon-table"></i> Excel</a>

            <form action="<?php echo base_url('panel/cuentas_cobrar/'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters">
                <label for="ffecha1" style="margin-top: 15px;">Fecha del</label>
                <input type="date" name="ffecha1" class="input-large search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1'); ?>" size="10">
                <label for="ffecha2">Al</label>
                <input type="date" name="ffecha2" class="input-large search-query" id="ffecha2" value="<?php echo set_value_get('ffecha2'); ?>" size="10"> | 
                
                <label for="ftipo">Pagos:</label>
                <select name="ftipo" id="ftipo" class="input-large search-query">
                  <option value="to" <?php echo set_select_get('ftipo', 'to'); ?>>Todas</option>
                  <option value="pp" <?php echo set_select_get('ftipo', 'pp'); ?>>Pendientes por pagar</option>
                  <option value="pv" <?php echo set_select_get('ftipo', 'pv'); ?>>Plazo vencido</option>
                </select><br>

                <label for="dcliente">Cliente</label>
                <input type="text" name="dcliente" class="input-large search-query" id="dcliente" value="<?php echo set_value_get('dcliente'); ?>" size="73">
                <input type="hidden" name="fid_cliente" id="fid_cliente" value="<?php echo set_value_get('fid_cliente'); ?>"> | 

                <label for="dempresa">Empresa</label>
                <input type="text" name="dempresa" class="input-large search-query" id="dempresa" value="<?php echo set_value_get('dempresa', (isset($empresa->nombre_fiscal)? $empresa->nombre_fiscal: '') ); ?>" size="73">
                <input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo set_value_get('did_empresa', (isset($empresa->id_empresa)? $empresa->id_empresa: '')); ?>">

                <input type="submit" name="enviar" value="Enviar" class="btn">
              </div>
            </form>

            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th>Cliente</th>
                  <th>Cargos</th>
                  <th>Abonos</th>
                  <th>Saldo</th>
                </tr>
              </thead>
              <tbody>
            <?php
            $total_saldo = $total_abono = $total_cargo = 0; 
            foreach($data['cuentas'] as $cuenta){
              $total_cargo += $cuenta->total;
              $total_abono += $cuenta->abonos;
              $total_saldo += $cuenta->saldo;
            ?>
                <tr>
                  <td><a href="<?php echo base_url('panel/cuentas_cobrar/cuenta').'?id_cliente='.$cuenta->id_cliente.'&'.
                    String::getVarsLink(array('id_cliente', 'msg')); ?>" class="linksm lkzoom"><?php echo $cuenta->nombre; ?></a></td>
                  <td><?php echo String::formatoNumero($cuenta->total); ?></td>
                  <td><?php echo String::formatoNumero($cuenta->abonos); ?></td>
                  <td><?php echo String::formatoNumero($cuenta->saldo); ?></td>
                </tr>
            <?php }?>
                <tr style="background-color:#ccc;font-weight: bold;">
                  <td class="a-r">Total x Página:</td>
                  <td><?php echo String::formatoNumero($total_cargo); ?></td>
                  <td><?php echo String::formatoNumero($total_abono); ?></td>
                  <td><?php echo String::formatoNumero($total_saldo); ?></td>
                </tr>
                <tr style="background-color:#ccc;font-weight: bold;">
                  <td class="a-r">Total:</td>
                  <td><?php echo String::formatoNumero($data['ttotal_cargos']); ?></td>
                  <td><?php echo String::formatoNumero($data['ttotal_abonos']); ?></td>
                  <td><?php echo String::formatoNumero($data['ttotal_saldo']); ?></td>
                </tr>
              </tbody>
            </table>

            <?php
            //Paginacion
            $this->pagination->initialize(array(
                'base_url'      => base_url($this->uri->uri_string()).'?'.String::getVarsLink(array('pag')).'&',
                'total_rows'    => $data['total_rows'],
                'per_page'      => $data['items_per_page'],
                'cur_page'      => $data['result_page']*$data['items_per_page'],
                'page_query_string' => TRUE,
                'num_links'     => 1,
                'anchor_class'  => 'pags corner-all',
                'num_tag_open'  => '<li>',
                'num_tag_close' => '</li>',
                'cur_tag_open'  => '<li class="active"><a href="#">',
                'cur_tag_close' => '</a></li>'
            ));
            $pagination = $this->pagination->create_links();
            echo '<div class="pagination pagination-centered"><ul>'.$pagination.'</ul></div>';
            ?>
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