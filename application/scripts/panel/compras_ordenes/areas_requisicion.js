(function (closure) {
  closure($, window);
})(function ($, window) {
  var objCodigoArea;

  $(function(){

    showCodigoArea();
    btnModalAreasSel();
    autocompleteCodigos();

  });

  var autocompleteCodigos = function () {
    $('#table-productos, .tblproductos0').on('focus', 'input.showCodigoAreaAuto:not(.ui-autocomplete-input)', function(event) {
      var iniCat = 0;
      if (parseInt($(this).attr('data-ini')) > 0)
        iniCat = $(this).attr('data-ini');
      $(this).autocomplete({
        source: base_url+'panel/catalogos_sft/ajax_get_codigosauto/?id_padre='+iniCat,
        // source: base_url+'panel/compras_areas/ajax_get_areasauto/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          var $this = $(this),
              $tr = $this.parent().parent();
          if ($this.is('.notr')) {
            $tr = $this.parent();
          }

          $this.css("background-color", "#B0FFB0");
          setTimeout(function(){
            if ($.trim(ui.item.item.codigo) != '')
              $this.val(ui.item.item.codigo);
          },100)

          $tr.find('.showCodigoAreaAutoId').val(ui.item.id);
          $tr.find('#codigoAreaId').val(ui.item.id);
          $tr.find('#codigoCampo').val('id_cat_codigos'); // campo del area new catalogo

          if ($this.attr('data-call') && $this.attr('data-call').length > 0)
            funciones[$this.attr('data-call')].call(this, $tr);
        }
      }).keydown(function(event){
        if(event.which == 8 || event == 46) {
          var $tr = $(this).parent().parent();

          $(this).css("background-color", "#FFD9B3");
          $tr.find('.showCodigoAreaAutoId').val('');
          $tr.find('#codigoAreaId').val('');
        }
      });
    });
  };

  var showCodigoArea = function() {
    $("#productos, .tblproductos0").on('click', '.showCodigoArea', function(event) {
      var $tr = $(this).parent().parent(),
      iniCat = null;
      if ($(this).is('.notr'))
        $tr = $(this).parent();
      if (parseInt($(this).attr('data-ini')) > 0)
        iniCat = $(this).attr('data-ini');

      objCodigoArea = $tr.find('.showCodigoAreaAuto');
      $("div[id^=tblAreas]").hide();
      getAjaxAreas(1, iniCat);
      $("#modalAreas").modal('show');
    });


    $("#modalAreas").on('click', '.areaClick', function(event) {
      getAjaxAreas($(this).attr('data-sig'), $(this).attr('data-id'));
    });
  };

  var btnModalAreasSel = function() {
    $("#btnModalAreasSel").on('click', function(event) {
      var passes = true,
          radioSel = $("#modalAreas input[name=modalRadioSel]:checked");

      if (radioSel.length == 0){
        passes = false;
        noty({"text": 'Selecciona un codigo de los listados', "layout":"topRight", "type": 'error'});
      }


      if (passes) {
        objCodigoArea.val(radioSel.attr('data-codfin'));
        objCodigoArea.parent().find('.showCodigoAreaAutoId').val(radioSel.val());
        objCodigoArea.parent().find('#codigoAreaId').val(radioSel.val());
        objCodigoArea.parent().find('#codigoCampo').val('id_cat_codigos'); // campo del area new catalogo

        if (objCodigoArea.attr('data-call') && objCodigoArea.attr('data-call').length > 0)
          funciones[objCodigoArea.attr('data-call')].call(this, objCodigoArea.parent().parent());

        $("#modalAreas").modal('hide');
        objCodigoArea = undefined;
      }

    });
  };

  var getAjaxAreas = function(area, padre) {
    $.getJSON(base_url+'panel/catalogos_sft/ajax_get_codigos',
    // $.getJSON(base_url+'panel/compras_areas/ajax_get_areas',
      {id_area: area, id_padre: padre},
      function(json, textStatus) {
        var html = '', attrval = '';
        for (var i = 0; i < json.length; i++) {
          attrval = json[i].codigo!=''? json[i].codigo: json[i].nombre;
          html += '<tr class="areaClick" data-id="'+json[i].id_area+'" data-sig="'+(parseInt(area)+1)+'">'+
                  '<td><input type="radio" name="modalRadioSel" value="'+json[i].id_area+'" data-codfin="'+attrval+'" data-uniform="false"></td>'+
                  '<td>'+json[i].codigo+'</td>'+
                  '<td>'+json[i].nombre+'</td>'+
                '</tr>';
        }
        $("#tblAreasDiv"+area).show();
        $("#tblAreasDiv"+area+" tbody").html(html);

        for (var i = parseInt(area)+1; i < 15; i++) {
          $("#tblAreasDiv"+i).hide();
        }
      }
    );
  };

  var funciones = {
    ComprasOrdenes: function ($tr) {
      msb.confirm('Estas seguro de modificar el area?', 'Ordenes de Compras', this, function(){
        var datos = "?id_orden="+$tr.find('#prodIdOrden').val()+'&id_area='+$tr.find('#codigoAreaId').val()+'&num_row='+$tr.find('#prodIdNumRow').val();
        $.get(base_url+'panel/compras_ordenes/ajax_cambia_area/'+datos,
        function() {
          console.log("dd");
        });
      });
    },
  };

});