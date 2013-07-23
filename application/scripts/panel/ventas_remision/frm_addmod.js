$(function(){

  $('#form').keyJump();

  // $("#dfecha").datepicker({
  //      dateFormat: 'yy-mm-dd', //formato de la fecha - dd,mm,yy=dia,mes,año numericos  DD,MM=dia,mes en texto
  //      //minDate: '-2Y', maxDate: '+1M +10D', //restringen a un rango el calendario - ej. +10D,-2M,+1Y,-3W(W=semanas) o alguna fecha
  //      changeMonth: true, //permite modificar los meses (true o false)
  //      changeYear: true, //permite modificar los años (true o false)
  //      //yearRange: (fecha_hoy.getFullYear()-70)+':'+fecha_hoy.getFullYear(),
  //      numberOfMonths: 1 //muestra mas de un mes en el calendario, depende del numero
  //    });

  $("#dcliente").autocomplete({
      source: base_url+'panel/clientes/ajax_get_proveedores/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#did_cliente").val(ui.item.id);
        createInfoCliente(ui.item.item);
        $("#dcliente").css("background-color", "#B0FFB0");

        $('#dplazo_credito').val(ui.item.item.dias_credito);
      }
  }).on("keydown", function(event){
      if(event.which == 8 || event == 46){
        $("#dcliente").css("background-color", "#FFD9B3");
        $("#did_cliente").val("");
        $("#dcliente_rfc").val("");
        $("#dcliente_domici").val("");
        $("#dcliente_ciudad").val("");
      }
  });

  $("#dempresa").autocomplete({
      source: base_url+'panel/facturacion/ajax_get_empresas_fac',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#did_empresa").val(ui.item.id);
        $("#dempresa").css("background-color", "#B0FFB0");

        $('#dversion').val(ui.item.item.version);
        $('#dcer_caduca').val(ui.item.item.cer_caduca);

        $('#dno_certificado').val(ui.item.item.no_certificado);

        loadSerieFolio(ui.item.id);
      }
  }).on("keydown", function(event){
      if(event.which == 8 || event == 46){
        $("#dempresa").css("background-color", "#FFD9B3");
        $("#did_empresa").val("");
        $('#dserie').html('');
        $("#dfolio").val("");
        $("#dno_aprobacion").val("");

        $('#dversion').val('');
        $('#dcer_caduca').val('');
        $('#dno_certificado').val('');
      }
  });

  autocompleteClasifi();
  autocompleteClasifiLive();

  if ($('#did_empresa').val() !== '') {
    loadFolio();
  }

  //==> Evento para cambiar la forma de pago
  $("#dforma_pago").on("change", function(){
    if($(this).val() == "Pago en parcialidades"){
      $("#dforma_pago_parci_grup").show();
      $("#dforma_pago_parcialidad").focus();
    }else
      $("#dforma_pago_parci_grup").hide();
  });


  // Elimina un prod del listado
  $(document).on('click', 'button#delProd', function(e) {
      $(this).parent().parent().remove();
      calculaTotal();
  });


  // Asigna evento enter cuando dan click al input de importe.
  $('#table_prod').on('keypress', 'input#prod_importe', function(event) {
    event.preventDefault();

    if (event.which === 13) {
      var $tr = $(this).parent().parent();

      if (valida_agregar($tr)) {
        $tr.find('td').effect("highlight", {'color': '#99FF99'}, 500);
        addProducto();
      } else {
        $tr.find('#prod_ddescripcion').focus();
        $tr.find('td').effect("highlight", {'color': '#da4f49'}, 500);
        noty({"text": 'Verifique los datos del producto.', "layout":"topRight", "type": 'error'});
      }
    }
  });
  //Evento de key para calcular el total del producto
  $('#table_prod').on('keyup', '#prod_dcantidad, #prod_dpreciou', function(e) {
    var key = e.which,
        $this = $(this),
        $tr = $this.parent().parent();

    if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
      calculaTotalProducto($tr);
    }
  });
  // //Evento para calcular el total de producto iva
  // $('#table_prod').on('change', '#diva', function(event) {
  //   var $this = $(this),
  //       $tr = $this.parent().parent();

  //   $tr.find('#prod_diva_porcent').val($this.find('option:selected').val());

  //   calculaTotalProducto ($tr);
  // });
  // //Evento para calcular el total del producto retencion de iva
  // $('#table_prod').on('change', '#dreten_iva', function(event) {
  //   var $this = $(this),
  //       $tr = $this.parent().parent();

  //   $tr.find('#prod_dreten_iva_porcent').val($this.find('option:selected').val());

  //   calculaTotalProducto ($tr)
  // });
});


