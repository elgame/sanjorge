    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            Proveedores Facturación
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-file"></i> Proveedores Facturas</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <?php
            $quit_params = array('ffolio', 'dempresa', 'did_empresa', 'fstatus');
            if($this->input->get('fnombre') == '')
            {
              $quit_params[] = 'fnombre';
              $quit_params[] = 'fid_proveedor';
            }
            ?>
            <a href="<?php echo base_url('panel/proveedores_facturacion?'.MyString::getVarsLink($quit_params) ); ?>" class="linksm">
              <i class="icon-chevron-left"></i> Atras</a>
            <form action="<?php echo base_url('panel/proveedores_facturacion/admin'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters">
                <label for="ffolio">Folio</label>
                <input type="number" name="ffolio" id="ffolio" value="<?php echo set_value_get('ffolio'); ?>" class="input-mini search-query" autofocus>

                <label for="dproveedor">Proveedor</label>
                <input type="text" name="fnombre" class="input-large search-query" id="dproveedor" value="<?php echo set_value_get('fnombre'); ?>" size="73">
                <input type="hidden" name="fid_proveedor" id="fid_proveedor" value="<?php echo set_value_get('fid_proveedor'); ?>">

                <label for="dempresa">Empresa</label>
                <input type="text" name="dempresa" class="input-large search-query" id="dempresa" value="<?php echo set_value_get('dempresa'); ?>" size="73">
                <input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo set_value_get('did_empresa'); ?>">

                <br>
                <label for="ffecha1" style="margin-top: 15px;">Fecha del</label>
                <input type="date" name="ffecha1" class="input-xlarge search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1'); ?>" size="10">

                <label for="fstatus">Estado</label>
                <select name="fstatus" class="input-medium" id="fstatus">
                  <option value="">TODAS</option>
                  <option value="pa" <?php echo set_select_get('fstatus', 'pa'); ?>>PAGADAS</option>
                  <option value="p" <?php echo set_select_get('fstatus', 'p'); ?>>PENDIENTE</option>
                  <option value="ca" <?php echo set_select_get('fstatus', 'ca'); ?>>CANCELADAS</option>
                </select>

                <button type="submit" class="btn">Enviar</button>
              </div>
            </form>

            <?php
            echo $this->usuarios_model->getLinkPrivSm('proveedores_facturacion/agregar/', array(
                    'params'   => MyString::getVarsLink(['ffecha1']),
                    'btn_type' => 'btn-success pull-right',
                    'attrs' => array('style' => 'margin-bottom: 10px;') )
                );
             ?>
            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Serie-Folio</th>
                  <th>Empresa</th>
                  <th>Proveedor</th>
                  <th>Forma de Pago</th>
                  <th>Estado</th>
                  <th>Estado Timbre</th>
                  <th>Opc</th>
                </tr>
              </thead>
              <tbody>
            <?php foreach($datos_s['fact'] as $fact) {?>
                <tr>
                  <td><?php echo $fact->fecha; ?></td>
                  <td>
                    <span class="label"><?php echo $fact->serie.' - '.$fact->folio; ?></span>

                    <?php if ($fact->id_nc !== null){ ?>
                      <br><span class="label label-warning">Nota de Crédito</span>
                    <?php } ?>

                  </td>
                  <td><?php echo $fact->nombre_fiscal; ?></td>
                  <td><?php echo $fact->proveedor; ?></td>
                  <td><?php $texto = $fact->condicion_pago === 'cr' ? 'Credito' : 'Contado'; ?>
                      <span class="label label-info"><?php echo $texto ?></span>
                  </td>
                  <td><?php
                            $texto = 'Cancelada';
                            $label = 'important';
                            if ($fact->status === 'p') {
                              $texto = 'Pendiente';
                              $label = 'warning';
                            } else if ($fact->status === 'pa') {
                              $texto = 'Pagada';
                              $label = 'success';
                            }?>
                      <span class="label label-<?php echo $label ?> "><?php echo $texto ?></span>
                  </td>
                  <td><?php
                            $texto = 'Cancelado';
                            $label = 'Inverse';
                            if ($fact->status_timbrado === 'p') {
                              $texto = 'Pendiente';
                              $label = 'warning';
                            } else if ($fact->status_timbrado === 't') {
                              $texto = 'Timbrado';
                              $label = 'success';
                            }?>
                      <span class="label label-<?php echo $label ?> "><?php echo $texto ?></span>
                  </td>
                  <td class="center">
                    <?php

                      echo $this->usuarios_model->getLinkPrivSm('proveedores_facturacion/imprimir/', array(
                        'params'   => 'id='.$fact->id_factura,
                        'btn_type' => 'btn-info',
                        'attrs' => array('target' => "_blank"))
                      );

                      if ($fact->status !== 'ca')
                      {
                        echo $this->usuarios_model->getLinkPrivSm('proveedores_facturacion/cancelar/', array(
                          'params'   => 'id='.$fact->id_factura.'&'.MyString::getVarsLink(array('msg')),
                          'btn_type' => 'btn-danger',
                          'attrs' => array('onclick' => "msb.confirm('Estas seguro de Cancelar la factura?<br><strong>NOTA: Esta opción no se podra revertir.</strong>', 'Proveedores Facturación', this); return false;"))
                        );
                      }

                      // if ($fact->id_nc === null && $fact->status !== 'ca') {
                      //   echo $this->usuarios_model->getLinkPrivSm('proveedores_notas_credito/agregar/', array(
                      //       'params'   => 'id='.$fact->id_factura,
                      //       'btn_type' => '',
                      //       'attrs' => array('target' => "_blank"))
                      //   );
                      // }

                      // if ($fact->status === 'p')
                      // {
                      //   echo $this->usuarios_model->getLinkPrivSm('cuentas_cobrar/agregar_abono/', array(
                      //       'params'   => 'id='.$fact->id_factura.'&tipo=f',
                      //       'btn_type' => 'btn btn-success',
                      //       'attrs'    => array('rel' => 'superbox-50x500'))
                      //   );
                      // }

                      // if ($fact->status_timbrado === 't')
                      // {
                      //   echo '<a class="btn" href="'.base_url('panel/proveedores_facturacion/xml/?id='.$fact->id_factura).'" title="Descargar XML" target="_BLANK"><i class="icon-download-alt icon-white"></i> <span class="hidden-tablet">XML</span></a>';
                      // }

                      // if ($fact->id_nc === null)
                      // {
                      //   echo $this->usuarios_model->getLinkPrivSm('proveedores_facturacion/enviar_documentos/', array(
                      //     'params'   => 'id='.$fact->id_factura,
                      //     'btn_type' => 'btn-success',
                      //     'attrs' => array('rel' => 'superbox-50x450'))
                      //   );
                      // }
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
