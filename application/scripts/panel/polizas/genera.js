$(function(){
  $('#fechaini').datepicker({
    dateFormat: 'yy-mm-dd', //formato de la fecha - dd,mm,yy=dia,mes,año numericos  DD,MM=dia,mes en texto
    //minDate: '-2Y', maxDate: '+1M +10D', //restringen a un rango el calendario - ej. +10D,-2M,+1Y,-3W(W=semanas) o alguna fecha
    changeMonth: true, //permite modificar los meses (true o false)
    changeYear: true, //permite modificar los años (true o false)
    //yearRange: (fecha_hoy.getFullYear()-70)+':'+fecha_hoy.getFullYear(),
    numberOfMonths: 1, //muestra mas de un mes en el calendario, depende del numero
  });

  $('#fechaend').datepicker({
    dateFormat: 'yy-mm-dd', //formato de la fecha - dd,mm,yy=dia,mes,año numericos  DD,MM=dia,mes en texto
    //minDate: '-2Y', maxDate: '+1M +10D', //restringen a un rango el calendario - ej. +10D,-2M,+1Y,-3W(W=semanas) o alguna fecha
    changeMonth: true, //permite modificar los meses (true o false)
    changeYear: true, //permite modificar los años (true o false)
    //yearRange: (fecha_hoy.getFullYear()-70)+':'+fecha_hoy.getFullYear(),
    numberOfMonths: 1, //muestra mas de un mes en el calendario, depende del numero
  });


  $("#ftipo").on("change", function(){
    var vthis = $(this);
    if(vthis.val() == '3')
      $("#grupftipo2").show();
    else
      $("#grupftipo2").hide();

    getFolioPoliza();
  });
  $("#ftipo2").on("change", function(){
    getFolioPoliza();
  });
});


function getFolioPoliza () {
  $.post(base_url+"panel/polizas/get_folio", 
    {
      'tipo'  : $("#ftipo").val(),
      'tipo2' : $("#ftipo2").val()
    }, function(data){
      if (data.folio == "")
        noty({"text":"Los folios para este tipo de poliza se teminaron", "layout":"topRight", "type":"error"});
      else
        $("#ffolio").val(data.folio);
    }, "json");
}

function newPoliza () {
  getFolioPoliza();
  $("#fconcepto").val("");
  noty({"text":"La poliza se genero correctamente", "layout":"topRight", "type":"success"});
}