<h3 style="text-align: center;">CHOFER FOTO FIRMA DEL MANIFIESTO</h3><br>

<div class="row-fluid">

  <div class="span12">

    <fieldset class="span4" style="border-bottom: none;">
      <legend style="margin-bottom: 3px;">Camara LIVE<button class="btn pull-right" type="button" id="btnSnapshot" data-name="pimgsalida"><i class="icon-camera icon-2x"></i></button></legend>
      <div class="row-fluid">
        <div class="span12">
          <img src="<?php echo $this->config->item('base_url_cam_salida_stream') ?>" width="320">
        </div>
      </div>
    </fieldset><!--/span4 -->

    <fieldset class="span4" style="border-bottom: none;">
      <legend style="margin-bottom: 3px;">Captura<button class="btn btn-danger pull-right" type="button" id="btn-del-captura" data-name=""><i class="icon-remove-circle icon-2x"></i></button></legend>
      <div class="row-fluid">
        <div class="span12">
          <?php
            $url = isset($dataDocumento->url) && $dataDocumento->url !== '' ? str_replace('\\', '', base_url($dataDocumento->url)) : '';
          ?>
          <img src="<?php echo $url ?>" width="320" id="imgCapture">
          <input type="hidden"  value="" id="inputImgCapture">
        </div>
      </div>
    </fieldset><!--/span4 -->

    <fieldset class="span4" style="border-bottom: none;">
      <legend style="margin-bottom: 3px;">Accion</legend>
      <div class="row-fluid">
        <div class="well span12">
          <?php if ($finalizados === 'f'){ ?>
            <div class="row-fluid">
              <button type="button" class="btn btn-success btn-large span12" id="btnSnapshotSave">Guardar</button>
            </div>
          <?php } ?>
          <?php if (isset($dataDocumento->url) && $dataDocumento->url !== '') { ?>
            <br>
            <div class="row-fluid" id="btn-show-captura">
              <a href="<?php echo str_replace('\\', '', base_url($dataDocumento->url)) ?>" class="btn btn-success btn-large span12" rel="superbox-80x600">Ver</a>
            </div>
          <?php } ?>
        </div>
      </div>
    </fieldset><!--/span4 -->

  </div><!--/span12 -->

</div><!--/row-fluid -->