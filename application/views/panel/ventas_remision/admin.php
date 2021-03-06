    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            Notas remisión
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-file"></i> Notas de remisión</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/ventas/'.$method); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters center">
                <label for="ffolio">Folio</label>
                <input type="number" name="ffolio" id="ffolio" value="<?php echo set_value_get('ffolio'); ?>" class="input-mini search-query" autofocus>

                <label for="dempresa">Empresa</label>
                <input type="text" name="dempresa" class="input-large search-query" id="dempresa" value="<?php echo set_value_get('dempresa'); ?>" size="73">
                <input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo set_value_get('did_empresa'); ?>">


                <label for="dcliente">Cliente</label>
                <input type="text" name="dcliente" class="input-large search-query" id="dcliente" value="<?php echo set_value_get('dcliente'); ?>" size="73">
                <input type="hidden" name="fid_cliente" id="fid_cliente" value="<?php echo set_value_get('fid_cliente'); ?>">

                <br>
                <label for="ffecha1" style="margin-top: 15px;">Fecha del</label>
                <input type="date" name="ffecha1" class="input-xlarge search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1', date('Y-m').'-01' ); ?>" size="10">
                <label for="ffecha2">Al</label>
                <input type="date" name="ffecha2" class="input-xlarge search-query" id="ffecha2" value="<?php echo set_value_get('ffecha2', $fecha); ?>" size="10">

                <label for="fstatus">Estado</label>
                <select name="fstatus" class="input-medium" id="fstatus">
                  <option value="">TODAS</option>
                  <option value="pa" <?php echo set_select_get('fstatus', 'pa'); ?>>PAGADAS</option>
                  <option value="p" <?php echo set_select_get('fstatus', 'p'); ?>>PENDIENTE</option>
                  <option value="ca" <?php echo set_select_get('fstatus', 'ca'); ?>>CANCELADAS</option>
                </select>

                <input type="submit" name="enviar" value="Enviar" class="btn">
              </div>
            </form>

            <?php
            echo $this->usuarios_model->getLinkPrivSm('ventas/agregar/', array(
                    'params'   => '',
                    'btn_type' => 'btn-success pull-right',
                    'attrs' => array('style' => 'margin-bottom: 10px;') )
                );
             ?>
            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Folio</th>
                  <th>Cliente</th>
                  <th>Empresa</th>
                  <th>Forma de Pago</th>
                  <th>Total</th>
                  <th>Estado</th>
                  <th>Observaciones</th>
                  <th>Acción</th>
                </tr>
              </thead>
              <tbody>
            <?php foreach($datos_s['fact'] as $fact) {?>
                <tr>
                  <td style="width:70px;"><?php echo $fact->fecha; ?></td>
                  <td>
                    <span class="label"><?php echo ($fact->serie !== '' ? $fact->serie.'-' : '').$fact->folio; ?></span>
                  </td>
                  <td><?php echo $fact->nombre_fiscal; ?></td>
                  <td><?php echo $fact->empresa; ?></td>
                  <td><?php $texto = $fact->condicion_pago === 'cr' ? 'Credito' : 'Contado'; ?>
                      <span class="label label-info"><?php echo $texto ?></span>
                  </td>
                  <td style="text-align: right;"><?php echo MyString::formatoNumero($fact->total, 2, '$', false); ?></td>
                  <td><?php
                            $texto = 'Cancelada';
                            $label = 'important';
                            if ($fact->status === 'p') {
                              $texto = 'Pendiente';
                              $label = 'warning';
                            } else if ($fact->status === 'pa') {
                              $texto = 'Pagada';
                              $label = 'success';
                            }
                            if($fact->id_nc != '')
                              $texto .= ' - Nota de Credito';

                            if ($fact->facturada > 0) {
                              echo '<span class="label label-info">Facturada</span>';
                            }
                      ?>
                      <span class="label label-<?php echo $label ?> "><?php echo $texto ?></span>
                  </td>
                  <td><?php echo $fact->observaciones; ?></td>
                  <td class="center">
                    <?php

                      if($this->usuarios_model->tienePrivilegioDe('', 'ventas/modificar/') && $fact->id_nc == '' && $fact->facturada == 0)
                        echo '<a class="btn btn-success" href="'.base_url().'panel/ventas/agregar/?id_nr='.$fact->id_factura.'" title="Modificar">
                              <i class="icon-edit icon-white"></i> <span class="hidden-tablet">Modificar</span></a>';

                      echo $this->usuarios_model->getLinkPrivSm('ventas/imprimir/', array(
                        'params'   => 'id='.$fact->id_factura,
                        'btn_type' => 'btn-info',
                        'attrs' => array('target' => "_blank"))
                      );

                      if($this->usuarios_model->tienePrivilegioDe('', 'ventas/imprimir/'))
                        echo '<a class="btn btn-info" href="'.base_url().'panel/ventas/imprimir_tk/?id='.$fact->id_factura.'" title="Ticket" target="_blank">
                              <i class="icon-print icon-white"></i> <span class="hidden-tablet">Ticket</span></a>';

                      if ($fact->status !== 'ca')
                      {
                        echo $this->usuarios_model->getLinkPrivSm('ventas/cancelar/', array(
                          'params'   => 'id='.$fact->id_factura,
                          'btn_type' => 'btn-danger',
                          'attrs' => array('onclick' => "msb.confirm('Estas seguro de Cancelar la nota de remisión?', 'Notas de Remisión', this); return false;"))
                        );
                        if($fact->id_nc == '')
                        {
                          echo $this->usuarios_model->getLinkPrivSm('documentos/agregar/', array(
                                'params'   => 'id='.$fact->id_factura,
                                'btn_type' => 'btn-success',
                                'attrs'    => array())
                            );
                          if($this->usuarios_model->tienePrivilegioDe('', 'ventas/nota_credito/') && $fact->facturada == 0)
                            echo '<a class="btn btn-warning" href="'.base_url().'panel/ventas/agregar/?id_nrc='.$fact->id_factura.'" title="Agregar Nota credito">
                                  <i class="icon-edit icon-white"></i> <span class="hidden-tablet">Nota Credito</span></a>';
                        }
                      }

                      if ($fact->facturada == 0 && $fact->id_nc == '') //&& $fact->status != 'ca'
                      {
                        echo '<a class="btn btn-success" href="'.base_url().'panel/facturacion/agregar/?id_nr='.$fact->id_factura.'" title="Facturar"
                                onclick="msb.confirm(\'Estas seguro de agregar una factura con los datos de la nota de remisión?\', \'Notas de Remisión\', this); return false;">
                              <i class="icon-print icon-white"></i> <span class="hidden-tablet">Facturar</span></a>';
                      }

                      echo $this->usuarios_model->getLinkPrivSm('ventas/enviar_documentos/', array(
                        'params'   => 'id='.$fact->id_factura,
                        'btn_type' => 'btn-success',
                        'attrs' => array('rel' => 'superbox-50x450'))
                      );

                    ?>
                  </td>
                </tr>
            <?php }?>
              </tbody>
            </table>

            <?php
            //Paginacion
            $this->pagination->initialize(array(
                'base_url'      => base_url($this->uri->uri_string()).'?'.MyString::getVarsLink(array('pag')).'&',
                'total_rows'    => $datos_s['total_rows'],
                'per_page'      => $datos_s['items_per_page'],
                'cur_page'      => $datos_s['result_page']*$datos_s['items_per_page'],
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