function loadFolio(){
  loader.create();
  $.getJSON(base_url+'panel/ventas/get_folio/?ide='+$('#did_empresa').val(),
  function(res){
    if(res.msg == 'ok'){
      $("#dfolio").val(res.data.folio);
      $("#dno_aprobacion").val(res.data.no_aprobacion);
      $("#dano_aprobacion").val(res.data.ano_aprobacion);
      $("#dimg_cbb").val(res.data.imagen);
    }else{
      $("#dfolio").val('');
      $("#dno_aprobacion").val('');
      $("#dano_aprobacion").val('');
      $("#dimg_cbb").val('');
      noty({"text":res.msg, "layout":"topRight", "type":res.ico});
    }
    loader.close();
  });
}


function calculaTotalProducto ($tr) {

  var $cantidad   = $tr.find('#prod_dcantidad'),
      $precio_uni = $tr.find('#prod_dpreciou'),
      $iva        = 0,
      $retencion  = 0,
      $importe    = $tr.find('#prod_importe'),

      $totalIva       = $tr.find('#prod_diva_total'),
      $totalRetencion = $tr.find('#prod_dreten_iva_total'),

      totalImporte   = trunc2Dec(parseFloat(($cantidad.val() || 0) * parseFloat($precio_uni.val() || 0))),
      totalIva       = trunc2Dec(((totalImporte) * parseFloat($iva)) / 100),
      totalRetencion = trunc2Dec(totalIva * parseFloat($retencion));

  $totalIva.val(totalIva);
  $totalRetencion.val(totalRetencion);
  $importe.val(totalImporte);

  calculaTotal();
}

var jumpIndex = 0;
function addProducto() {

  var $tabla = $('#table_prod'),
      trHtml = '',
      indexJump = jumpIndex + 1;

  trHtml = '<tr>' +
              '<td>' +
                '<input type="text" name="prod_ddescripcion[]" value="" id="prod_ddescripcion" class="span12 jump'+(++jumpIndex)+'" data-next="jump'+(++jumpIndex)+'">' +
                '<input type="hidden" name="prod_did_prod[]" value="" id="prod_did_prod" class="span12">' +
              '</td>' +
              '<td><input type="text" name="prod_dmedida[]" value="" id="prod_dmedida" class="span12 jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'"></td>' +
              '<td>' +
                  '<input type="text" name="prod_dcantidad[]" value="0" id="prod_dcantidad" class="span12 vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
              '</td>' +
              '<td>' +
                '<input type="text" name="prod_dpreciou[]" value="0" id="prod_dpreciou" class="span12 vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
              '</td>' +
              '<td>' +
                '<input type="text" name="prod_importe[]" value="0" id="prod_importe" class="span12 vpositive jump'+jumpIndex+'">' +
              '</td>' +
              '<td><button type="button" class="btn btn-danger" id="delProd"><i class="icon-remove"></i></button></td>' +
            '</tr>';


  var tradd = $(trHtml).appendTo($tabla.find('tbody'));
  $(".vpositive", tradd).numeric({ negative: false }); //Numero positivo
  
  for (i = indexJump, max = jumpIndex; i <= max; i += 1)
    $.fn.keyJump.setElem($('.jump'+i));

  $('.jump'+indexJump).focus();
}

