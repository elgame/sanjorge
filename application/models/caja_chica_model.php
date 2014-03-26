<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class caja_chica_model extends CI_Model {

  public function get($fecha)
  {
    $info = array(
      'saldo_inicial' => 0,
      'ingresos'      => array(),
      'otros'         => array(),
      'remisiones'    => array(),
      'boletas'       => array(),
      'denominaciones' => array(),
      'gastos'        => array(),
      'categorias'    => array(),
    );

    // Obtiene el saldo incial.
    $ultimoSaldo = $this->db->query(
      "SELECT saldo
       FROM cajachica_efectivo
       WHERE fecha < '$fecha'
       ORDER BY fecha DESC
       LIMIT 1"
    );

    if ($ultimoSaldo->num_rows() > 0)
    {
      $info['saldo_inicial'] = $ultimoSaldo->result()[0]->saldo;
    }

    $ingresos = $this->db->query(
      "SELECT *
       FROM cajachica_ingresos
       WHERE fecha = '$fecha' AND otro = 'f'
       ORDER BY id_ingresos ASC"
    );

    if ($ingresos->num_rows() > 0)
    {
      $info['ingresos'] = $ingresos->result();
    }

    $otros = $this->db->query(
      "SELECT *
       FROM cajachica_ingresos
       WHERE fecha = '$fecha' AND otro = 't'
       ORDER BY id_ingresos ASC"
    );

    if ($otros->num_rows() > 0)
    {
      $info['otros'] = $otros->result();
    }

    // remisiones
    $remisiones = $this->db->query(
      "SELECT cr.id_remision, cr.monto, cr.observacion, f.folio
       FROM cajachica_remisiones cr
       INNER JOIN facturacion f ON f.id_factura = cr.id_remision
       WHERE cr.fecha = '$fecha'"
    );

    if ($remisiones->num_rows() > 0)
    {
      $info['remisiones'] = $remisiones->result();
    }

    // boletas
    $boletas = $this->db->query(
      "SELECT b.folio, pr.nombre_fiscal as proveedor, b.importe
       FROM bascula b
       INNER JOIN proveedores pr ON pr.id_proveedor = b.id_proveedor
       WHERE DATE(b.fecha_tara) = '$fecha' AND b.accion = 'p' AND b.status = 't'
       ORDER BY (b.folio) ASC"
    );

    if ($boletas->num_rows() > 0)
    {
      $info['boletas'] = $boletas->result();
    }

    // denominaciones
    $denominaciones = $this->db->query(
      "SELECT *
       FROM cajachica_efectivo
       WHERE fecha = '$fecha'"
    );

    if ($denominaciones->num_rows() === 0)
    {
      $denominaciones = new StdClass;
      $denominaciones->den_05 = 0;
      $denominaciones->den_1 = 0;
      $denominaciones->den_2 = 0;
      $denominaciones->den_5 = 0;
      $denominaciones->den_10 = 0;
      $denominaciones->den_20 = 0;
      $denominaciones->den_50 = 0;
      $denominaciones->den_100 = 0;
      $denominaciones->den_200 = 0;
      $denominaciones->den_500 = 0;
      $denominaciones->den_1000 = 0;
    }
    else
    {
      $denominaciones = $denominaciones->result()[0];
      $info['status'] = $denominaciones->status;
      $info['id'] = $denominaciones->id_efectivo;
    }

    foreach ($denominaciones as $den => $cantidad)
    {
      if (strrpos($den, 'den_') !== false)
      {
        switch ($den)
        {
          case 'den_05':
            $denominacion = '0.50';
            break;
          case 'den_1':
            $denominacion = '1.00';
            break;
          case 'den_2':
            $denominacion = '2.00';
            break;
          case 'den_5':
            $denominacion = '5.00';
            break;
          case 'den_10':
            $denominacion = '10.00';
            break;
          case 'den_20':
            $denominacion = '20.00';
            break;
          case 'den_50':
            $denominacion = '50.00';
            break;
          case 'den_100':
            $denominacion = '100.00';
            break;
          case 'den_200':
            $denominacion = '200.00';
            break;
          case 'den_500':
            $denominacion = '500.00';
            break;
          case 'den_1000':
            $denominacion = '1000.00';
            break;
        }

        $info['denominaciones'][] = array(
          'denominacion' => $denominacion,
          'cantidad'     => $cantidad,
          'total'        => floatval($denominacion) * $cantidad,
          'denom_abrev'  => $den,
        );
      }
    }

    // gastos
    $gastos = $this->db->query(
      "SELECT cg.id_gasto, cg.concepto, cg.fecha, cg.monto, cc.id_categoria, cc.nombre, cc.abreviatura
       FROM cajachica_gastos cg
       INNER JOIN cajachica_categorias cc ON cc.id_categoria = cg.id_categoria
       WHERE fecha = '$fecha'"
    );

    if ($gastos->num_rows() > 0)
    {
      $info['gastos'] = $gastos->result();
    }

    $info['categorias'] = $this->db->query(
    "SELECT id_categoria, nombre, abreviatura
     FROM cajachica_categorias
     WHERE status = 't'")->result();

    foreach ($info['categorias'] as $key => $categoria)
    {
      $categoria->importe = 0;
      foreach ($info['gastos'] as $gasto)
      {
        if ($gasto->id_categoria == $categoria->id_categoria)
        {
          $categoria->importe += floatval($gasto->monto);
        }
      }
    }

    return $info;

    echo "<pre>";
      var_dump($info);
    echo "</pre>";exit;
  }

  public function guardar($data)
  {
    $ingresos = array();

    // ingresos
    foreach ($data['ingreso_concepto'] as $key => $ingreso)
    {
      $ingresos[] = array(
        'concepto' => $ingreso,
        'monto'    => $data['ingreso_monto'][$key],
        'fecha'    => $data['fecha_caja_chica'],
        'otro'    => 'f'
      );
    }

    // Otros
    if (isset($data['otros_concepto']))
    {
      foreach ($data['otros_concepto'] as $key => $otro)
      {
        $ingresos[] = array(
          'concepto' => $otro,
          'monto'    => $data['otros_monto'][$key],
          'fecha'    => $data['fecha_caja_chica'],
          'otro'    => 't'
        );
      }
    }

    if (count($ingresos) > 0)
    {
      $this->db->delete('cajachica_ingresos', array('fecha' => $data['fecha_caja_chica']));
      $this->db->insert_batch('cajachica_ingresos', $ingresos);
    }

    // Remisiones
    $this->db->delete('cajachica_remisiones', array('fecha' => $data['fecha_caja_chica']));
    if (isset($data['remision_concepto']))
    {
      $remisiones = array();

      foreach ($data['remision_concepto'] as $key => $concepto)
      {
        $remisiones[] = array(
          'observacion' => $concepto,
          'id_remision' => $_POST['remision_id'][$key],
          'fecha'       => $data['fecha_caja_chica'],
          'monto'       => $_POST['remision_importe'][$key],
          'row'         => $key,
        );
      }

      $this->db->insert_batch('cajachica_remisiones', $remisiones);
    }

    // Denominaciones
    $this->db->delete('cajachica_efectivo', array('fecha' => $data['fecha_caja_chica']));
    $efectivo = array();
    foreach ($data['denom_abrev'] as $key => $denominacion)
    {
      $efectivo[$denominacion] = $data['denominacion_cantidad'][$key];
    }

    $efectivo['fecha'] = $data['fecha_caja_chica'];
    $efectivo['saldo'] = $data['saldo_corte'];

    $this->db->insert('cajachica_efectivo', $efectivo);

    // Gastos del dia
    $this->db->delete('cajachica_gastos', array('fecha' => $data['fecha_caja_chica']));
    if (isset($data['gasto_concepto']))
    {
      $gastos = array();
      foreach ($data['gasto_concepto'] as $key => $gasto)
      {
        $gastos[] = array(
          'concepto'     => $gasto,
          'id_categoria' => $_POST['gasto_cargo_id'][$key],
          'monto'        => $_POST['gasto_importe'][$key],
          'fecha'        => $data['fecha_caja_chica'],
        );
      }

      $this->db->insert_batch('cajachica_gastos', $gastos);
    }

    return true;
  }

  public function getRemisiones()
  {
    $remisiones = $this->db->query(
      "SELECT f.id_factura, DATE(f.fecha) as fecha, serie, folio, total, c.nombre_fiscal as cliente
       FROM facturacion f
       INNER JOIN clientes c ON c.id_cliente = f.id_cliente
       LEFT JOIN cajachica_remisiones cr ON cr.id_remision = f.id_factura
       WHERE is_factura = 'f'  AND f.status != 'ca' AND COALESCE(cr.id_remision, 0) = 0
       ORDER BY (f.fecha, f.serie, f.folio) DESC"
    );

    return $remisiones->result();
  }

  public function getCategorias($perpage = '40')
  {
    $sql = '';
    //paginacion
    $params = array(
        'result_items_per_page' => $perpage,
        'result_page'       => (isset($_GET['pag'])? $_GET['pag']: 0)
    );

    if($params['result_page'] % $params['result_items_per_page'] == 0)
      $params['result_page'] = ($params['result_page']/$params['result_items_per_page']);

    //Filtros para buscar
    if ($this->input->get('fstatus') != '')
    {
      $sql .= " AND status = '".$this->input->get('fstatus')."'";
    }

    $query = BDUtil::pagination(
        "SELECT id_categoria, nombre, status, abreviatura
        FROM cajachica_categorias
        WHERE 1 = 1 {$sql}
        ORDER BY (nombre) ASC
        ", $params, true);

    $res = $this->db->query($query['query']);

    $response = array(
        'categorias'     => array(),
        'total_rows'     => $query['total_rows'],
        'items_per_page' => $params['result_items_per_page'],
        'result_page'    => $params['result_page']
    );
    if($res->num_rows() > 0)
      $response['categorias'] = $res->result();

    return $response;
  }

  public function agregarCategoria($data)
  {
    $this->db->insert('cajachica_categorias', array(
      'nombre' => $data['nombre'],
      'abreviatura' => $data['abreviatura'],
    ));

    return true;
  }

  public function info($idCategoria)
  {
    $query = $this->db->query(
      "SELECT *
        FROM cajachica_categorias
        WHERE id_categoria = {$idCategoria}");

    $data = array();
    if ($query->num_rows() > 0)
    {
      $data['info'] = $query->result();
    }

    return $data;
  }

  public function modificarCategoria($categoriaId, $data)
  {
    $this->db->update('cajachica_categorias', array(
      'nombre' => $data['nombre'],
      'abreviatura' => $data['abreviatura'],
    ), array('id_categoria' => $categoriaId));

    return true;
  }

  public function elimimnarCategoria($categoriaId)
  {
    $this->db->update('cajachica_categorias', array('status' => 'f'), array('id_categoria' => $categoriaId));

    return true;
  }

  public function ajaxCategorias()
  {
    $sql = '';
    $res = $this->db->query("
        SELECT *
        FROM cajachica_categorias
        WHERE status = 't' AND lower(nombre) LIKE '%".mb_strtolower($_GET['term'], 'UTF-8')."%'
        ORDER BY abreviatura ASC
        LIMIT 20");

    $response = array();
    if($res->num_rows() > 0){
      foreach($res->result() as $itm){
        $response[] = array(
          'id' => $itm->id_categoria,
          'label' => $itm->abreviatura,
          'value' => $itm->abreviatura,
          'item' => $itm,
        );
      }
    }

    return $response;
  }

  public function cerrarCaja($idCaja)
  {
    $this->db->update('cajachica_efectivo', array('status' => 'f'), array('id_efectivo' => $idCaja));

    return true;
  }

  public function printCaja($fecha)
  {
    $caja = $this->get($fecha);

    // echo "<pre>";
    //   var_dump($caja);
    // echo "</pre>";exit;
    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->show_head = true;
    // $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    // $pdf->titulo1 S= $empresa['info']->nombre_fiscal;
    // $pdf->logo = $empresa['info']->logo;
    // $pdf->titulo2 = $empleado['info'][0]->nombre;
    $pdf->titulo2 = "Caja Chica del {$fecha}";
    $pdf->AliasNbPages();
    $pdf->AddPage();

    // Saldo inicial
    $pdf->SetFont('Arial','B', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(255, 255 ,255);
    $pdf->SetXY(6, $pdf->GetY() - 8);
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(125));
    $pdf->Row(array('SALDO INICIAL '.String::formatoNumero($caja['saldo_inicial'], 2, '$')), false, false);

    $ttotalGastos = 0;
    foreach ($caja['gastos'] as $gasto)
    {
      $ttotalGastos += floatval($gasto->monto);
    }

    // Reporte caja
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(210, 210, 210);
    $pdf->SetX(6);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(125));
    $pdf->Row(array('REPORTE CAJA "COMPRAS LIMON"'), true, true);

    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetXY(131, $pdf->GetY() - 5.5);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(77));
    $pdf->Row(array('DESGLOSE "OTROS INGRESOS"'), false, true);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R'));
    $pdf->SetWidths(array(19, 35, 21, 25, 25));

    $lastY = $pdf->GetY();

    $totalIngresos = 0;
    foreach ($caja['ingresos'] as $key => $ingreso)
    {
      $pdf->SetX(6);

      $pdf->Row(array(
        ($key === 0) ? 'INGRESOS:' : '',
        $ingreso->concepto,
        String::float(String::formatoNumero($ingreso->monto, 2, ''))), false, true);

      $totalIngresos += floatval($ingreso->monto);
    }

    foreach ($caja['otros'] as $key => $ingreso)
    {
      $pdf->SetX(6);

      $pdf->Row(array(
        ($key === 0) ? 'OTROS INGRESOS:' : '',
        $ingreso->concepto,
        String::float(String::formatoNumero($ingreso->monto, 2, ''))), false, true);

      $totalIngresos += floatval($ingreso->monto);
    }

    $comprasY = $pdf->GetY();

    $ttotalIngresos = $totalIngresos + $caja['saldo_inicial'];

    $pdf->SetX(6);
    $pdf->Row(array('', '', '', String::formatoNumero($totalIngresos, 2, '$'), String::formatoNumero($ttotalIngresos, 2, '$')), false, true);

    // Remisiones
    $pdf->SetY($lastY);

    $pdf->SetX(131);
    $pdf->SetAligns(array('L', 'L', 'R'));
    $pdf->SetWidths(array(41, 15, 21));

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(210, 210, 210);
    $pdf->Row(array('OBSV.', 'No. REM', 'IMPORTE'), true, true);

    $totalRemisiones = 0;
    foreach ($caja['remisiones'] as $key => $remision)
    {
      $pdf->SetX(131);

      $pdf->Row(array(
        $remision->observacion,
        $remision->folio,
        String::float(String::formatoNumero($remision->monto, 2, ''))), false, true);

      $totalRemisiones += floatval($remision->monto);
    }

    $pdf->SetX(131);
    $pdf->Row(array('', '', String::formatoNumero($totalRemisiones, 2, '$')), false, true);

    if ($comprasY >= $pdf->GetY())
    {
      $pdf->SetY($comprasY);
    }

    // Boletas
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(210, 210, 210);
    $pdf->SetXY(6, $pdf->GetY() + 5.2);
    $pdf->SetAligns(array('C', 'C', 'R', 'R'));
    $pdf->SetWidths(array(19, 56, 25, 25));
    $pdf->Row(array('BOLETA', 'PRODUCTOR', 'IMPORTE', ''), true, true);

    // $caja['boletas'] = array_merge($caja['boletas'], $caja['boletas']);

    $pdf->SetFont('Arial','', 7);
    $boletasY = $pdf->GetY();

    $totalBoletas = 0;
    foreach ($caja['boletas'] as $key => $boleta)
    {
      if($pdf->GetY() >= $pdf->limiteY){
        $pdf->AddPage();
        $pdf->SetFont('Helvetica','B', 7);
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->Row(array('BOLETA', 'PRODUCTOR', 'IMPORTE', ''), true, true);

        $boletasY = $pdf->GetY();
      }

      $pdf->SetFont('Helvetica','', 7);
      $pdf->SetX(6);

      $pdf->Row(array(
        $boleta->folio,
        $boleta->proveedor,
        String::float(String::formatoNumero($boleta->importe, 2, ''))), false, true);

      $totalBoletas += floatval($boleta->importe);
    }

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(6);
    $pdf->Row(array('', '', '', String::formatoNumero($totalBoletas, 2, '$')), false, true);

    $boletasLastY = $pdf->GetY();

    // Tabulaciones
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(210, 210, 210);
    $pdf->SetXY(131, $boletasY - 5.4);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(75));
    $pdf->Row(array('TABULACION DE EFECTIVO'), true, true);

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(210, 210, 210);
    // $pdf->SetXY(131, $boletasY - 5.4);
    $pdf->SetXY(131, $pdf->GetY() + 5);
    $pdf->SetAligns(array('C', 'C', 'C'));
    $pdf->SetWidths(array(25, 25, 25));
    $pdf->Row(array('NUMERO', 'DENOMINACION', 'TOTAL'), false, false);

    $pdf->SetAligns(array('R', 'R', 'R'));
    $pdf->SetFont('Arial','', 7);
    $totalEfectivo = 0;
    foreach ($caja['denominaciones'] as $key => $denominacion)
    {
      // if($pdf->GetY() >= $pdf->limiteY){
      //   $pdf->AddPage();
      //   $pdf->SetFont('Helvetica','B', 7);
      //   $pdf->SetXY(6, $pdf->GetY());
      //   $pdf->Row(array('BOLETA', 'PRODUCTOR', 'IMPORTE', ''), true, true);
      // }

      // $pdf->SetFont('Helvetica','', 7);
      $pdf->SetX(131);

      $pdf->Row(array(
        $denominacion['cantidad'],
        $denominacion['denominacion'],
        String::float(String::formatoNumero($denominacion['total'], 2, ''))), false, true);

      $totalEfectivo += floatval($denominacion['total']);
    }

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(131);
    $pdf->Row(array('', 'TOTAL EFECTIVO', String::formatoNumero($totalEfectivo, 2, '$')), false, true);

    $pdf->SetX(131);
    $pdf->Row(array('', 'DIFERENCIA', String::formatoNumero($totalEfectivo - ($ttotalIngresos - $totalBoletas - $ttotalGastos) , 2, '$')), false, true);

    if ($boletasLastY > $pdf->GetY())
    {
      $pdf->SetY($boletasLastY);
    }

    if($pdf->GetY() >= $pdf->limiteY){
      $pdf->AddPage();
    }

    // Gastos
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(210, 210, 210);
    $pdf->SetX(6);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(125));
    $pdf->Row(array('GASTOS DEL DIA'), true, true);

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(210, 210, 210);
    $pdf->SetX(6);
    $pdf->SetAligns(array('C', 'C', 'C', 'L', 'C', 'R'));
    $pdf->SetWidths(array(75, 25, 25, 25, 25, 25));
    $pdf->Row(array('CONCEPTO', 'CARGO', 'IMPORTE'), true, true);

    $pdf->SetAligns(array('L', 'C', 'R', 'L', 'R', 'R'));
    $pdf->SetFont('Arial','', 7);
    $totalGastos = 0;
    foreach ($caja['gastos'] as $key => $gasto)
    {
      if ($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $pdf->SetFont('Helvetica','B', 7);
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->Row(array('CONCEPTO', 'CARGO', 'IMPORTE'), true, true);
      }

      $pdf->SetX(6);

      $totalGastos += floatval($gasto->monto);

      if ($key === count($caja['gastos']) - 1)
      {
        $pdf->Row(array(
          $gasto->concepto,
          $gasto->abreviatura,
          String::float(String::formatoNumero($gasto->monto, 2, '')),
          '$ ' . String::float(String::formatoNumero($totalGastos, 2, '')),
          'Saldo Al Corte',
          '$ ' . String::float(String::formatoNumero($ttotalIngresos - $totalBoletas - $totalGastos, 2, ''))), false, true);
      }
      else
      {
        $pdf->Row(array(
          $gasto->concepto,
          $gasto->abreviatura,
          String::float(String::formatoNumero($gasto->monto, 2, ''))), false, true);
      }
    }

    // Reposicion por gastos
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(210, 210, 210);
    $pdf->SetX(6);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(125));
    $pdf->Row(array('REPOSICION DE GASTOS X CONCEPTOS'), true, true);

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(210, 210, 210);
    $pdf->SetX(6);
    $pdf->SetAligns(array('C', 'L', 'R', 'R'));
    $pdf->SetWidths(array(25, 50, 25, 25));
    // $pdf->Row(array('CONCEPTO', 'CARGO', 'IMPORTE'), true, true);

    $pdf->SetFont('Arial', '', 7);
    foreach ($caja['categorias'] as $key => $cate)
    {
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
      }

      $pdf->SetX(6);

      if ($key === count($caja['categorias']) - 1)
      {
        $pdf->Row(array(
          $key + 1,
          $cate->nombre,
          String::float(String::formatoNumero($cate->importe, 2, '')),
          '$ ' . String::float(String::formatoNumero($totalGastos, 2, ''))), false, true);
      }
      else
      {
        $pdf->Row(array(
          $key + 1,
          $cate->nombre,
          String::float(String::formatoNumero($cate->importe, 2, ''))), false, true);
      }
    }

    $pdf->Output('CAJA_CHICA.pdf', 'I');
  }

}

/* End of file caja_chica_model.php */
/* Location: ./application/models/caja_chica_model.php */