    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/facturacion/prodfact_pdf/'); ?>" method="GET" class="form-search" target="rpfReporte" id="form">
              <div class="form-actions form-filters">

                <div class="control-group">
                  <label class="control-label" for="ffecha1">Del</label>
                  <div class="controls">
                    <input type="date" name="ffecha1" class="input-medium search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1', date('Y-m').'-01'); ?>" size="10">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="ffecha2">Al</label>
                  <div class="controls">
                    <input type="date" name="ffecha2" class="input-medium search-query" id="ffecha2" value="<?php echo set_value_get('ffecha2', date('Y-m-d')); ?>" size="10">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dempresa">Empresa</label>
                  <div class="controls">
                    <input type="text" name="dempresa" class="input-xlarge search-query" id="dempresa" value="<?php echo set_value_get('dempresa', $empresa->nombre_fiscal); ?>" size="73">
                    <input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo set_value_get('did_empresa', $empresa->id_empresa); ?>">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dproducto">Producto</label>
                  <!-- <div class="controls">
                    <input type="text" name="dproducto" class="input-xlarge search-query" id="dproducto" value="<?php echo set_value_get('dproducto'); ?>" size="73">
                    <input type="hidden" name="did_producto" id="did_producto" value="<?php echo set_value_get('did_producto'); ?>">
                  </div> -->
                  <div class="input-append span12">
                    <input type="text" name="dproducto" value="" id="dproducto" class="span9" placeholder="Buscar">
                    <button class="btn" type="button" id="btnAddProducto" style="margin-left:-3px;"><i class="icon-plus-sign"></i></button>
                    <input type="hidden" name="did_producto" value="" id="did_producto">
                  </div>
                  <div class="clearfix"></div>
                  <div style="height:110px;overflow-y: scroll;background-color:#eee;">
                    <ul id="lista_proveedores" style="list-style: none;margin-left: 4px;">
                    </ul>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dcliente">Cliente</label>
                  <div class="controls">
                    <input type="text" name="dcliente" class="input-xlarge search-query" id="dcliente" value="<?php echo set_value_get('dcliente'); ?>" size="73">
                    <input type="hidden" name="fid_cliente" id="fid_cliente" value="<?php echo set_value_get('fid_cliente'); ?>">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dpagadas">Pagadas</label>
                  <div class="controls">
                    <input type="checkbox" name="dpagadas" value="1">
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
          <a href="" id="linkDownXls" data-url="<?php echo base_url('panel/facturacion/prodfact_xls'); ?>" class="linksm" target="_blank">
            <i class="icon-table"></i> Excel</a>

          <div class="box-content">
            <div class="row-fluid">
              <iframe name="rpfReporte" id="iframe-reporte" class="span12"
                src="<?php echo base_url('panel/facturacion/prodfact_pdf')?>" style="height:520px;"></iframe>
            </div>
          </div>
        </div><!--/span-->
      </div><!--/row-->




          <!-- content ends -->
    </div><!--/#content.span10-->