function calculaTotal ($tr) {
  var total_importes = 0,
      total_descuentos = 0,
      total_ivas = 0,
      total_retenciones = 0,
      total_factura = 0;

  $('input#prod_importe').each(function(i, e) {
    total_importes += parseFloat($(this).val());
  });

  // $('input#prod_ddescuento').each(function(i, e) {
  //   total_descuentos += parseFloat($(this).val());
  // });

  var total_subtotal = parseFloat(total_importes) - parseFloat(total_descuentos);

  // $('input#prod_diva_total').each(function(i, e) {
  //   total_ivas += parseFloat($(this).val());
  // });

  // $('input#prod_dreten_iva_total').each(function(i, e) {
  //   total_retenciones += parseFloat($(this).val());
  // });

  total_factura = parseFloat(total_subtotal) + (parseFloat(total_ivas) - parseFloat(total_retenciones));

  $('#importe-format').html(util.darFormatoNum(total_importes));
  $('#total_importe').val(total_importes);

  // $('#descuento-format').html(util.darFormatoNum(total_descuentos));
  // $('#total_descuento').val(total_descuentos);

  // $('#subtotal-format').html(util.darFormatoNum(total_subtotal));
  // $('#total_subtotal').val(total_subtotal);

  // $('#iva-format').html(util.darFormatoNum(total_ivas));
  // $('#total_iva').val(total_ivas);

  // $('#retiva-format').html(util.darFormatoNum(total_retenciones));
  // $('#total_retiva').val(total_retenciones);

  $('#totfac-format').html(util.darFormatoNum(total_factura));
  $('#total_totfac').val(total_factura);

  $('#total_letra').val(util.numeroToLetra.covertirNumLetras(total_factura.toString()))

}


/**
 * Crea una cadena con la informacion del cliente para mostrarla
 * cuando se seleccione
 * @param item
 * @returns {String}
 */
function createInfoCliente(item){
  var info = '', info2 = '';

  console.log(item);

  info += item.calle!=''? item.calle: '';
  info += item.no_exterior!=''? ' #'+item.no_exterior: '';
  info += item.no_interior!=''? '-'+item.no_interior: '';
  info += item.colonia!=''? ', '+item.colonia: '';
  // info += item.localidad!=''? ', '+item.localidad: '';

  info2 += item.municipio!=''? item.municipio: '';
  info2 += item.estado!=''? ', '+item.estado: '';
  info2 += item.cp!=''? ', CP: '+item.cp: '';

  $("#dcliente_rfc").val(item.rfc);
  $("#dcliente_domici").val(info);
  $("#dcliente_ciudad").val(info2);
}


function autocompleteClasifi () {
 $("input#prod_ddescripcion").autocomplete({
    source: base_url+'panel/facturacion/ajax_get_clasificaciones/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      var $this = $(this),
          $tr = $this.parent().parent();

      $this.css("background-color", "#B0FFB0");

      $tr.find('#prod_did_prod').val(ui.item.id);
      // $tr.find('#prod_dpreciou').val(ui.item.item.precio);

    }
  }).keydown(function(event){
      if(event.which == 8 || event == 46){
        var $tr = $(this).parent().parent();

        $(this).css("background-color", "#FFD9B3");
        $tr.find('#prod_did_prod').val('');
      }
  });
}

function autocompleteClasifiLive () {
  $('#table_prod').on('focus', 'input#prod_ddescripcion:not(.ui-autocomplete-input)', function(event) {
    $(this).autocomplete({
      source: base_url+'panel/facturacion/ajax_get_clasificaciones/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $this = $(this),
            $tr = $this.parent().parent();

        $this.css("background-color", "#B0FFB0");

        $tr.find('#prod_did_prod').val(ui.item.id);
        // $tr.find('#prod_dpreciou').val(ui.item.item.precio);
      }
    }).keydown(function(event){
      if(event.which == 8 || event == 46) {
        var $tr = $(this).parent().parent();

        $(this).css("background-color", "#FFD9B3");
        $tr.find('#prod_did_prod').val('');
      }
    });
  });
}

function valida_agregar ($tr) {
  // $tr.find("#prod_did_prod").val() === '' ||

  if ($tr.find("#prod_dmedida").val() === '' || $tr.find("#prod_dcantidad").val() == 0 ||
      $tr.find("#prod_dpreciou").val() == 0) {
    return false;
  }
  else return true;
}

/**
 * Modificacion del plugin autocomplete
 */
$.widget( "custom.catcomplete", $.ui.autocomplete, {
  _renderMenu: function( ul, items ) {
    var self = this,
      currentCategory = "";
    $.each( items, function( index, item ) {
      if(item.category != undefined){
        if ( item.category != currentCategory ) {
          ul.append( "<li class='ui-autocomplete-category'>" + item.category + "</li>" );
          currentCategory = item.category;
        }
      }
      self._renderItem( ul, item );
    });
  }
});

function trunc2Dec(num) {
  return Math.floor(num * 100) / 100;
}

function round2Dec(val) {
  return Math.round(val * 100) / 100;
}