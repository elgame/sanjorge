<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class polizas_model extends CI_Model {

  function __construct()
  {
    parent::__construct();
  }

  public function getCuentaIvaTrasladado($basic=true){
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE nivel = 4 AND nombre like 'IVA TRASLADADO'")->row();
    return $basic? $data->cuenta: $data;
  }
  public function getCuentaIvaXTrasladar($basic=true){
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE nivel = 4 AND nombre like 'IVA X TRASLADAR'")->row();
    return $basic? $data->cuenta: $data;
  }
  public function getCuentaIvaRetCobradoAc($basic=true){
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_padre = 39 AND nivel = 4 AND nombre like 'IVA RETENIDO COBRADO'")->row();
    return $basic? $data->cuenta: $data;
  }
  public function getCuentaIvaRetXCobrarAc($basic=true){
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_padre = 39 AND nivel = 4 AND nombre like 'IVA RETENIDO X COBRAR'")->row();
    return $basic? $data->cuenta: $data;
  }
  public function getCuentaNCVenta($basic=true){
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_padre = 39 AND nivel = 4 AND nombre like 'IVA RETENIDO X COBRAR'")->row();
    return $basic? $data->cuenta: $data;
  }
  public function getCuentaIvaXAcreditar($basic=true){
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_padre = 231 AND nivel = 4 AND nombre like '%IVA ACREDITABLE PO%'")->row();
    return $basic? $data->cuenta: $data;
  }
  public function getCuentaIvaAcreditado($basic=true){
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_padre = 231 AND nivel = 4 AND nombre like '%IVA ACREDITABLE PA%'")->row();
    return $basic? $data->cuenta: $data;
  }
  public function getCuentaIvaRetXPagar($basic=true){
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_padre = 1191 AND nivel = 4 AND nombre like '4% RETENCION IVA X PAGAR'")->row();
    return $basic? $data->cuenta: $data;
  }
  public function getCuentaIvaRetPagado($basic=true){
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_padre = 1191 AND nivel = 4 AND nombre like '4% RETENCION IVA PAGADO'")->row();
    return $basic? $data->cuenta: $data;
  }

  public function setEspacios($texto, $posiciones, $direccion='l')
  {
    $len   = strlen($texto);
    $texto = $len>$posiciones? substr($texto, 0, $posiciones): $texto;
    $len   = strlen($texto);
    $faltante = $posiciones-$len;
    $espacios = '';
    if ($faltante > 0) 
      for ($i=0; $i < $faltante; $i++)
        $espacios .= ' ';

    if($direccion=='l')
      $texto .= $espacios;
    else
      $texto = $espacios.$texto;

    return $texto.' ';
  }
  public function numero($numero)
  {
    $numero = str_replace(',', '', String::formatoNumero($numero, 2, '') );
    $num = explode('.', $numero);
    if(!isset($num[1]))
      $numero .= '.0';
    return $numero;
  }

   /**
    * OBTIENE EL FOLIO PARA LA POLIZA CON RANGOS DE ACUERDO AL TIPO
    * @return [type]
    */
  public function getFolio($tipo=null, $tipo2=null){
    $tipo  = $tipo!=null? $tipo: $this->input->post('tipo');
    $tipo2 = $tipo2!=null? $tipo2: $this->input->post('tipo2');
    $rangos = array(
      'diario_ventas'    => array(1, 50),
      'diario_ventas_nc' => array(51, 100),
      'diario_gastos'    => array(101, 200),
      'nomina'           => array(201, 250),
      'ingresos'         => array(251, 350),
      );

    $response = array('folio' => '', 'concepto' => '');
    $rango_sel = '';
    $sql = '';
    if ($tipo == '3') //Diarios
    {
      $response['concepto'] = "Gastos del dia ".String::fechaATexto(date("Y-m-d"));
      $rango_sel         = 'diario_gastos';
      if ($tipo2 == 'v')
      {
        $response['concepto'] = "Ventas del dia ".String::fechaATexto(date("Y-m-d"));
        $rango_sel         = 'diario_ventas';
      }elseif ($tipo2 == 'vnc') 
      {
        $response['concepto'] = "Notas de Credito del dia ".String::fechaATexto(date("Y-m-d"));
        $rango_sel         = 'diario_ventas_nc';
      }
      $sql = " AND tipo = {$tipo} AND tipo2 = '{$tipo2}'";
    }elseif ($tipo == '1') //Ingresos
    {
      $rango_sel = 'ingresos';
      $sql       = " AND tipo = {$tipo}";
    }else{ //Egresos = 2
      $rango_sel = 'egresos';
      $sql       = " AND tipo = {$tipo}";
    }

    $anio = date("Y"); $mes = date("m");
    $result = $this->db->query("SELECT * FROM polizas
                               WHERE extract(year FROM fecha) = '{$anio}' AND extract(month FROM fecha) = '{$mes}' 
                                {$sql} ORDER BY id_poliza DESC LIMIT 1");
    $folio = $rangos[$rango_sel][0];
    if ($result->num_rows() > 0) {
      $row = $result->row();
      $folio = $row->folio+1;
    }

    if($folio > $rangos[$rango_sel][1])
      $folio = '';
    $response['folio'] = $folio;
    return $response;
  }

  /**
   * GENERA UNA POLIZA DE DIARIOS PARA LAS VENTAS
   * @return [type] [description]
   */
  public function polizaDiarioVentas()
  {
    $response = array('data' => '', 'facturas' => array());
    $sql = $sql2 = '';

    if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
      $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
      $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
    }
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
      $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
      $sql .= " AND Date(f.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
    }

    if ($this->input->get('fid_empresa') != '')
      $sql .= " AND f.id_empresa = '".$_GET['fid_empresa']."'";

    $query = $this->db->query(
      "SELECT id_factura
       FROM facturacion AS f
      WHERE status <> 'ca' AND status <> 'b' 
          AND poliza_diario = 'f' AND id_nc IS NULL 
         {$sql}
      ORDER BY id_factura ASC
      ");

    if($query->num_rows() > 0)
    {
      $data = $query->result();
      $response['facturas'] = $data;

      $this->load->model('facturacion_model');

      $impuestos = array('iva_trasladar' => array('cuenta_cpi' => $this->getCuentaIvaXTrasladar(), 'importe' => 0, 'tipo' => '1'),
                         'iva_retenido' => array('cuenta_cpi' => $this->getCuentaIvaRetXCobrarAc(), 'importe' => 0, 'tipo' => '0'), );

      //Agregamos el header de la poliza
      $response['data'] = $this->setEspacios('P',2).
                          $this->setEspacios(date("Ymd"),8).$this->setEspacios('3',4,'r').  //tipo poliza = 3 poliza diarios
                          $this->setEspacios($this->input->get('ffolio'),9,'r').  //folio poliza
                          $this->setEspacios('1',1). //clase
                          $this->setEspacios('0',10). //iddiario
                          $this->setEspacios($this->input->get('fconcepto'),100). //concepto
                          $this->setEspacios('11',2). //sistema de origen
                          $this->setEspacios('0',1). //impresa
                          $this->setEspacios('0',1)."\n"; //ajuste
      //Contenido de la Poliza
      foreach ($data as $key => $value) 
      {
        $inf_factura = $this->facturacion_model->getInfoFactura($value->id_factura);

        //Colocamos el Cargo al Cliente de la factura
        $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($inf_factura['info']->cliente->cuenta_cpi,30).  //cuenta contpaq
                          $this->setEspacios($inf_factura['info']->serie.$inf_factura['info']->folio,10).  //referencia movimiento
                          $this->setEspacios('0',1).  //tipo movimiento, clientes es un cargo = 0
                          $this->setEspacios( $this->numero($inf_factura['info']->total) , 20).  //importe movimiento - retencion
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios('FAC No. '.$inf_factura['info']->serie.$inf_factura['info']->folio, 100). //concepto
                          $this->setEspacios('',4)."\n"; //segmento de negocio
        
        $impuestos['iva_trasladar']['importe'] = 0;
        $impuestos['iva_retenido']['importe']  = 0;
        //Colocamos los Ingresos de la factura
        foreach ($inf_factura['productos'] as $key => $value) 
        {
          $impuestos['iva_trasladar']['importe'] += $value->iva;
          $impuestos['iva_retenido']['importe']  += $value->retencion_iva;
          $response['data'] .= $this->setEspacios('M',2).
                          $this->setEspacios($value->cuenta_cpi,30).
                          $this->setEspacios($inf_factura['info']->serie.$inf_factura['info']->folio,10).
                          $this->setEspacios('1',1).  //clientes es un abono = 1
                          $this->setEspacios( $this->numero($value->importe) , 20).
                          $this->setEspacios('0',10).
                          $this->setEspacios('0.0',20).
                          $this->setEspacios('FAC No. '.$inf_factura['info']->serie.$inf_factura['info']->folio,100).
                          $this->setEspacios('',4)."\n";
        }
        //Colocamos los impuestos de la factura
        foreach ($impuestos as $key => $impuesto) 
        {
          if ($impuestos[$key]['importe'] > 0) 
          {
            $response['data'] .= $this->setEspacios('M',2).
                          $this->setEspacios($impuesto['cuenta_cpi'],30).
                          $this->setEspacios($inf_factura['info']->serie.$inf_factura['info']->folio,10).
                          $this->setEspacios($impuesto['tipo'],1).  //clientes es un abono = 1
                          $this->setEspacios( $this->numero($impuesto['importe']) , 20).
                          $this->setEspacios('0',10).
                          $this->setEspacios('0.0',20).
                          $this->setEspacios('FAC No. '.$inf_factura['info']->serie.$inf_factura['info']->folio,100).
                          $this->setEspacios('',4)."\n";
          }
        }
        unset($inf_factura);
      }
    }

    return $response;
  }

  /**
   * GENERA UNA POLIZA DE DIARIOS PARA LAS NOTAS DE CREDITO DE VENTAS
   * @return [type] [description]
   */
  public function polizaDiarioVentasNC()
  {
    $response = array('data' => '', 'facturas' => array());
    $sql = $sql2 = '';

    if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
      $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
      $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
    }
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
      $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
      $sql .= " AND Date(f.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
    }

    if ($this->input->get('fid_empresa') != '')
      $sql .= " AND f.id_empresa = '".$_GET['fid_empresa']."'";

    $query = $this->db->query(
      "SELECT id_factura
       FROM facturacion AS f
      WHERE status <> 'ca' AND status <> 'b' 
          AND poliza_diario = 'f' AND id_nc IS NOT NULL 
         {$sql}
      ORDER BY id_factura ASC
      ");

    if($query->num_rows() > 0)
    {
      $data = $query->result();
      $response['facturas'] = $data;

      $this->load->model('facturacion_model');

      $impuestos = array('iva_trasladar' => array('cuenta_cpi' => $this->getCuentaIvaTrasladado(), 'importe' => 0, 'tipo' => '1'),
                         'iva_retenido' => array('cuenta_cpi' => $this->getCuentaIvaRetXCobrarAc(), 'importe' => 0, 'tipo' => '0'), );

      //Agregamos el header de la poliza
      $response['data'] = $this->setEspacios('P',2).
                          $this->setEspacios(date("Ymd"),8).$this->setEspacios('3',4,'r').  //tipo poliza = 3 poliza diarios
                          $this->setEspacios($this->input->get('ffolio'),9,'r').  //folio poliza
                          $this->setEspacios('1',1). //clase
                          $this->setEspacios('0',10). //iddiario
                          $this->setEspacios($this->input->get('fconcepto'),100). //concepto
                          $this->setEspacios('11',2). //sistema de origen
                          $this->setEspacios('0',1). //impresa
                          $this->setEspacios('0',1)."\n"; //ajuste
      //Contenido de la Poliza
      foreach ($data as $key => $value) 
      {
        $inf_factura = $this->facturacion_model->getInfoFactura($value->id_factura);

        //Colocamos el Cargo al Cliente de la factura
        $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($inf_factura['info']->cliente->cuenta_cpi,30).  //cuenta contpaq
                          $this->setEspacios($inf_factura['info']->serie.$inf_factura['info']->folio,10).  //referencia movimiento
                          $this->setEspacios('1',1).  //tipo movimiento, clientes es un cargo = 1
                          $this->setEspacios( $this->numero($inf_factura['info']->total) , 20).  //importe movimiento - retencion
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios('NC No. '.$inf_factura['info']->serie.$inf_factura['info']->folio, 100). //concepto
                          $this->setEspacios('',4)."\n"; //segmento de negocio
        
        $impuestos['iva_trasladar']['importe'] = 0;
        $impuestos['iva_retenido']['importe']  = 0;
        //Colocamos los Ingresos de la factura
        foreach ($inf_factura['productos'] as $key => $value) 
        {
          $impuestos['iva_trasladar']['importe'] += $value->iva;
          $impuestos['iva_retenido']['importe']  += $value->retencion_iva;
          $response['data'] .= $this->setEspacios('M',2).
                          $this->setEspacios($this->getCuentaNCVenta(), 30).  //cuenta nc ventas
                          $this->setEspacios($inf_factura['info']->serie.$inf_factura['info']->folio,10).
                          $this->setEspacios('0',1).  //ingresos es un abono = 0
                          $this->setEspacios( $this->numero($value->importe) , 20).
                          $this->setEspacios('0',10).
                          $this->setEspacios('0.0',20).
                          $this->setEspacios('NC No. '.$inf_factura['info']->serie.$inf_factura['info']->folio,100).
                          $this->setEspacios('',4)."\n";
        }
        //Colocamos los impuestos de la factura, negativos por nota de credito
        foreach ($impuestos as $key => $impuesto) 
        {
          if ($impuestos[$key]['importe'] > 0) 
          {
            $response['data'] .= $this->setEspacios('M',2).
                          $this->setEspacios($impuesto['cuenta_cpi'],30).
                          $this->setEspacios($inf_factura['info']->serie.$inf_factura['info']->folio,10).
                          $this->setEspacios($impuesto['tipo'],1).  //clientes es un abono = 1
                          $this->setEspacios( '-'.$this->numero($impuesto['importe']) , 20).
                          $this->setEspacios('0',10).
                          $this->setEspacios('0.0',20).
                          $this->setEspacios('FAC No. '.$inf_factura['info']->serie.$inf_factura['info']->folio,100).
                          $this->setEspacios('',4)."\n";
          }
        }
        unset($inf_factura);
      }
    }

    return $response;
  }

  /**
   * GENERA UNA POLIZA DE DIARIOS PARA LOS GASTOS, COMPRAS
   * @return [type] [description]
   */
  public function polizaDiarioGastos()
  {
    $response = array('data' => '', 'facturas' => array());
    $sql = $sql2 = '';

    if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
      $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
      $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
    }
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
      $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
      $sql .= " AND Date(f.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
    }

    if ($this->input->get('fid_empresa') != '')
      $sql .= " AND f.id_empresa = '".$_GET['fid_empresa']."'";

    $query = $this->db->query(
      "SELECT id_compra
       FROM compras AS f
      WHERE status <> 'ca'
          AND poliza_diario = 'f'
         {$sql}
      ORDER BY id_compra ASC
      ");

    if($query->num_rows() > 0)
    {
      $data = $query->result();
      $response['facturas'] = $data;

      $this->load->model('compras_model');

      $impuestos = array('iva_acreditar' => array('cuenta_cpi' => $this->getCuentaIvaXAcreditar(), 'importe' => 0, 'tipo' => '0'),
                         'iva_retenido' => array('cuenta_cpi' => $this->getCuentaIvaRetXPagar(), 'importe' => 0, 'tipo' => '1'), );

      //Agregamos el header de la poliza
      $response['data'] = $this->setEspacios('P',2).
                          $this->setEspacios(date("Ymd"),8).$this->setEspacios('3',4,'r').  //tipo poliza = 3 poliza diarios
                          $this->setEspacios($this->input->get('ffolio'),9,'r').  //folio poliza
                          $this->setEspacios('1',1). //clase
                          $this->setEspacios('0',10). //iddiario
                          $this->setEspacios($this->input->get('fconcepto'),100). //concepto
                          $this->setEspacios('11',2). //sistema de origen
                          $this->setEspacios('0',1). //impresa
                          $this->setEspacios('0',1)."\n"; //ajuste
      //Contenido de la Poliza
      foreach ($data as $key => $value) 
      {
        $inf_compra = $this->compras_model->getInfoCompra($value->id_compra);

        //Colocamos el Cargo al Proveedor de la factura
        $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($inf_compra['info']->proveedor->cuenta_cpi,30).  //cuenta contpaq
                          $this->setEspacios($inf_compra['info']->serie.$inf_compra['info']->folio,10).  //referencia movimiento
                          $this->setEspacios('1',1).  //tipo movimiento, proveedor es un abono = 1
                          $this->setEspacios( $this->numero($inf_compra['info']->total) , 20).  //importe movimiento - retencion
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios('Compra No. '.$inf_compra['info']->serie.$inf_compra['info']->folio, 100). //concepto
                          $this->setEspacios('',4)."\n"; //segmento de negocio
        
        $impuestos['iva_acreditar']['importe'] = 0;
        $impuestos['iva_retenido']['importe']  = 0;
        //Colocamos los productos de la factura
        foreach ($inf_compra['productos'] as $key => $value) 
        {
          $impuestos['iva_acreditar']['importe'] += $value->iva;
          $impuestos['iva_retenido']['importe']  += $value->retencion_iva;
          $response['data'] .= $this->setEspacios('M',2).
                          $this->setEspacios($value->cuenta_cpi, 30).  //cuenta conpaq
                          $this->setEspacios($inf_compra['info']->serie.$inf_compra['info']->folio,10).
                          $this->setEspacios('0',1).  //cargo, = 0
                          $this->setEspacios( $this->numero($value->importe) , 20).
                          $this->setEspacios('0',10).
                          $this->setEspacios('0.0',20).
                          $this->setEspacios('Compra No. '.$inf_compra['info']->serie.$inf_compra['info']->folio,100).
                          $this->setEspacios('',4)."\n";
        }
        //Colocamos los impuestos de la factura, negativos por nota de credito
        foreach ($impuestos as $key => $impuesto) 
        {
          if ($impuestos[$key]['importe'] > 0) 
          {
            $response['data'] .= $this->setEspacios('M',2).
                          $this->setEspacios($impuesto['cuenta_cpi'],30).
                          $this->setEspacios($inf_compra['info']->serie.$inf_compra['info']->folio,10).
                          $this->setEspacios($impuesto['tipo'],1).  //de acuerdo al impuesto
                          $this->setEspacios( $this->numero($impuesto['importe']) , 20).
                          $this->setEspacios('0',10).
                          $this->setEspacios('0.0',20).
                          $this->setEspacios('Compra No. '.$inf_compra['info']->serie.$inf_compra['info']->folio,100).
                          $this->setEspacios('',4)."\n";
          }
        }
        unset($inf_compra);
      }
    }

    return $response;
  }

  /**
   * GENERA UNA POLIZA DE INGRESOS
   * @return [type] [description]
   */
  public function polizaIngreso()
  {
    $response = array('data' => '', 'abonos' => array());
    $sql = $sql2 = '';

    if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
      $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
      $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
    }
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
      $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
      $sql .= " AND Date(fa.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
    }

    if ($this->input->get('fid_empresa') != '')
      $sql .= " AND f.id_empresa = '".$_GET['fid_empresa']."'";

    $query = $this->db->query(
      "SELECT 
        fa.id_abono, f.id_factura, fa.ref_movimiento, fa.concepto, fa.total AS total_abono, 
        bc.cuenta_cpi, f.subtotal, f.total, f.importe_iva, f.retencion_iva, c.nombre_fiscal, 
        c.cuenta_cpi AS cuenta_cpi_cliente
      FROM facturacion AS f 
        INNER JOIN facturacion_abonos AS fa ON fa.id_factura = f.id_factura
        INNER JOIN banco_cuentas AS bc ON bc.id_cuenta = fa.id_cuenta 
        INNER JOIN clientes AS c ON c.id_cliente = f.id_cliente 
      WHERE f.status <> 'ca' AND f.status <> 'b' AND fa.poliza_ingreso = 'f'
         {$sql}
      ORDER BY fa.id_abono ASC
      ");

    if($query->num_rows() > 0)
    {
      $data = $query->result();
      $response['abonos'] = $data;

      $this->load->model('facturacion_model');

      $impuestos = array(
        'iva_trasladar'  => array('cuenta_cpi' => $this->getCuentaIvaXTrasladar(), 'importe' => 0, 'tipo' => '1'),
        'iva_trasladado' => array('cuenta_cpi' => $this->getCuentaIvaTrasladado(), 'importe' => 0, 'tipo' => '0'),
        'iva_retener'    => array('cuenta_cpi' => $this->getCuentaIvaRetXCobrarAc(), 'importe' => 0, 'tipo' => '1'),
        'iva_retenido'   => array('cuenta_cpi' => $this->getCuentaIvaRetCobradoAc(), 'importe' => 0, 'tipo' => '0'), );

      //Agregamos el header de la poliza
      $response['data'] = $this->setEspacios('P',2).
                          $this->setEspacios(date("Ymd"),8).$this->setEspacios('1',4,'r').  //tipo poliza = 1 poliza ingresos
                          $this->setEspacios($this->input->get('ffolio'),9,'r').  //folio poliza
                          $this->setEspacios('1',1). //clase
                          $this->setEspacios('0',10). //iddiario
                          $this->setEspacios($this->input->get('fconcepto'),100). //concepto
                          $this->setEspacios('11',2). //sistema de origen
                          $this->setEspacios('0',1). //impresa
                          $this->setEspacios('0',1)."\n"; //ajuste
      //Contenido de la Poliza
      foreach ($data as $key => $value) 
      {
        $factor = $value->total_abono*100/($value->total); //abono*100/total_factura
        $impuestos['iva_retener']['importe']    = $factor*$value->retencion_iva/100;
        $impuestos['iva_retenido']['importe']   = $impuestos['iva_retener']['importe'];

        $impuestos['iva_trasladar']['importe']  = $factor*($value->importe_iva)/100;
        $impuestos['iva_trasladado']['importe'] = $impuestos['iva_trasladar']['importe'];
        $subtotal = $value->total_abono;//-$impuestos['iva_retener']['importe']-$impuestos['iva_trasladar']['importe'];

        //Colocamos el Cargo al Banco que se deposito el dinero
        $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($value->cuenta_cpi,30).  //cuenta contpaq
                          $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                          $this->setEspacios('0',1).  //tipo movimiento, banco es un cargo = 0
                          $this->setEspacios( $this->numero($subtotal) , 20).  //importe movimiento
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios($value->nombre_fiscal,100). //concepto
                          $this->setEspacios('',4)."\n"; //segmento de negocio
        //Colocamos el Abono al Cliente que realizo el pago
        $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($value->cuenta_cpi_cliente,30).  //cuenta contpaq
                          $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                          $this->setEspacios('1',1).  //tipo movimiento, Cliente es un abono = 1
                          $this->setEspacios( $this->numero($subtotal), 20).  //importe movimiento
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios($value->concepto,100). //concepto
                          $this->setEspacios('',4)."\n"; //segmento de negocio
        
        //Colocamos los impuestos de la factura
        foreach ($impuestos as $key => $impuesto) 
        {
          if ($impuestos[$key]['importe'] > 0) 
          {
            $response['data'] .= $this->setEspacios('M',2).
                          $this->setEspacios($impuesto['cuenta_cpi'],30).
                          $this->setEspacios($value->ref_movimiento,10).
                          $this->setEspacios($impuesto['tipo'],1).  //clientes es un abono = 1
                          $this->setEspacios( $this->numero($impuesto['importe']) , 20).
                          $this->setEspacios('0',10).
                          $this->setEspacios('0.0',20).
                          $this->setEspacios($value->concepto,100).
                          $this->setEspacios('',4)."\n";
          }
        }
      }
    }

    return $response;
  }

  /**
   * GENERA UNA POLIZA DE EGRESO
   * @return [type] [description]
   */
  public function polizaEgreso()
  {
    $response = array('data' => '', 'abonos' => array());
    $sql = $sql2 = '';

    if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
      $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
      $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
    }
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
      $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
      $sql .= " AND Date(fa.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
    }

    if ($this->input->get('fid_empresa') != '')
      $sql .= " AND f.id_empresa = '".$_GET['fid_empresa']."'";

    $query = $this->db->query(
      "SELECT 
        fa.id_abono, f.id_compra, fa.ref_movimiento, fa.concepto, fa.total AS total_abono, 
        bc.cuenta_cpi, f.subtotal, f.total, f.importe_iva, f.retencion_iva, c.nombre_fiscal, 
        c.cuenta_cpi AS cuenta_cpi_proveedor
      FROM compras AS f 
        INNER JOIN compras_abonos AS fa ON fa.id_compra = f.id_compra
        INNER JOIN banco_cuentas AS bc ON bc.id_cuenta = fa.id_cuenta 
        INNER JOIN proveedores AS c ON c.id_proveedor = f.id_proveedor 
      WHERE f.status <> 'ca' AND fa.poliza_egreso = 'f'
         {$sql}
      ORDER BY fa.id_abono ASC
      ");

    if($query->num_rows() > 0)
    {
      $data = $query->result();
      $response['abonos'] = $data;

      $this->load->model('facturacion_model');

      $impuestos = array(
        'iva_acreditar'  => array('cuenta_cpi' => $this->getCuentaIvaXAcreditar(), 'importe' => 0, 'tipo' => '1'),
        'iva_acreditado' => array('cuenta_cpi' => $this->getCuentaIvaAcreditado(), 'importe' => 0, 'tipo' => '0'),
        'iva_retener'    => array('cuenta_cpi' => $this->getCuentaIvaRetXPagar(), 'importe' => 0, 'tipo' => '0'),
        'iva_retenido'   => array('cuenta_cpi' => $this->getCuentaIvaRetPagado(), 'importe' => 0, 'tipo' => '1'), );

      //Agregamos el header de la poliza
      $response['data'] = $this->setEspacios('P',2).
                          $this->setEspacios(date("Ymd"),8).$this->setEspacios('2',4,'r').  //tipo poliza = 2 poliza egreso
                          $this->setEspacios($this->input->get('ffolio'),9,'r').  //folio poliza
                          $this->setEspacios('1',1). //clase
                          $this->setEspacios('0',10). //iddiario
                          $this->setEspacios($this->input->get('fconcepto'),100). //concepto
                          $this->setEspacios('11',2). //sistema de origen
                          $this->setEspacios('0',1). //impresa
                          $this->setEspacios('0',1)."\n"; //ajuste
      //Contenido de la Poliza
      foreach ($data as $key => $value) 
      {
        $factor = $value->total_abono*100/($value->total); //abono*100/total_factura
        $impuestos['iva_retener']['importe']    = $factor*$value->retencion_iva/100;
        $impuestos['iva_retenido']['importe']   = $impuestos['iva_retener']['importe'];

        $impuestos['iva_acreditar']['importe']  = $factor*($value->importe_iva)/100;
        $impuestos['iva_acreditado']['importe'] = $impuestos['iva_acreditar']['importe'];
        $subtotal = $value->total_abono;//-$impuestos['iva_retener']['importe']-$impuestos['iva_acreditar']['importe'];

        //Colocamos el Cargo al Banco que se deposito el dinero
        $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($value->cuenta_cpi,30).  //cuenta contpaq
                          $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                          $this->setEspacios('0',1).  //tipo movimiento, banco es un cargo = 0
                          $this->setEspacios( $this->numero($subtotal) , 20).  //importe movimiento
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios($value->nombre_fiscal,100). //concepto
                          $this->setEspacios('',4)."\n"; //segmento de negocio
        //Colocamos el Abono al Proveedor que realizo el pago
        $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($value->cuenta_cpi_proveedor,30).  //cuenta contpaq
                          $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                          $this->setEspacios('0',1).  //tipo movimiento, Proveedor es un abono = 0
                          $this->setEspacios( $this->numero($subtotal), 20).  //importe movimiento
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios($value->concepto,100). //concepto
                          $this->setEspacios('',4)."\n"; //segmento de negocio
        
        //Colocamos los impuestos de la factura
        foreach ($impuestos as $key => $impuesto) 
        {
          if ($impuestos[$key]['importe'] > 0) 
          {
            $response['data'] .= $this->setEspacios('M',2).
                          $this->setEspacios($impuesto['cuenta_cpi'],30).
                          $this->setEspacios($value->ref_movimiento,10).
                          $this->setEspacios($impuesto['tipo'],1).  //clientes es un abono = 1
                          $this->setEspacios( $this->numero($impuesto['importe']) , 20).
                          $this->setEspacios('0',10).
                          $this->setEspacios('0.0',20).
                          $this->setEspacios($value->concepto,100).
                          $this->setEspacios('',4)."\n";
          }
        }
      }
    }

    return $response;
  }

  

   /**
    * Visualiza/Descarga el PDF para el Reporte Diario de Entradas.
    *
    * @return void
    */
  public function generaPoliza()
  {
    $response = array('data' => '', 'facturas' => array());
    if ($this->input->get('ftipo') == '3') //******Polizas Diario
    {
      if($this->input->get('ftipo2') == 'v') //**Diario de ventas
      {
        $response = $this->polizaDiarioVentas();

        //actualizamos el estado de la factura y descarga el archivo
        if (isset($_POST['poliza']{0})) 
        {
          $idsf = array();
          foreach ($response['facturas'] as $key => $value) 
            $idsf[] = $value->id_factura;
          if(count($idsf) > 0)
          {
            $this->db->where_in('id_factura', $idsf);
            $this->db->update('facturacion', array('poliza_diario' => 't'));

            $_GET['poliza_nombre'] = 'polizadiario.txt';
            file_put_contents(APPPATH.'media/polizas/polizadiario.txt', $response['data']);
            $this->addPoliza($response['data']); //se registra la poliza en la BD
          }
        }
      }elseif($this->input->get('ftipo2') == 'vnc') //**Diario de notas de credito de Ventas
      {
        $response = $this->polizaDiarioVentasNC();

        //actualizamos el estado de la factura y descarga el archivo
        if (isset($_POST['poliza']{0})) 
        {
          $idsf = array();
          foreach ($response['facturas'] as $key => $value) 
            $idsf[] = $value->id_factura;
          if(count($idsf) > 0)
          {
            $this->db->where_in('id_factura', $idsf);
            $this->db->update('facturacion', array('poliza_diario' => 't'));

            $_GET['poliza_nombre'] = 'polizadiarionc.txt';
            file_put_contents(APPPATH.'media/polizas/polizadiarionc.txt', $response['data']);
            $this->addPoliza($response['data']); //se registra la poliza en la BD
          }
        }
      }else{
        $response = $this->polizaDiarioGastos();
      }

      
    }elseif ($this->input->get('ftipo') == '1')  //*******Polizas Ingresos
    {
      $response = $this->polizaIngreso();
      //actualizamos el estado de los abonos de las facturas
      if (isset($_POST['poliza']{0})) 
      {
        $idsa = array();
        foreach ($response['abonos'] as $key => $value) 
          $idsa[] = $value->id_abono;
        if(count($idsa) > 0)
        {
          $this->db->where_in('id_abono', $idsa);
          $this->db->update('facturacion_abonos', array('poliza_ingreso' => 't'));

          $_GET['poliza_nombre'] = 'polizaingreso.txt';
          file_put_contents(APPPATH.'media/polizas/polizaingreso.txt', $response['data']);
          $this->addPoliza($response['data']); //se registra la poliza en la BD
        }
      }
    }elseif ($this->input->get('ftipo') == '2')  //*******Polizas Egreso
    {
      $response = $this->polizaEgreso();
      //actualizamos el estado de los abonos de las facturas
      if (isset($_POST['poliza']{0})) 
      {
        $idsa = array();
        foreach ($response['abonos'] as $key => $value) 
          $idsa[] = $value->id_abono;
        if(count($idsa) > 0)
        {
          $this->db->where_in('id_abono', $idsa);
          $this->db->update('facturacion_abonos', array('poliza_ingreso' => 't'));

          $_GET['poliza_nombre'] = 'polizaegreso.txt';
          file_put_contents(APPPATH.'media/polizas/polizaegreso.txt', $response['data']);
          $this->addPoliza($response['data']); //se registra la poliza en la BD
        }
      }
    }

    return $response;
  }

  public function addPoliza($txtpoliza){
    $data = array(
      'tipo'     => $this->input->get('ftipo'),
      'tipo2'    => $this->input->get('ftipo2'),
      'folio'    => $this->input->get('ffolio'),
      'concepto' => $this->input->get('fconcepto'),
      'poliza'   => $txtpoliza,
      );
    $this->db->insert('polizas', $data);
  }


}

/* End of file bascula_model.php */
/* Location: ./application/models/bascula_model.php */