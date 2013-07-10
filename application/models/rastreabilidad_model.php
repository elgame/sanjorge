<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class rastreabilidad_model extends CI_Model {

  function __construct()
  {
    parent::__construct();
  }

  public function saveClasificacion()
  {
    $data = array(
      'id_rendimiento'   => $_POST['id_rendimiento'],
      'id_clasificacion' => $_POST['id_clasificacion'],
      'existente'        => $_POST['existente'],
      'linea1'           => $_POST['linea1'],
      'linea2'           => $_POST['linea2'],
      'total'            => $_POST['total'],
      'rendimiento'      => $_POST['rendimiento'],
    );
    $this->db->insert('rastria_rendimiento_clasif', $data);

    $info = $this->getLoteInfo($_POST['id_rendimiento']);

    // Obtiene los lotes siguientes al lote de la clasificacion que se modifico
    $sql = $this->db->query(
      "SELECT id_rendimiento, lote
        FROM rastria_rendimiento
        WHERE fecha = '{$info['info']->fecha}' AND lote > {$info['info']->lote}
        ORDER BY lote ASC
      ");

    // Si existen lotes siguientes
    if ($sql->num_rows() > 0)
      $this->updateMasivoClasifi($sql->result(), $data['total']);

    return array('passess' => true);
  }

  /**
   * Edita una clasificacion de un lote y todas aquellas clasificaciones iguales
   * de los lotes siguientes.
   *
   * @return array
   */
  public function editClasificacion()
  {
    $data = array(
      'existente'        => $_POST['existente'],
      'linea1'           => $_POST['linea1'],
      'linea2'           => $_POST['linea2'],
      'total'            => $_POST['total'],
      'rendimiento'      => $_POST['rendimiento'],
    );

    // Actualiza los datos de la clasificacion
    $this->db->update('rastria_rendimiento_clasif',$data, array(
      'id_rendimiento'   => $_POST['id_rendimiento'],
      'id_clasificacion' => $_POST['id_clasificacion'])
    );

    // Elimina la clasificacion de los pallets
    $this->db->delete('rastria_pallets_rendimiento', array(
      'id_rendimiento'   => $_POST['id_rendimiento'],
      'id_clasificacion' => $_POST['id_clasificacion']
    ));

    // Obtiene la fecha y el lote de la clasificacion que se modifico.
    $res = $this->db->select("DATE(fecha) AS fecha, lote")
      ->from("rastria_rendimiento")
      ->where("id_rendimiento", $_POST['id_rendimiento'])
      ->get()->row();

    // Obtiene los lotes siguientes al lote de la clasificacion que se modifico
    $sql = $this->db->query(
      "SELECT id_rendimiento, lote
        FROM rastria_rendimiento
        WHERE fecha = '{$res->fecha}' AND lote > {$res->lote}
        ORDER BY lote ASC
      ");

    // Si existen lotes siguientes
    if ($sql->num_rows() > 0)
      $this->updateMasivoClasifi($sql->result(), $data['total']);

    return array('passess' => true);
  }

  /**
   * Elimina una clasificacion de la BDD.
   *
   * @return array
   */
  public function delClasificacion()
  {
    // Obtiene la fecha y el lote de la clasificacion que se elimino.
    $res = $this->db->select("DATE(fecha) AS fecha, lote")
      ->from("rastria_rendimiento")
      ->where("id_rendimiento", $_POST['id_rendimiento'])
      ->get()->row();

    // Se elimina la clasificacion
    $tables = array('rastria_rendimiento_clasif', 'rastria_pallets_rendimiento');
    $this->db->where(array(
      'id_rendimiento'   => $_POST['id_rendimiento'],
      'id_clasificacion' => $_POST['id_clasificacion'])
    );
    $this->db->delete($tables);

    // Obtiene los lotes anteriores al lote de la clasificacion que se elimino
    $sql = $this->db->query(
      "SELECT id_rendimiento, lote
        FROM rastria_rendimiento
        WHERE fecha = '{$res->fecha}' AND lote < {$res->lote}
        ORDER BY lote DESC
      ");

    $existente = 0;

    // Si existen lotes anteriores
    if ($sql->num_rows() > 0)
    {
      // Recorre los lotes para ver si tienen una clasificacion como la que se
      // elimino y si tienen entonces toma los datos de esa clasificacion como base
      // para recalcular los demas lotes.
      foreach ($sql->result() as $key => $lote)
      {
        $sql2 = $this->db->query(
          "SELECT id_rendimiento, id_clasificacion, existente, linea1, linea2,
                  total, rendimiento
            FROM rastria_rendimiento_clasif
            WHERE id_clasificacion = {$_POST['id_clasificacion']} AND id_rendimiento = {$lote->id_rendimiento}
        ");

        if ($sql2->num_rows() > 0)
        {
          $lote_iden = $sql2->result();

          $existente = $lote_iden[0]->total;

          break;
        }
      }
    }

    // Obtiene los lotes siguientes apartir del lote de la clasificacion
    // que se elimino.
    $sql3 = $this->db->query(
      "SELECT id_rendimiento, lote
        FROM rastria_rendimiento
        WHERE fecha = '{$res->fecha}' AND lote > {$res->lote}
        ORDER BY lote ASC
      ");

    if ($sql3->num_rows() > 0)
      $this->updateMasivoClasifi($sql3->result(), $existente);

    return array('passess' => true);
  }

  public function updateMasivoClasifi($lotes, $existente)
  {
    $existente = $existente;

    // Recorre los lotes para ver si tiene clasificaciones como las que se
    // modifico y si tienen entonces recalculan sus datos.
    foreach ($lotes as $key => $lote)
    {
      $sql2 = $this->db->query(
        "SELECT id_rendimiento, id_clasificacion, existente, linea1, linea2,
                total, rendimiento
        FROM rastria_rendimiento_clasif
        WHERE id_clasificacion = {$_POST['id_clasificacion']} AND id_rendimiento = {$lote->id_rendimiento}
      ");

      if ($sql2->num_rows() > 0)
      {
        $clasifi = $sql2->result();

        // Actualiza los datos de la clasificacion.
        $this->db->update('rastria_rendimiento_clasif',
          array(
            'existente' => $existente,
            'total'     => floatval($existente) + floatval($clasifi[0]->linea1) + floatval($clasifi[0]->linea2)
          ),
          array(
            'id_rendimiento'   => $clasifi[0]->id_rendimiento,
            'id_clasificacion' => $clasifi[0]->id_clasificacion
          )
        );

        // Elimina la clasificacion de la tabla de los pallets.
        $this->db->delete('rastria_pallets_rendimiento', array(
            'id_rendimiento'   => $clasifi[0]->id_rendimiento,
            'id_clasificacion' => $clasifi[0]->id_clasificacion
        ));

        $existente = floatval($existente) + floatval($clasifi[0]->linea1) + floatval($clasifi[0]->linea2);
      }

      $sql2->free_result();
    }
  }

  public function createLote($fecha, $lote)
  {
    $this->db->insert('rastria_rendimiento', array(
      'lote'  => $lote,
      'fecha' => $fecha,
    ));

    $id = $this->db->insert_id();

    return $id;
  }

  public function getLotesByFecha($fecha)
  {
    $sql = $this->db->query(
      "SELECT id_rendimiento, lote, fecha, status
      FROM rastria_rendimiento
      WHERE
        DATE(fecha) = '{$fecha}' AND
        status = true
      ORDER BY lote ASC
      ");

    $lotes = array();
    if ($sql->num_rows() > 0)
      $lotes = $sql->result();

    return $lotes;
  }

  public function getLoteInfo($id_rendimiento, $full_info = true)
  {
    $sql = $this->db->select("id_rendimiento, lote, DATE(fecha) AS fecha, status")
      ->from("rastria_rendimiento")
      ->where("id_rendimiento", $id_rendimiento)
      ->get();

    $data = array(
      "info" => array(),
      "clasificaciones" => array(),
    );

    if ($sql->num_rows > 0)
    {
      $data['info'] = $sql->row();

      if ($full_info)
      {
        $sql->free_result();

        $sql = $this->db->query(
          "SELECT rrc.id_rendimiento, rrc.id_clasificacion, rrc.existente, rrc.linea1, rrc.linea2,
                  rrc.total, rrc.rendimiento, cl.nombre as clasificacion
          FROM rastria_rendimiento_clasif AS rrc
          INNER JOIN clasificaciones AS cl ON cl.id_clasificacion = rrc.id_clasificacion
          WHERE
            id_rendimiento = {$id_rendimiento}
          ORDER BY id_rendimiento ASC
          ");

        if ($sql->num_rows() > 0)
          $data['clasificaciones'] = $sql->result();
      }
    }

    return $data;
  }

  /**
   * Obtiene los existentes de una clasificacion
   *
   * @param  string $id_rendimiento
   * @param  string $id_clasificacion
   * @return array
   */
  public function getPrevClasificacion($id_rendimiento, $id_clasificacion, $lote)
  {

    $info = $this->getLoteInfo($id_rendimiento, false);

    if (intval($lote) === 1)
    {
      $sql = $this->db->select('SUM(libres) AS existentes')
        ->from('rastria_cajas_libres AS rcl')
        ->join('rastria_rendimiento AS rd','rd.id_rendimiento = rcl.id_rendimiento', 'join')
        ->where('rcl.id_clasificacion', $id_clasificacion)
        ->where('DATE(rd.fecha) <', $info['info']->fecha)
        ->get();
    }
    else
    {
      // Obtiene los lotes anteriores.
      $sql2 = $this->db->query(
        "SELECT id_rendimiento, lote
          FROM rastria_rendimiento
          WHERE fecha = '{$info['info']->fecha}' AND lote < {$info['info']->lote}
          ORDER BY lote DESC
        ");

      // echo "<pre>";
      //   var_dump($sql2->result());
      // echo "</pre>";exit;

      $tiene = false;

      // Si existen lotes anteriores
      if ($sql2->num_rows() > 0)
      {
        foreach ($sql2->result() as $key => $lotee)
        {
          $sql = $this->db->query(
            "SELECT total AS existentes
              FROM rastria_rendimiento_clasif
              WHERE id_clasificacion = {$id_clasificacion} AND id_rendimiento = {$lotee->id_rendimiento}
          ");

          if ($sql->num_rows() > 0)
          {
            $tiene = true;
            break;
          }
        }
      }

      if ($tiene === false)
      {
        $sql->free_result();

        $sql = $this->db->select('SUM(libres) AS existentes')
          ->from('rastria_cajas_libres AS rcl')
          ->join('rastria_rendimiento AS rd','rd.id_rendimiento = rcl.id_rendimiento', 'join')
          ->where('rcl.id_clasificacion', $id_clasificacion)
          ->where('DATE(rd.fecha) <', $info['info']->fecha)
          ->get();
      }
    }

    return $sql->row();
  }

  /*
   |-------------------------------------------------------------------------
   |  REPORTES
   |-------------------------------------------------------------------------
  */
  /**
   * REPORTE DE RASTREABILIDAD DE PRODUCTOS
   * @return [type] [description]
   */
  public function rrp_data()
   {
      $response = array('data' => array(), 'calidad' => '', 'tipo' => 'Entrada');
      $sql = '';

      if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
        $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
        $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
      }
      if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
        $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
        $sql .= " AND Date(b.fecha_tara) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
      }
      //Filtros de calidad
      if ($this->input->get('fcalidad') != ''){
        $sql .= " AND bc.id_calidad = " . $_GET['fcalidad'];

        // Obtiene la informacion del Area filtrada.
        $this->load->model('calidades_model');
        $response['calidad'] = $this->calidades_model->getCalidadInfo($_GET['fcalidad']);
      }else
        $sql .= " AND bc.id_calidad = 0";

      $query = $this->db->query(
        "SELECT b.id_bascula,
                b.folio,
                b.no_lote,
                b.fecha_tara,
                b.chofer_es_productor,
                p.nombre_fiscal,
                c.nombre,
                Sum(bc.cajas) AS cajas,
                Sum(bc.kilos) AS kilos
        FROM bascula AS b
          INNER JOIN bascula_compra AS bc ON bc.id_bascula = b.id_bascula
          INNER JOIN proveedores AS p ON p.id_proveedor = b.id_proveedor
          LEFT JOIN choferes AS c ON c.id_chofer = b.id_chofer
        WHERE b.status = true AND b.tipo = 'en' AND b.accion IN('sa', 'p', 'b')
          {$sql}
        GROUP BY b.id_bascula, b.folio, b.no_lote, b.fecha_tara, b.chofer_es_productor, p.nombre_fiscal, c.nombre
        ORDER BY b.no_lote ASC, b.folio ASC
        "
      );
      if($query->num_rows() > 0)
        $response['data'] = $query->result();


      return $response;
   }

   /**
    * Visualiza/Descarga el PDF para el Reporte Rastreabilidad de productos
    *
    * @return void
    */
   public function rrp_pdf()
   {
      // Obtiene los datos del reporte.
      $data = $this->rrp_data();

      if(isset($data['calidad']['info']->nombre)){
        $calidad_nombre = $data['calidad']['info']->nombre;
      }else
        $calidad_nombre = '';

      $fecha = new DateTime($_GET['ffecha1']);
      $fecha2 = new DateTime($_GET['ffecha2']);

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
      $pdf->titulo2 = "REPORTE RASTREABILIDAD DEL PRODUCTO <{$calidad_nombre}>";
      $pdf->titulo3 = "DEL {$fecha->format('d/m/Y')} AL {$fecha2->format('d/m/Y')}\n";
      $lote = isset($data['data'][count($data['data'])-1]->no_lote)? $data['data'][count($data['data'])-1]->no_lote: '1';
      $pdf->titulo3 .= "Estado: 6 | Municipio: 9 | Semana {$fecha->format('W')} | NUMERADOR: 69{$fecha->format('Ww')}/1 Al ".$lote;

      $pdf->AliasNbPages();
      //$pdf->AddPage();
      $pdf->SetFont('helvetica','', 8);

      $aligns = array('C', 'C', 'L', 'L', 'R', 'R');
      $widths = array(12, 20, 68, 70, 15, 20);
      $header = array('LOTE', 'BOLETA', 'PRODUCTOR','FACTURADOR', 'CAJAS', 'KGS');

      $total_kilos = 0;
      $total_cajas = 0;
      $kilos_lote  = 0;
      $cajas_lote  = 0;
      $num_lote    = -1;

      foreach($data['data'] as $key => $boleta)
      {
        if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
        {
          $pdf->AddPage();

          $pdf->SetFont('helvetica','B',8);
          $pdf->SetTextColor(0,0,0);
          $pdf->SetFillColor(200,200,200);
          // $pdf->SetY($pdf->GetY()-2);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);

          if($key==0)
            $num_lote = $boleta->no_lote;
        }

        if($num_lote != $boleta->no_lote){
          $pdf->SetFont('helvetica','B',8);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row(array(
              '', '', '', '',
              String::formatoNumero($cajas_lote, 2, ''),
              String::formatoNumero($kilos_lote, 2, ''),
            ), true);
          $cajas_lote = 0;
          $kilos_lote = 0;
          $num_lote = $boleta->no_lote;
        }

        $pdf->SetFont('helvetica','', 8);
        $pdf->SetTextColor(0,0,0);

        // $pdf->SetY($pdf->GetY()-2);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
          $pdf->Row(array(
              $boleta->no_lote,
              $boleta->folio,
              ($boleta->chofer_es_productor=='t'? $boleta->nombre: ''),
              $boleta->nombre_fiscal,
              String::formatoNumero($boleta->cajas, 2, ''),
              String::formatoNumero($boleta->kilos, 2, ''),
            ), false);
        $cajas_lote  += $boleta->cajas;
        $kilos_lote  += $boleta->kilos;
        $total_cajas += $boleta->cajas;
        $total_kilos += $boleta->kilos;
      }
      //Total del ultimo lote
      $pdf->SetFont('helvetica','B',8);
      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row(array(
          '', '', '', '',
          String::formatoNumero($cajas_lote, 2, ''),
          String::formatoNumero($kilos_lote, 2, ''),
        ), true);

      //total general
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetFont('helvetica','B',8);
      $pdf->SetTextColor(0 ,0 ,0 );
      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
        $pdf->Row(array(
            '', '', '', 'TOTAL',
            String::formatoNumero($total_cajas, 2, ''),
            String::formatoNumero($total_kilos, 2, ''),
          ), false, false);


      $pdf->Output('reporte_rastreabilidad_'.$fecha->format('d/m/Y').'.pdf', 'I');
   }

   /**
   * REPORTE DE RASTREABILIDAD DE PRODUCTOS
   * @return [type] [description]
   */
  public function ref_data()
   {
      $response = array('data' => array(), 'calidad' => '', 'tipo' => 'Entrada');
      $sql = '';

      if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
        $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
        $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
      }
      if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
        $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
        $sql .= " AND Date(b.fecha_tara) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
      }
      //Filtros de area
      if ($this->input->get('farea') != ''){
        $sql .= " AND b.id_area = " . $_GET['farea'];
      }else
        $sql .= " AND b.id_area = 0";

      $query = $this->db->query(
        "SELECT b.id_bascula,
          bc.id_calidad,
          c.nombre,
          b.folio,
          b.no_lote,
          b.fecha_tara,
          p.nombre_fiscal,
          Sum(bc.cajas) AS cajas,
          Sum(bc.kilos) AS kilos
        FROM bascula AS b
          INNER JOIN bascula_compra as bc ON bc.id_bascula = b.id_bascula
          INNER JOIN proveedores AS p ON p.id_proveedor = b.id_proveedor
          INNER JOIN calidades AS c ON bc.id_calidad = c.id_calidad
        WHERE b.status = true AND b.tipo = 'en' AND b.accion IN('sa', 'p', 'b')
          {$sql}
        GROUP BY b.id_bascula, bc.id_calidad, c.nombre, b.folio, b.no_lote, b.fecha_tara, p.nombre_fiscal, bc.num_registro
        ORDER BY no_lote ASC, folio ASC, num_registro ASC
        "
      );
      if($query->num_rows() > 0){
        $response['data'] = $query->result();
        $query->free_result();
      }


      return $response;
   }

   /**
    * Visualiza/Descarga el PDF para el Reporte Rastreabilidad de productos
    *
    * @return void
    */
   public function ref_pdf()
   {
      // Obtiene los datos del reporte.
      $data = $this->ref_data();

      if(isset($data['calidad']['info']->nombre)){
        $calidad_nombre = $data['calidad']['info']->nombre;
      }else
        $calidad_nombre = '';

      $fecha = new DateTime($_GET['ffecha1']);
      $fecha2 = new DateTime($_GET['ffecha2']);

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
      $pdf->titulo2 = "REPORTE RASTREABILIDAD DEL PRODUCTO <{$calidad_nombre}>";
      $pdf->titulo3 = "DEL {$fecha->format('d/m/Y')} AL {$fecha2->format('d/m/Y')}\n";
      $lote = isset($data['data'][count($data['data'])-1]->no_lote)? $data['data'][count($data['data'])-1]->no_lote: '1';
      $pdf->titulo3 .= "Estado: 6 | Municipio: 9 | Semana {$fecha->format('W')} | NUMERADOR: 69{$fecha->format('Ww')}/1 Al ".$lote;

      $pdf->AliasNbPages();
      //$pdf->AddPage();
      $pdf->SetFont('helvetica','', 8);

      $aligns = array('C', 'C', 'L', 'L', 'R', 'R');
      $widths = array(12, 20, 68, 70, 15, 20);
      $header = array('LOTE', 'BOLETA', 'PRODUCTOR','CALIDAD', 'CAJAS', 'KGS');

      $total_kilos = 0;
      $total_cajas = array();
      $num_lote    = -1;

      foreach($data['data'] as $key => $boleta)
      {
        if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
        {
          $pdf->AddPage();

          $pdf->SetFont('helvetica','B',8);
          $pdf->SetTextColor(0,0,0);
          $pdf->SetFillColor(200,200,200);
          // $pdf->SetY($pdf->GetY()-2);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);
        }

        $pdf->SetFont('helvetica','', 8);
        $pdf->SetTextColor(0,0,0);

        // $pdf->SetY($pdf->GetY()-2);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
          $pdf->Row(array(
              ($num_lote != $boleta->no_lote? $boleta->no_lote: ''),
              $boleta->folio,
              $boleta->nombre_fiscal,
              $boleta->nombre,
              String::formatoNumero($boleta->cajas, 2, ''),
              String::formatoNumero($boleta->kilos, 2, ''),
            ), false);

        if($num_lote != $boleta->no_lote){
          $num_lote = $boleta->no_lote;
        }

        if(array_key_exists($boleta->id_calidad, $total_cajas)){
          $total_cajas[$boleta->id_calidad]['cajas'] += $boleta->cajas;
          $total_cajas[$boleta->id_calidad]['kilos'] += $boleta->kilos;
        }else{
          $total_cajas[$boleta->id_calidad] = array('cajas' => $boleta->cajas, 'kilos' => $boleta->kilos, 'nombre' => $boleta->nombre);
        }
      }

      //total general
      $pdf->SetFont('helvetica','B',8);
      $pdf->SetTextColor(0 ,0 ,0 );
      $pdf->SetAligns(array('L', 'R', 'R'));
      $pdf->SetWidths(array(40, 20, 20));

      $pdf->SetX(6);
      $pdf->Row(array(
          'CALIDAD', 'CAJAS', 'KILOS',
        ), false, false);
      foreach ($total_cajas as $key => $value) {
        if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

        $pdf->SetX(6);
        $pdf->Row(array(
            $value['nombre'],
            String::formatoNumero($value['cajas'], 2, ''),
            String::formatoNumero($value['kilos'], 2, ''),
          ), false, false);
      }


      $pdf->Output('reporte_rastreabilidad_'.$fecha->format('d/m/Y').'.pdf', 'I');
   }

   /**
    * Visualiza/Descarga el PDF para el Reporte Rendimiento por Lote
    *
    * @return void
    */
   public function rpl_pdf($id_rendimiento)
   {
      // Obtiene los datos del reporte.
      $data = $this->getLoteInfo($id_rendimiento);

      $fecha = new DateTime($data['info']->fecha);

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', array(110, 140));

      $pdf->show_head = false;

      $pdf->Image(APPPATH.'/images/logo.png', 6, 5, 20);
      $pdf->SetFont('Arial','',5);

      $pdf->titulo2 = "RENDIMIENTO POR LOTE";
      $pdf->titulo3 = "{$fecha->format('d/m/Y')} - ";

      $pdf->AliasNbPages();
      //$pdf->AddPage();
      $pdf->SetFont('helvetica','', 8);

      $aligns = array('C', 'C', 'L', 'L', 'R', 'R');
      $widths = array(12, 20, 68, 70, 15, 20);
      $header = array('CLASIF.', 'EXIST.', 'LINEA 1', 'LINEA 2', 'TOTAL', 'RD');

      // foreach($data['data'] as $key => $boleta)
      // {
      //   if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
      //   {
      //     $pdf->AddPage();

      //     $pdf->SetFont('helvetica','B',8);
      //     $pdf->SetTextColor(0,0,0);
      //     $pdf->SetFillColor(200,200,200);
      //     // $pdf->SetY($pdf->GetY()-2);
      //     $pdf->SetX(6);
      //     $pdf->SetAligns($aligns);
      //     $pdf->SetWidths($widths);
      //     $pdf->Row($header, true);
      //   }

      //   $pdf->SetFont('helvetica','', 8);
      //   $pdf->SetTextColor(0,0,0);

      //   // $pdf->SetY($pdf->GetY()-2);
      //   $pdf->SetX(6);
      //   $pdf->SetAligns($aligns);
      //   $pdf->SetWidths($widths);
      //     $pdf->Row(array(
      //         ($num_lote != $boleta->no_lote? $boleta->no_lote: ''),
      //         $boleta->folio,
      //         $boleta->nombre_fiscal,
      //         $boleta->nombre,
      //         String::formatoNumero($boleta->cajas, 2, ''),
      //         String::formatoNumero($boleta->kilos, 2, ''),
      //       ), false);

      //   if($num_lote != $boleta->no_lote){
      //     $num_lote = $boleta->no_lote;
      //   }

      //   if(array_key_exists($boleta->id_calidad, $total_cajas)){
      //     $total_cajas[$boleta->id_calidad]['cajas'] += $boleta->cajas;
      //     $total_cajas[$boleta->id_calidad]['kilos'] += $boleta->kilos;
      //   }else{
      //     $total_cajas[$boleta->id_calidad] = array('cajas' => $boleta->cajas, 'kilos' => $boleta->kilos, 'nombre' => $boleta->nombre);
      //   }
      // }

      $pdf->Output('rendimiento_lore'.$fecha->format('d/m/Y').'.pdf', 'I');
   }

}

/* End of file bascula_model.php */
/* Location: ./application/models/bascula_model.php */