<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class caja_chica_model extends CI_Model {

  public function get($fecha, $noCaja)
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
       WHERE fecha < '{$fecha}' AND no_caja = {$noCaja}
       ORDER BY fecha DESC
       LIMIT 1"
    );

    if ($ultimoSaldo->num_rows() > 0)
    {
      $info['saldo_inicial'] = $ultimoSaldo->result()[0]->saldo;
    }

    $ingresos = $this->db->query(
      "SELECT ci.*, cc.abreviatura as categoria, cn.nomenclatura
       FROM cajachica_ingresos ci
       INNER JOIN cajachica_categorias cc ON cc.id_categoria = ci.id_categoria
       INNER JOIN cajachica_nomenclaturas cn ON cn.id = ci.id_nomenclatura
       WHERE ci.fecha = '{$fecha}' AND ci.otro = 'f' AND ci.no_caja = {$noCaja}
       ORDER BY ci.id_ingresos ASC"
    );

    if ($ingresos->num_rows() > 0)
    {
      $info['ingresos'] = $ingresos->result();
    }

    // $otros = $this->db->query(
    //   "SELECT *
    //    FROM cajachica_ingresos
    //    WHERE fecha = '$fecha' AND otro = 't'
    //    ORDER BY id_ingresos ASC"
    // );

    // if ($otros->num_rows() > 0)
    // {
    //   $info['otros'] = $otros->result();
    // }

    // remisiones
    $remisiones = $this->db->query(
      "SELECT cr.id_remision, cr.monto, cr.observacion, f.folio, cr.folio_factura, cr.id_categoria, cc.abreviatura as empresa,
              COALESCE((select (serie || folio) as folio from facturacion where id_factura = fvr.id_factura), null) as folio_factura,
              cr.id_movimiento, cr.row, cr.fecha
       FROM cajachica_remisiones cr
       INNER JOIN facturacion f ON f.id_factura = cr.id_remision
       INNER JOIN cajachica_categorias cc ON cc.id_categoria = cr.id_categoria
       LEFT JOIN facturacion_ventas_remision_pivot fvr ON fvr.id_venta = f.id_factura
       WHERE cr.fecha = '{$fecha}' AND cr.no_caja = {$noCaja}"
    );

    if ($remisiones->num_rows() > 0)
    {
      $info['remisiones'] = $remisiones->result();
    }

    // boletas
    if($noCaja == '1' || $noCaja == '3')
    {
      $sql = ' AND b.id_area <> 7';
      if ($noCaja == '3') {
        $sql = " AND b.id_area = 7";
      }
      $boletas = $this->db->query(
        "SELECT b.id_bascula, b.folio as boleta, DATE(b.fecha_pago) as fecha, pr.nombre_fiscal as proveedor, b.importe, cb.folio as folio_caja_chica
         FROM bascula b
         INNER JOIN proveedores pr ON pr.id_proveedor = b.id_proveedor
         LEFT JOIN cajachica_boletas cb ON cb.id_bascula = b.id_bascula
         WHERE DATE(b.fecha_pago) = '{$fecha}' AND b.accion = 'p' AND b.status = 't'{$sql}
         ORDER BY (b.folio) ASC"
      );

      if ($boletas->num_rows() > 0)
      {
        $info['boletas'] = $boletas->result();
      }
    }

    // denominaciones
    $denominaciones = $this->db->query(
      "SELECT *
       FROM cajachica_efectivo
       WHERE fecha = '{$fecha}' AND no_caja = {$noCaja}"
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
      "SELECT cg.id_gasto, cg.concepto, cg.fecha, cg.monto, cc.id_categoria, cc.abreviatura as empresa,
          cg.folio, cg.id_nomenclatura, cn.nomenclatura, COALESCE(cca.id_cat_codigos, ca.id_area) AS id_area,
          COALESCE(cca.nombre, ca.nombre) AS nombre_codigo,
          COALESCE((CASE WHEN cca.codigo <> '' THEN cca.codigo ELSE cca.nombre END), ca.codigo_fin) AS codigo_fin,
          (CASE WHEN cca.id_cat_codigos IS NULL THEN 'id_area' ELSE 'id_cat_codigos' END) AS campo,
          cg.reposicion
       FROM cajachica_gastos cg
         INNER JOIN cajachica_categorias cc ON cc.id_categoria = cg.id_categoria
         INNER JOIN cajachica_nomenclaturas cn ON cn.id = cg.id_nomenclatura
         LEFT JOIN compras_areas ca ON ca.id_area = cg.id_area
         LEFT JOIN otros.cat_codigos AS cca ON cca.id_cat_codigos = cg.id_cat_codigos
       WHERE cg.fecha = '{$fecha}' AND cg.no_caja = {$noCaja}
       ORDER BY cg.id_gasto ASC"
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
  }

  public function guardar($data)
  {
    $ingresos = array();

    // ingresos
    if (isset($data['ingreso_concepto']) && is_array($data['ingreso_concepto'])) {
      foreach ($data['ingreso_concepto'] as $key => $ingreso)
      {
        $ingresos[] = array(
          'concepto'        => $ingreso,
          'monto'           => $data['ingreso_monto'][$key],
          'fecha'           => $data['fecha_caja_chica'],
          'otro'            => 'f',
          'id_categoria'    => $data['ingreso_empresa_id'][$key],
          'id_nomenclatura' => $data['ingreso_nomenclatura'][$key],
          'poliza'          => empty($data['ingreso_poliza'][$key]) ? null : $data['ingreso_poliza'][$key],
          'id_movimiento'   => is_numeric($data['ingreso_concepto_id'][$key]) ? $data['ingreso_concepto_id'][$key] : null,
          'no_caja'         => $data['fno_caja'],
        );
      }
    }

    // Otros
    // if (isset($data['otros_concepto']))
    // {
    //   foreach ($data['otros_concepto'] as $key => $otro)
    //   {
    //     $ingresos[] = array(
    //       'concepto' => $otro,
    //       'monto'    => $data['otros_monto'][$key],
    //       'fecha'    => $data['fecha_caja_chica'],
    //       'otro'    => 't'
    //     );
    //   }
    // }

    $this->db->delete('cajachica_ingresos', array('fecha' => $data['fecha_caja_chica'], 'no_caja' => $data['fno_caja']));
    if (count($ingresos) > 0)
    {
      $this->db->insert_batch('cajachica_ingresos', $ingresos);
    }

    // Remisiones
    //Elimina los movimientos de banco y cuentas por cobrar si ya se cerro el corte
    $this->load->model('banco_cuentas_model');
    $corte_caja = $this->get($data['fecha_caja_chica'], $data['fno_caja']);
    foreach ($corte_caja['remisiones'] as $key => $value)
    {
      if($value->id_movimiento != '')
        $this->banco_cuentas_model->deleteMovimiento($value->id_movimiento);
    }
    $this->db->delete('cajachica_remisiones', array('fecha' => $data['fecha_caja_chica'], 'no_caja' => $data['fno_caja']));
    if (isset($data['remision_concepto']))
    {
      $remisiones = array();

      foreach ($data['remision_concepto'] as $key => $concepto)
      {
        $remisiones[] = array(
          'observacion'   => $concepto,
          'id_remision'   => $data['remision_id'][$key],
          'fecha'         => $data['fecha_caja_chica'],
          'monto'         => $data['remision_importe'][$key],
          'row'           => $key,
          'id_categoria'  => $data['remision_empresa_id'][$key],
          'folio_factura' => empty($data['remision_folio'][$key]) ? null : $data['remision_folio'][$key],
          'no_caja'       => $data['fno_caja'],
        );
      }

      $this->db->insert_batch('cajachica_remisiones', $remisiones);
    }

    // Boletas
    $this->db->delete('cajachica_boletas', array('fecha' => $data['fecha_caja_chica'], 'no_caja' => $data['fno_caja']));
    if (isset($data['boletas_id']))
    {
      $boletas = array();

      foreach ($data['boletas_id'] as $key => $idBoleta)
      {
        $boletas[] = array(
          'fecha'      => $data['fecha_caja_chica'],
          'id_bascula' => $idBoleta,
          'row'        => $key,
          'folio'      => empty($data['boletas_folio'][$key]) ? null : $data['boletas_folio'][$key],
          'no_caja'    => $data['fno_caja'],
        );
      }

      $this->db->insert_batch('cajachica_boletas', $boletas);
    }

    // Denominaciones
    $this->db->delete('cajachica_efectivo', array('fecha' => $data['fecha_caja_chica'], 'no_caja' => $data['fno_caja']));
    $efectivo = array();
    foreach ($data['denom_abrev'] as $key => $denominacion)
    {
      $efectivo[$denominacion] = $data['denominacion_cantidad'][$key];
    }

    $efectivo['fecha']   = $data['fecha_caja_chica'];
    $efectivo['saldo']   = $data['saldo_corte'];
    $efectivo['no_caja'] = $data['fno_caja'];

    $this->db->insert('cajachica_efectivo', $efectivo);

    // Gastos del dia
    // $this->db->delete('cajachica_gastos', array('fecha' => $data['fecha_caja_chica'], 'no_caja' => $data['fno_caja']));
    if (isset($data['gasto_concepto']))
    {
      $gastos_ids = array('adds' => array(), 'delets' => array(), 'updates' => array());
      $gastos_udt = $gastos = array();
      foreach ($data['gasto_concepto'] as $key => $gasto)
      {
        if (isset($data['gasto_del'][$key]) && $data['gasto_del'][$key] == 'true' &&
          isset($data['gasto_id_gasto'][$key]) && floatval($data['gasto_id_gasto'][$key]) > 0) {
          $gastos_ids['delets'][] = $this->getDataGasto($data['gasto_id_gasto'][$key]);

          $this->db->delete('cajachica_gastos', "id_gasto = ".$data['gasto_id_gasto'][$key]);
        } elseif (isset($data['gasto_id_gasto'][$key]) && floatval($data['gasto_id_gasto'][$key]) > 0) {
          $gastos_udt = array(
            'id_categoria'    => $data['gasto_empresa_id'][$key],
            'id_nomenclatura' => $data['gasto_nomenclatura'][$key],
            'folio'           => $data['gasto_folio'][$key],
            'concepto'        => $gasto,
            'monto'           => $data['gasto_importe'][$key],
            'fecha'           => $data['fecha_caja_chica'],
            'no_caja'         => $data['fno_caja'],
            // 'id_area'         => (isset($data['codigoAreaId'][$key]{0})? $data['codigoAreaId'][$key]: NULL),
            $data['codigoCampo'][$key] => (isset($data['codigoAreaId'][$key]{0})? $data['codigoAreaId'][$key]: NULL),
            'reposicion'      => ($data['gasto_reposicion'][$key]=='t'? 't': 'f'),
          );

          $this->db->update('cajachica_gastos', $gastos_udt, "id_gasto = ".$data['gasto_id_gasto'][$key]);
        } else {
          $gastos = array(
            'id_categoria'    => $data['gasto_empresa_id'][$key],
            'id_nomenclatura' => $data['gasto_nomenclatura'][$key],
            'folio'           => $data['gasto_folio'][$key],
            'concepto'        => $gasto,
            'monto'           => $data['gasto_importe'][$key],
            'fecha'           => $data['fecha_caja_chica'],
            'no_caja'         => $data['fno_caja'],
            // 'id_area'         => (isset($data['codigoAreaId'][$key]{0})? $data['codigoAreaId'][$key]: NULL),
            $data['codigoCampo'][$key] => (isset($data['codigoAreaId'][$key]{0})? $data['codigoAreaId'][$key]: NULL),
            'reposicion'      => ($data['gasto_reposicion'][$key]=='t'? 't': 'f'),
          );
          $this->db->insert('cajachica_gastos', $gastos);
          $gastos_ids['adds'][] = $this->db->insert_id();
        }
      }

      if (count($gastos_ids['adds']) > 0 || count($gastos_ids['delets']) > 0) {
        $this->enviarEmail($gastos_ids);
        // $this->db->insert_batch('cajachica_gastos', $gastos);
      }
    }

    return true;
  }

  public function getRemisiones()
  {
    $this->load->model('cuentas_cobrar_model');

    $remisiones = $this->db->query(
      "SELECT f.id_factura, DATE(f.fecha) as fecha, f.serie, f.folio, f.total, c.nombre_fiscal as cliente,
            COALESCE((select (serie || folio) as folio from facturacion where id_factura = fvr.id_factura), null) as folio_factura,
            sfr.saldo
       FROM facturacion f
       INNER JOIN clientes c ON c.id_cliente = f.id_cliente
       INNER JOIN saldos_facturas_remisiones sfr ON f.id_factura = sfr.id_factura
       LEFT JOIN cajachica_remisiones cr ON cr.id_remision = f.id_factura
       LEFT JOIN facturacion_ventas_remision_pivot fvr ON fvr.id_venta = f.id_factura
       WHERE f.is_factura = 'f' AND f.status = 'p'
       ORDER BY (f.fecha, f.serie, f.folio) DESC"
    );
    // COALESCE(cr.id_remision, 0) = 0

    $response = $remisiones->result();
    // foreach ($response as $key => $value)
    // {
    //   $inf_factura = $this->cuentas_cobrar_model->saldoFactura($value->id_factura);
    //   echo "<pre>";
    //     var_dump($value->id_factura, $inf_factura);
    //   echo "</pre>";
    //   $value->saldo = $inf_factura->saldo;
    // }

    return $response;
  }

  public function getMovimientos()
  {
    $this->load->model('empresas_model');

    $defaultEmpresa = $this->empresas_model->getDefaultEmpresa();
    //  AND bc.id_empresa = {$defaultEmpresa->id_empresa}

    $movimientos = $this->db->query(
      "SELECT bm.id_movimiento, COALESCE(p.nombre_fiscal, bm.a_nombre_de) as proveedor, bm.numero_ref, ba.nombre as banco, bm.monto, DATE(bm.fecha) as fecha
       FROM banco_movimientos bm
       INNER JOIN banco_cuentas bc ON bc.id_cuenta = bm.id_cuenta
       LEFT JOIN proveedores p ON p.id_proveedor = bm.id_proveedor
       INNER JOIN banco_bancos as ba ON ba.id_banco = bm.id_banco
       LEFT JOIN cajachica_ingresos ci ON ci.id_movimiento = bm.id_movimiento
       WHERE bm.tipo = 'f' AND COALESCE(ci.id_ingresos, 0) = 0 AND DATE(bm.fecha) > (Now() - interval '4 months')
       ORDER BY bm.fecha ASC, ci.id_ingresos ASC
    ");

    return $movimientos->result();
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
        "SELECT cc.id_categoria, cc.nombre, cc.status, cc.abreviatura, e.nombre_fiscal as empresa
        FROM cajachica_categorias cc
        LEFT JOIN empresas e ON e.id_empresa = cc.id_empresa
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
    $insertData = array(
      'nombre' => $data['nombre'],
      'abreviatura' => $data['abreviatura'],
    );

    if (isset($data['pid_empresa']) && is_numeric($data['pid_empresa']))
    {
      $insertData['id_empresa'] = $data['pid_empresa'];
    }

    $this->db->insert('cajachica_categorias', $insertData);

    return true;
  }

  public function info($idCategoria)
  {
    $query = $this->db->query(
      "SELECT cc.*, e.nombre_fiscal as empresa
        FROM cajachica_categorias cc
        LEFT JOIN empresas e ON e.id_empresa = cc.id_empresa
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
    $updateData = array(
      'nombre'      => $data['nombre'],
      'abreviatura' => $data['abreviatura'],
      'id_empresa'  => is_numeric($data['pid_empresa']) ? $data['pid_empresa'] : null,
    );

    $this->db->update('cajachica_categorias', $updateData, array('id_categoria' => $categoriaId));

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
        WHERE status = 't' AND lower(abreviatura) LIKE '%".mb_strtolower($_GET['term'], 'UTF-8')."%'
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

  public function enviarEmail($gastos_ids)
  {
      $this->load->library('my_email');

      // Obtiene la informacion de la factura.
      $html_adds = $txt_adds = '';
      $caja = '';
      foreach ($gastos_ids['adds'] as $key => $value) {
        $gasto = $this->getDataGasto($value);
        $html_adds .= '<table width="652" border="0">
        <tbody><tr>
        <td align="left"><b>Fecha:</b></td><td align="left">'.$gasto->fecha.'</td>
        </tr>
        <tr>
        <td align="left" width="142"><b>Operacion:</b></td><td align="left" width="510">Se agrego gasto</td>
        </tr>
        <tr>
        <td align="left"><b>Concepto:</b></td><td align="left">'.$gasto->concepto.'</td>
        </tr>
        <tr>
        <td align="left"><b>Importe:</b></td><td align="left">'.String::formatoNumero($gasto->monto).'</td>
        </tr>
        <tr>
        <td align="left"><b>Codigo gasto:</b></td><td align="left">'.$gasto->codigo_fin.'/'.$gasto->nombre_codigo.'</td>
        </tr>
        </tbody></table>';
        $txt_adds .= "Fecha: ".$gasto->fecha.", Operacion: Se elimino el gasto {$gasto->id_gasto}, Concepto: {$gasto->concepto}, Importe: ".String::formatoNumero($gasto->monto)."\r\n";
        $caja = $gasto->no_caja;
      }
      foreach ($gastos_ids['delets'] as $key => $gasto) {
        $html_adds .= '<table width="652" border="0">
        <tbody><tr>
        <td align="left"><b>Fecha:</b></td><td align="left">'.date("Y-m-d").'</td>
        </tr>
        <tr>
        <td align="left" width="142"><b>Operacion:</b></td><td align="left" width="510">Se elimino el gasto '.$gasto->id_gasto.'</td>
        </tr>
        <tr>
        <td align="left"><b>Concepto:</b></td><td align="left">'.$gasto->concepto.'</td>
        </tr>
        <tr>
        <td align="left"><b>Importe:</b></td><td align="left">'.String::formatoNumero($gasto->monto).'</td>
        </tr>
        <tr>
        <td align="left"><b>Codigo gasto:</b></td><td align="left">'.$gasto->codigo_fin.'/'.$gasto->nombre_codigo.'</td>
        </tr>
        </tbody></table>';
        $txt_adds .= "Fecha: ".date("Y-m-d").", Operacion: Se elimino el gasto {$gasto->id_gasto}, Concepto: {$gasto->concepto}, Importe: ".String::formatoNumero($gasto->monto)."\r\n";
        $caja = $gasto->no_caja;
      }

      if ($caja == '3') {
        //////////////////
        // Datos Correo //
        //////////////////

        $asunto = "Operacion realizada en Caja {$caja}";
        $altBody = "Notificacion, se registro un movimiento en la Caja {$caja}";

        $body = '<p>Notificacion, se registro un movimiento en la Caja '.$caja.'</p>
          <table border="0" width="652">
          <tbody>
          <tr>
          <td align="left" style="font-family:Arial,Helvetica,sans-serif;font-weight:bold;font-size:22px;color:#004785">Datos de las operaciones
            </td>
          </tr>
          <tr>
          <td height="25">&nbsp;</td>
          </tr>
          <tr>
          <td width="652">
          '.$html_adds.'
          </td>
          </tr>
          </tbody></table>
          <br>
          <p>EMPAQUE SAN JORGE</p>';

        //////////////////////
        // Datos del Emisor //
        //////////////////////

        $correoEmisorEm = "empaquesanjorge@hotmail.com"; // Correo con el q se emitira el correo.
        $nombreEmisor   = 'EMPAQUE SAN JORGE';
        $correoEmisor   = "empaquesanjorgemx@gmail.com"; // Correo para el auth. empaquesanjorgemx@gmail.com (mandrill)
        $contrasena     = "2x02pxeexCUpiKncoWI50Q"; // Contraseña de $correEmisor S4nj0rg3V14n3y

        ////////////////////////
        // Datos del Receptor //
        ////////////////////////

        $correoDestino = array('gamameso@gmail.com');

        $nombreDestino = 'Gamaliel';
        $datosEmail = array(
            'correoEmisorEm' => $correoEmisorEm,
            'correoEmisor'   => $correoEmisor,
            'nombreEmisor'   => $nombreEmisor,
            'contrasena'     => $contrasena,
            'asunto'         => $asunto,
            'altBody'        => $altBody,
            'body'           => $body,
            'correoDestino'  => $correoDestino,
            'nombreDestino'  => $nombreDestino,
            'cc'             => '',
            'adjuntos'       => array()
        );

        // Envia el email.
        $result = $this->my_email->setData($datosEmail)->send();

        $response = array(
            'passes' => true,
            'msg'    => 10
        );

        if (isset($result['error']))
        {
            $response = array(
            'passes' => false,
            'msg'    => 9
            );
        }

        return $response;
      }
  }


  /**
   * NOMENCLATURAS
   */
  public function getNomenclaturas($perpage = '40')
  {
    $sql = '';
    // //paginacion
    // $params = array(
    //     'result_items_per_page' => $perpage,
    //     'result_page'       => (isset($_GET['pag'])? $_GET['pag']: 0)
    // );

    // if($params['result_page'] % $params['result_items_per_page'] == 0)
    //   $params['result_page'] = ($params['result_page']/$params['result_items_per_page']);

    //Filtros para buscar
    if ($this->input->get('fstatus') == '')
      $sql .= " AND cc.status = 't'";
    else
      $sql .= " AND cc.status = '".$this->input->get('fstatus')."'";

    $res = $this->db->query("SELECT cc.id, cc.nombre, cc.status, cc.nomenclatura
        FROM cajachica_nomenclaturas cc
        WHERE 1 = 1 {$sql}
        ORDER BY cc.nomenclatura::integer ASC
        ");

    $response = $res->result();

    return $response;
  }

  public function agregarNomenclaturas($data)
  {
    $nom_res = $this->db->query("SELECT nomenclatura
                               FROM cajachica_nomenclaturas
                               ORDER BY nomenclatura::integer DESC LIMIT 1")->row();
    $insertData = array(
      'nombre' => $data['nombre'],
      'nomenclatura' => $nom_res->nomenclatura+1,
    );

    $this->db->insert('cajachica_nomenclaturas', $insertData);

    return true;
  }

  public function infoNomenclaturas($idNomenclatura)
  {
    $query = $this->db->query(
      "SELECT cc.*
        FROM cajachica_nomenclaturas cc
        WHERE id = {$idNomenclatura}");

    $data = array();
    if ($query->num_rows() > 0)
    {
      $data['info'] = $query->result();
    }

    return $data;
  }

  public function modificarNomenclaturas($idNomenclatura, $data)
  {
    $updateData = array(
      'nombre'      => $data['nombre'],
    );

    $this->db->update('cajachica_nomenclaturas', $updateData, array('id' => $idNomenclatura));

    return true;
  }

  public function elimimnarNomenclaturas($idNomenclatura, $val)
  {
    $this->db->update('cajachica_nomenclaturas', array('status' => $val), array('id' => $idNomenclatura));

    return true;
  }


  public function cerrarCaja($idCaja, $noCajas)
  {
    $this->db->update('cajachica_efectivo', array('status' => 'f'), array('id_efectivo' => $idCaja));
    $caja = $this->db->query("SELECT fecha FROM cajachica_efectivo WHERE id_efectivo = {$idCaja}")->row();

    $this->load->model('cuentas_cobrar_model');
    $banco_cuenta = $this->db->query("SELECT id_cuenta FROM banco_cuentas WHERE UPPER(alias) LIKE '%PAGO REMISIONADO%'")->row();
    $corte_caja = $this->get($caja->fecha, $noCajas);
    foreach ($corte_caja['remisiones'] as $key => $value)
    {
      $_POST['fmetodo_pago'] = 'efectivo';
      $_GET['tipo'] = 'r';
      $data = array('fecha'  => $caja->fecha,
            'concepto'       => 'Pago en caja chica',
            'total'          => $value->monto, //$total,
            'id_cuenta'      => $banco_cuenta->id_cuenta,
            'ref_movimiento' => 'Caja '.$noCajas,
            'saldar'         => 'no' );
      $resp = $this->cuentas_cobrar_model->addAbono($data, $value->id_remision);
      $this->db->update('cajachica_remisiones', array('id_movimiento' => $resp['id_movimiento']),
        "fecha = '{$value->fecha}' AND id_remision = {$value->id_remision} AND row = {$value->row}");
    }

    return true;
  }

  public function printCajaNomenclatura(&$pdf, $nomenclaturas)
  {
    // nomenclatura
    $pdf->SetFont('Arial','', 6);
    $pdf->SetXY(111, 9);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(30));
    $pdf->Row(array('NOMENCLATURA INGRESOS'), false, false);

    $pdf->SetXY(150, 9);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(30));
    foreach ($nomenclaturas as $n)
    {
      $pdf->SetX(150);
      $pdf->Row(array($n->nomenclatura.' '.$n->nombre), false, false, null, 1, 1);
    }
  }
  public function printCaja($fecha, $noCajas)
  {
    $this->load->model('compras_areas_model');
    $this->load->model('catalogos_sft_model');

    $caja = $this->get($fecha, $noCajas);
    $nomenclaturas = $this->nomenclaturas();

    // echo "<pre>";
    //   var_dump($caja);
    // echo "</pre>";exit;
    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    $pdf->show_head = false;
    // $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    // $pdf->titulo1 S= $empresa['info']->nombre_fiscal;
    // $pdf->logo = $empresa['info']->logo;
    // $pdf->titulo2 = $empleado['info'][0]->nombre;
    $pdf->titulo2 = "Caja Chica del {$fecha}";
    $pdf->AliasNbPages();
    $pdf->AddPage();

    $pdf->limiteY = 235; //limite de alto

    // Reporte caja
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetX(6);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(104));
    $pdf->Row(array('REPORTE CAJA CHICA'), true, true, null, 3);

    $pdf->Image(APPPATH.(str_replace(APPPATH, '', '/images/logo.png')), 6, 15, 50);
    $pdf->Ln(20);

    $pdf->SetFont('Arial','B', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);

    // Fecha
    $pdf->SetXY(6, $pdf->GetY() - 20);
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(104));
    $pdf->Row(array('FECHA ' . $fecha), false, false);

    // Saldo inicial
    $pdf->SetXY(6, $pdf->GetY() + 5);
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(104));
    $pdf->Row(array('SALDO INICIAL '.String::formatoNumero($caja['saldo_inicial'], 2, '$', false)), false, false);

    // nomenclatura
    $this->printCajaNomenclatura($pdf, $nomenclaturas);
    // $pdf->SetFont('Arial','', 6);
    // $pdf->SetXY(111, 9);
    // $pdf->SetAligns(array('C'));
    // $pdf->SetWidths(array(30));
    // $pdf->Row(array('NOMENCLATURA INGRESOS'), false, false);

    // $pdf->SetXY(150, 9);
    // $pdf->SetAligns(array('L'));
    // $pdf->SetWidths(array(30));
    // foreach ($nomenclaturas as $n)
    // {
    //   $pdf->SetX(150);
    //   $pdf->Row(array($n->nomenclatura.' '.$n->nombre), false, false, null, 1, 1);
    // }

    $ttotalGastos = 0;
    foreach ($caja['gastos'] as $gasto)
    {
      $ttotalGastos += floatval($gasto->monto);
    }

    // Ingresos por reposicion
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, 32);
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(84, 20));
    $pdf->Row(array('INGRESOS POR REPOSICION', 'IMPORTE'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('C', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(15, 15, 15, 39, 20));
    $pdf->Row(array('EMPRESA', 'NOM', 'POLIZA', 'NOMBRE Y/O CONCEPTO', 'ABONO'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'R', 'L', 'L', 'R'));
    $pdf->SetWidths(array(15, 15, 15, 39, 20));

    $totalIngresos = 0;
    foreach ($caja['ingresos'] as $key => $ingreso)
    {
      $pdf->SetX(6);

      $pdf->Row(array(
        $ingreso->categoria,
        $ingreso->nomenclatura,
        $ingreso->poliza,
        $ingreso->concepto,
        String::formatoNumero($ingreso->monto, 2, '', false)), false, true);

      $totalIngresos += floatval($ingreso->monto);
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(84, 20));
    $pdf->Row(array('INGRESOS CLIENTES', 'IMPORTE'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('C', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(15, 15, 15, 39, 20));
    $pdf->Row(array('EMPRESA', 'REM', 'FOLIO', 'NOMBRE', 'ABONO'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetWidths(array(15, 15, 15, 39, 20));

    $totalRemisiones = 0;
    foreach ($caja['remisiones'] as $key => $remision)
    {
      $pdf->SetX(6);

      $pdf->SetAligns(array('L', 'R', 'R', 'L', 'R'));

      $pdf->Row(array(
        $remision->empresa,
        $remision->folio,
        $remision->folio_factura,
        $remision->observacion,
        String::formatoNumero($remision->monto, 2, '', false)), false, true);

      $totalRemisiones += floatval($remision->monto);
    }

    $ttotalIngresos = $totalRemisiones + $totalIngresos + $caja['saldo_inicial'];

    $pdf->SetX(6);
    $pdf->Row(array('', '', '', '', String::formatoNumero($totalRemisiones + $totalIngresos, 2, '', false)), false, true);

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(6);
    $pdf->Row(array('', '', '', 'TOTAL', String::formatoNumero($ttotalIngresos, 2, '$', false)), false, true);

    // if ($comprasY >= $pdf->GetY())
    // {
    //   $pdf->SetY($comprasY);
    // }


    // Boletas
    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('C', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(15, 15, 15, 39, 20));
    $pdf->Row(array('BOLETA', 'FECHA', 'FOLIO', 'FACTURADOR Y/O PRODUTOR', 'IMPORTE'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetAligns(array('C', 'C', 'C', 'C', 'R'));
    $pdf->SetWidths(array(15, 15, 15, 39, 20));

    // $caja['boletas'] = array_merge($caja['boletas'], $caja['boletas']);

    // $pdf->SetFont('Arial','', 7);
    // $boletasY = $pdf->GetY();

    $totalBoletas = 0;
    foreach ($caja['boletas'] as $key => $boleta)
    {
      if($pdf->GetY() >= $pdf->limiteY) {

        $pdf->AddPage();
        // nomenclatura
        $this->printCajaNomenclatura($pdf, $nomenclaturas);
        $pdf->SetAligns(array('C', 'C', 'C', 'C', 'R'));
        $pdf->SetWidths(array(15, 15, 15, 39, 20));
        $pdf->SetFont('Helvetica','B', 7);
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->Row(array('BOLETA', 'FECHA', 'FOLIO', 'FACTURADOR Y/O PRODUTOR', 'IMPORTE'), true, true);

        $boletasY = $pdf->GetY();
      }

      $pdf->SetFont('Helvetica','', 7);
      $pdf->SetX(6);

      $pdf->SetAligns(array('L', 'C', 'C', 'L', 'R'));
      $pdf->Row(array(
        $boleta->boleta,
        $boleta->fecha,
        $boleta->folio_caja_chica,
        $boleta->proveedor,
        String::formatoNumero($boleta->importe, 2, '', false)), false, true);

      $totalBoletas += floatval($boleta->importe);
    }

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(6);
    $pdf->Row(array('', '', '', 'TOTAL', String::formatoNumero($totalBoletas, 2, '$', false)), false, true);

    // Gastos del Dia
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(111, 32);
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(83, 17));
    $pdf->Row(array('GASTOS DEL DIA', 'IMPORTE'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(111);
    $pdf->SetAligns(array('C', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(25, 15, 7, 36, 17));
    $pdf->Row(array('COD', 'EMPRESA', 'NOM', 'CONCEPTO', 'CARGO'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetAligns(array('C', 'C', 'C', 'C', 'R'));
    $pdf->SetWidths(array(25, 15, 7, 36, 17));

    $codigoAreas = array();
    $totalGastos = 0;
    foreach ($caja['gastos'] as $key => $gasto)
    {
      if ($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        // nomenclatura
        $this->printCajaNomenclatura($pdf, $nomenclaturas);
        $pdf->SetFont('Helvetica','B', 7);
        $pdf->SetXY(111, $pdf->GetY());
        $pdf->SetAligns(array('C', 'C', 'C', 'C', 'C'));
        $pdf->SetWidths(array(25, 15, 7, 36, 17));
        $pdf->Row(array('COD', 'EMPRESA', 'NOM', 'CONCEPTO', 'CARGO'), true, true);
      }

      $totalGastos += floatval($gasto->monto);

      $pdf->SetAligns(array('L', 'L', 'R', 'L', 'R'));
      $pdf->SetX(111);
      $pdf->Row(array(
        $gasto->codigo_fin.' '.$this->{($gasto->campo=='id_area'? 'compras_areas_model': 'catalogos_sft_model')}->getDescripCodigo($gasto->id_area),
        $gasto->empresa,
        $gasto->nomenclatura,
        // $gasto->folio,
        $gasto->concepto,
        String::float(String::formatoNumero($gasto->monto, 2, '', false))), false, true);

      // if($gasto->id_area != '' && !array_key_exists($gasto->id_area, $codigoAreas))
      //   $codigoAreas[$gasto->id_area] = $this->compras_areas_model->getDescripCodigo($gasto->id_area);
    }

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(111);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetAligns(array('L', 'R', 'L', 'L', 'R'));
    $pdf->Row(array('', '', '', 'TOTAL', String::formatoNumero($totalGastos, 2, '$', false)), true, true);

    // Tabulaciones
    $pdf->SetFont('Arial','B', 6);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(210, 210, 210);
    $pdf->SetXY(111, $pdf->GetY() + 5);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(56));
    $pdf->Row(array('TABULACION DE EFECTIVO'), true, true);

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(255, 255, 255);
    // $pdf->SetXY(131, $boletasY - 5.4);
    $pdf->SetXY(111, $pdf->GetY());
    $pdf->SetAligns(array('C', 'C', 'C'));
    $pdf->SetWidths(array(15, 16, 25));
    $pdf->Row(array('NUMERO', 'DENOMIN.', 'TOTAL'), true, true);

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
      $pdf->SetX(111);

      $pdf->Row(array(
        $denominacion['cantidad'],
        $denominacion['denominacion'],
        String::formatoNumero($denominacion['total'], 2, '', false)), false, true);

      $totalEfectivo += floatval($denominacion['total']);
    }

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(111);
    $pdf->SetAligns(array('C', 'R'));
    $pdf->SetWidths(array(31, 25));
    $pdf->Row(array('TOTAL EFECTIVO', String::formatoNumero($totalEfectivo, 2, '$', false)), false, true);

    $pdf->SetX(111);
    $pdf->Row(array('DIFERENCIA', String::formatoNumero($totalEfectivo - ($caja['saldo_inicial'] + $totalRemisiones + $totalIngresos - $totalBoletas - $ttotalGastos) , 2, '$', false)), false, false);

    $pdf->SetFont('Arial', 'B', 6);
    $pdf->SetXY(168, $pdf->GetY() - 32);
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetWidths(array(25, 19));
    $pdf->Row(array('SALDO INICIAL', String::formatoNumero($caja['saldo_inicial'], 2, '$', false)), false, false);

    $pdf->SetX(168);
    $pdf->Row(array('TOTAL INGRESOS', String::formatoNumero($totalRemisiones + $totalIngresos, 2, '$', false)), false, false);
    $pdf->SetX(168);
    $pdf->Row(array('PAGO TOT LIMON ', String::formatoNumero($totalBoletas, 2, '$', false)), false, false);
    $pdf->SetX(168);
    $pdf->Row(array('PAGO TOT GASTOS', String::formatoNumero($ttotalGastos, 2, '$', false)), false, false);
    $pdf->SetX(168);
    $pdf->Row(array('EFECT. DEL CORTE', String::formatoNumero($caja['saldo_inicial'] + $totalRemisiones + $totalIngresos - $totalBoletas - $ttotalGastos, 2, '$', false)), false, false);

    if(count($codigoAreas) > 0){
      $pdf->SetFont('Arial', '', 6);
      $pdf->SetXY(6, $pdf->GetY()+7);
      $pdf->SetWidths(array(205));
      $pdf->SetAligns('L');
      $pdf->Row(array('COD/AREA: ' . implode(' - ', $codigoAreas)), false, false);
    }

    $pdf->Output('CAJA_CHICA.pdf', 'I');
  }

  public function nomenclaturas()
  {
    $res = $this->db->query("
        SELECT *
        FROM cajachica_nomenclaturas
        ORDER BY nomenclatura ASC");

    return $res->result();
  }

  public function getDataGasto($id_gasto)
  {
    $gastos = $this->db->query(
      "SELECT cg.id_gasto, cg.concepto, cg.fecha, cg.monto, cc.id_categoria, cc.abreviatura as empresa, cc.nombre as empresal,
          cg.folio, cg.id_nomenclatura, cn.nomenclatura, COALESCE(cca.id_cat_codigos, ca.id_area) AS id_area,
          COALESCE(cca.nombre, ca.nombre) AS nombre_codigo,
          COALESCE((CASE WHEN cca.codigo <> '' THEN cca.codigo ELSE cca.nombre END), ca.codigo_fin) AS codigo_fin,
          (CASE WHEN cca.id_cat_codigos IS NULL THEN 'id_area' ELSE 'id_cat_codigos' END) AS campo,
          cg.no_caja
       FROM cajachica_gastos cg
         INNER JOIN cajachica_categorias cc ON cc.id_categoria = cg.id_categoria
         INNER JOIN cajachica_nomenclaturas cn ON cn.id = cg.id_nomenclatura
         LEFT JOIN compras_areas ca ON ca.id_area = cg.id_area
         LEFT JOIN otros.cat_codigos AS cca ON cca.id_cat_codigos = cg.id_cat_codigos
       WHERE cg.id_gasto = '{$id_gasto}'
       ORDER BY cg.id_gasto ASC"
    )->row();

    return $gastos;
  }

  public function printVale($id_gasto)
  {
    $this->load->model('compras_areas_model');
    $this->load->model('catalogos_sft_model');

    $gastos = $this->getDataGasto($id_gasto);

    // echo "<pre>";
    //   var_dump($gastos);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', array(63, 130));
    $pdf->limiteY = 50;
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false);
    $pdf->show_head = false;

    $pdf->SetFont('helvetica','B', 8);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(63));
    $pdf->SetXY(0, $pdf->GetY()-5);
    $pdf->Row(array($gastos->empresal), false, false);

    $pdf->SetFont('helvetica','', 8);
    $pdf->SetXY(0, 0);
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(63));
    $pdf->SetXY(0, $pdf->GetY()+4);
    $pdf->Row(array('VALE PROVISIONAL DE CAJA'), false, false);

    $pdf->SetAligns(array('L'));
    // $pdf->SetWidths(array(63));
    $pdf->SetXY(0, $pdf->GetY()-6);
    $pdf->Row(array('Folio: '.$gastos->id_gasto), false, false);

    $pdf->SetWidths(array(20, 43));
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetX(0);
    $pdf->Row(array('Caja: '.$gastos->no_caja, String::formatoNumero($gastos->monto, 2, '$', false) ), false, false);

    $pdf->SetX(0);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(63));
    $pdf->Row(array('CANTIDAD:'), false, false);
    $pdf->SetX(0);
    $pdf->Row(array(String::num2letras($gastos->monto)), false, false);
    $pdf->SetX(0);
    $pdf->Line(0, $pdf->GetY()-1, 62, $pdf->GetY()-1);

    $pdf->SetX(0);
    // $pdf->SetAligns(array('L'));
    // $pdf->SetWidths(array(63));
    $pdf->Row(array('COD. AREA:'), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $cod_sof = $gastos->codigo_fin.' '.$this->{($gastos->campo=='id_area'? 'compras_areas_model': 'catalogos_sft_model')}->getDescripCodigo($gastos->id_area);
    $pdf->Row(array($cod_sof), false, false);
    $pdf->SetX(0);
    $pdf->Line(0, $pdf->GetY()-1, 62, $pdf->GetY()-1);

    $pdf->SetX(0);
    $pdf->Row(array($gastos->concepto), false, false);
    $pdf->Line(0, $pdf->GetY()-1, 62, $pdf->GetY()-1);

    $pdf->SetX(0);
    $pdf->SetAligns(array('C', 'C', 'C'));
    $pdf->SetWidths(array(21, 21, 21));
    $pdf->Row(array('AUTORIZA', 'RECIBIO', 'FECHA'), false, false);
    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Row(array('', '', $gastos->fecha), false, false);
    $pdf->Line(0, $pdf->GetY()+4, 62, $pdf->GetY()+4);
    $pdf->Line(21, $pdf->GetY()-12, 21, $pdf->GetY()+4);
    $pdf->Line(42, $pdf->GetY()-12, 42, $pdf->GetY()+4);

    // $pdf->AutoPrint(true);
    $pdf->Output();
  }


  /**
   * Reporte gastos caja chica
   *
   * @return
   */
  public function getRptGastosData()
  {
    $sql = '';
      $idsproveedores = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

    // $this->load->model('empresas_model');
    // $client_default = $this->empresas_model->getDefaultEmpresa();
    // $_GET['did_empresa'] = (isset($_GET['did_empresa']{0}) ? $_GET['did_empresa'] : $client_default->id_empresa);
    // $_GET['dempresa']    = (isset($_GET['dempresa']{0}) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND cc.id_categoria = '".$this->input->get('did_empresa')."'";
    }

    if ($this->input->get('fnomenclatura') != '')
      $sql .= " AND cn.id = ".$this->input->get('fnomenclatura');

    $response = array();
    $gastos = $this->db->query("SELECT cg.id_gasto, cc.id_categoria, cc.nombre AS categoria,
          cn.nombre AS nombre_nomen, cn.nomenclatura, cg.concepto, cg.monto, cg.fecha, cg.folio,
          cn.id AS id_nomenclatura, COALESCE(cca.id_cat_codigos, ca.id_area) AS id_area,
          COALESCE((CASE WHEN cca.codigo <> '' THEN cca.codigo ELSE cca.nombre END), ca.codigo_fin) AS codigo_fin,
          (CASE WHEN cca.id_cat_codigos IS NULL THEN 'id_area' ELSE 'id_cat_codigos' END) AS campo,
          cg.reposicion
        FROM cajachica_gastos cg
          INNER JOIN cajachica_categorias cc ON cc.id_categoria = cg.id_categoria
          INNER JOIN cajachica_nomenclaturas cn ON cn.id = cg.id_nomenclatura
          LEFT JOIN compras_areas ca ON ca.id_area = cg.id_area
          LEFT JOIN otros.cat_codigos AS cca ON cca.id_cat_codigos = cg.id_cat_codigos
        WHERE fecha BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
          {$sql}
        ORDER BY id_categoria ASC, fecha ASC");
    $response = $gastos->result();

    return $response;
  }
  /**
   * Reporte existencias por unidad pdf
   */
  public function getRptGastosPdf(){
    $res = $this->getRptGastosData();

    $this->load->model('compras_areas_model');
    $this->load->model('catalogos_sft_model');

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;

    $pdf->titulo2 = 'Reporte de Gastos';
    $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'L', 'L', 'C', 'L', 'L', 'R');
    $widths = array(18, 24, 22, 20, 75, 12, 30);
    $header = array('Fecha', 'Codigo', 'Nomenclatura', 'Folio', 'Concepto', 'Rep', 'Importe');

    $codigoAreas = array();
    $aux_categoria = '';
    $total_nomenclatura = array();
    $proveedor_cantidad = $proveedor_importe = $proveedor_impuestos = $proveedor_total = $reposicion_total = 0;
    foreach($res as $key => $producto){
      if($pdf->GetY() >= $pdf->limiteY || $key==0 || $aux_categoria != $producto->id_categoria){ //salta de pagina si exede el max
        if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

        if($aux_categoria != $producto->id_categoria && $key > 0)
        {
          $this->getRptGastosTotales($pdf, $proveedor_total, $reposicion_total, $total_nomenclatura, $aux_categoria, $producto);
        }elseif($key == 0)
          $aux_categoria = $producto->id_categoria;

        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(150));
        $pdf->Row(array($producto->categoria), false, false);

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
        $pdf->SetY($pdf->GetY()+2);
      }

      $pdf->SetTextColor(0,0,0);
      $pdf->SetFont('Arial','',8);
      $datos = array($producto->fecha,
        $producto->codigo_fin,
        $producto->nomenclatura,
        $producto->folio,
        $producto->concepto,
        $producto->reposicion=='t'? 'Si': 'No',
        String::formatoNumero($producto->monto, 2, '', false),
        );
      $pdf->SetXY(6, $pdf->GetY()-2);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false, false);

      if(array_key_exists($producto->id_nomenclatura, $total_nomenclatura))
        $total_nomenclatura[$producto->id_nomenclatura][0] += $producto->monto;
      else
        $total_nomenclatura[$producto->id_nomenclatura] = array($producto->monto, $producto->nombre_nomen, $producto->nomenclatura);

      if($producto->id_area != '' && !array_key_exists($producto->id_area, $codigoAreas))
          $codigoAreas[$producto->id_area] = $this->{($producto->campo=='id_area'? 'compras_areas_model': 'catalogos_sft_model')}->getDescripCodigo($producto->id_area);

      $proveedor_total += $producto->monto;
      $reposicion_total += $producto->reposicion=='t'? $producto->monto: 0;
    }

    if(isset($producto))
      $this->getRptGastosTotales($pdf, $proveedor_total, $reposicion_total, $total_nomenclatura, $aux_categoria, $producto);

    if(count($codigoAreas) > 0){
      $pdf->SetFont('Arial','',8);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(200));
      $pdf->Row(array('COD/AREA: ' . implode(' - ', $codigoAreas)), false, false);
    }

    $pdf->Output('compras_proveedor.pdf', 'I');
  }
  public function getRptGastosXls(){
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=reporte_ventas.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $res = $this->getRptGastosData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Reporte de Gastos';
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";

    $html = '<table>
      <tbody>
        <tr>
          <td colspan="6" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="6" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="6" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="6"></td>
        </tr>
        <tr style="font-weight:bold">
          <td style="border:1px solid #000;background-color: #cccccc;">Fecha</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Nomenclatura</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Folio</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Concepto</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Importe</td>
        </tr>';
    $aux_categoria = '';
    $total_nomenclatura = array();
    $proveedor_cantidad = $proveedor_importe = $proveedor_impuestos = $proveedor_total = 0;
    foreach($res as $key => $producto) {

      if($aux_categoria != $producto->id_categoria && $key > 0)
      {
        $html .= $this->getRptGastosTotalesXls($proveedor_total, $total_nomenclatura, $aux_categoria, $producto);
      }elseif($key == 0)
        $aux_categoria = $producto->id_categoria;

      $html .= '<tr style="">
          <td style="border:1px solid #000;">'.$producto->fecha.'</td>
          <td style="border:1px solid #000;">'.$producto->nomenclatura.'</td>
          <td style="border:1px solid #000;">'.$producto->folio.'</td>
          <td style="border:1px solid #000;">'.$producto->concepto.'</td>
          <td style="border:1px solid #000;">'.$producto->monto.'</td>
        </tr>';

      if(array_key_exists($producto->id_nomenclatura, $total_nomenclatura))
        $total_nomenclatura[$producto->id_nomenclatura][0] += $producto->monto;
      else
        $total_nomenclatura[$producto->id_nomenclatura] = array($producto->monto, $producto->nombre_nomen, $producto->nomenclatura);

      $proveedor_total += $producto->monto;
    }

    if(isset($producto))
      $html .= $this->getRptGastosTotalesXls($proveedor_total, $total_nomenclatura, $aux_categoria, $producto);

    $html .= '
      </tbody>
    </table>';

    echo $html;
  }

  public function getRptGastosTotales(&$pdf, &$proveedor_total, &$reposicion_total, &$total_nomenclatura, &$aux_categoria, &$producto)
  {
    if($pdf->GetY()+6 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B',8);
    $datos = array('Total General',
      String::formatoNumero(($proveedor_total), 2, '', false),
    );
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetWidths(array(171, 30));
    $pdf->Row($datos, false);

    if($pdf->GetY()+6 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(150));
    $pdf->Row(array('DESGLOSE DE GASTOS'), false, false);

    if($pdf->GetY()+6 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('C', 'L', 'R', 'R'));
    $pdf->SetWidths(array(25, 50, 50, 50));
    $pdf->Row(array('Nomenclatura', 'Concepto', 'Total por concepto', 'Total reposicion'), false, false);
    foreach ($total_nomenclatura as $keyn => $nomen)
    {
      if($pdf->GetY()+6 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetXY(6, $pdf->GetY()-2);
      $pdf->Row(array($nomen[2], $nomen[1], String::formatoNumero($nomen[0], 2, '', false) ), false, false);
    }
    if($pdf->GetY()+6 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('R', 'R', 'R'));
    $pdf->SetWidths(array(75, 50, 50));
    $pdf->Row(array('', String::formatoNumero(($proveedor_total), 2, '', false), String::formatoNumero(($reposicion_total), 2, '', false)), false);

    $aux_categoria      = $producto->id_categoria;
    $proveedor_total    = 0;
    $total_nomenclatura = array();

    $pdf->SetXY(6, $pdf->GetY()+8);
  }

  public function getRptGastosTotalesXls(&$proveedor_total, &$total_nomenclatura, &$aux_categoria, &$producto)
  {
    $html = '
    <tr style="font-weight:bold">
      <td colspan="4">Total General</td>
      <td style="border:1px solid #000;">'.($proveedor_total).'</td>
    </tr>
    <tr style="font-weight:bold">
      <td colspan="5">DESGLOSE DE GASTOS</td>
    </tr>
    <tr style="font-weight:bold">
      <td style="border:1px solid #000;background-color: #cccccc;">Nomenclatura</td>
      <td style="border:1px solid #000;background-color: #cccccc;">Concepto</td>
      <td style="border:1px solid #000;background-color: #cccccc;">Total por concepto</td>
    </tr>
    ';

    foreach ($total_nomenclatura as $keyn => $nomen)
    {
      $html .= '<tr style="font-weight:bold">
        <td style="border:1px solid #000;">'.$nomen[2].'</td>
        <td style="border:1px solid #000;">'.$nomen[1].'</td>
        <td style="border:1px solid #000;">'.$nomen[0].'</td>
      </tr>';
    }

    $html .= '<tr style="font-weight:bold">
        <td colspan="2" style="border:1px solid #000;background-color: #cccccc;">Total</td>
        <td style="border:1px solid #000;background-color: #cccccc;">'.$proveedor_total.'</td>
      </tr>';

    $aux_categoria      = $producto->id_categoria;
    $proveedor_total    = 0;
    $total_nomenclatura = array();
    return $html;
  }

  /**
   * Reporte gastos caja chica
   *
   * @return
   */
  public function getRptIngresosData()
  {
    $sql = $sql2 = '';
      $idsproveedores = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

    if($this->input->get('did_empresa') != ''){
      $sql .= " AND cc.id_categoria = '".$this->input->get('did_empresa')."'";
    }

    if ($this->input->get('fnomenclatura') != '')
      $sql2 .= " AND cn.id = ".$this->input->get('fnomenclatura');

    $response = array('movimientos' => array(), 'remisiones' => array());

    $movimientos = $this->db->query("SELECT ci.id_ingresos, cc.id_categoria, cc.nombre AS categoria,
          cn.nombre AS nombre_nomen, cn.nomenclatura, ci.concepto, ci.monto, ci.fecha, ci.poliza,
          cn.id AS id_nomenclatura
        FROM cajachica_ingresos ci
          INNER JOIN cajachica_categorias cc ON cc.id_categoria = ci.id_categoria
          INNER JOIN cajachica_nomenclaturas cn ON cn.id = ci.id_nomenclatura
        WHERE ci.fecha BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
          {$sql} {$sql2}
        ORDER BY id_categoria ASC, fecha ASC");
    $response['movimientos'] = $movimientos->result();

    $remisiones = $this->db->query("SELECT cr.id_remision, cc.id_categoria, cc.nombre AS categoria,
          f.folio, f.serie, cr.observacion, cr.monto, cr.fecha, cr.folio_factura
        FROM cajachica_remisiones cr
          INNER JOIN cajachica_categorias cc ON cc.id_categoria = cr.id_categoria
          INNER JOIN facturacion f ON f.id_factura = cr.id_remision
        WHERE cr.fecha BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
          {$sql}
        ORDER BY id_categoria ASC, fecha ASC");
    $response['remisiones'] = $remisiones->result();

    return $response;
  }
  /**
   * Reporte existencias por unidad pdf
   */
  public function getRptIngresosPdf(){
    $res = $this->getRptIngresosData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;

    $pdf->titulo2 = 'Reporte de Ingresos';
    $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'L', 'L', 'L', 'R');
    $widths = array(20, 22, 45, 80, 35);
    $header = array('Fecha', 'Nomenclatura', 'Poliza', 'Concepto', 'Importe');

    $aux_categoria = '';
    $total_nomenclatura = array();
    $aux_proveedor_total = $proveedor_total = 0;
    foreach($res['movimientos'] as $key => $producto){
      if($pdf->GetY() >= $pdf->limiteY || $key==0 || $aux_categoria != $producto->id_categoria){ //salta de pagina si exede el max
        if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

        if($aux_categoria != $producto->id_categoria && $key > 0)
        {
          $aux_proveedor_total = $proveedor_total;
          $this->getRptMovimientosTotales($pdf, $proveedor_total, $total_nomenclatura, $aux_categoria, $producto);

          $this->getRptRemisionesTotales($pdf, $res['remisiones'], $aux_proveedor_total, $producto->id_categoria);
        }elseif($key == 0)
          $aux_categoria = $producto->id_categoria;

        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Arial','B',12);
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(150));
        $pdf->Row(array($producto->categoria), false, false);

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
        $pdf->SetY($pdf->GetY()+2);
      }

      $pdf->SetTextColor(0,0,0);
      $pdf->SetFont('Arial','',8);
      $datos = array($producto->fecha,
        $producto->nomenclatura,
        $producto->poliza,
        $producto->concepto,
        String::formatoNumero($producto->monto, 2, '', false),
        );
      $pdf->SetXY(6, $pdf->GetY()-2);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false, false);

      if(array_key_exists($producto->id_nomenclatura, $total_nomenclatura))
        $total_nomenclatura[$producto->id_nomenclatura][0] += $producto->monto;
      else
        $total_nomenclatura[$producto->id_nomenclatura] = array($producto->monto, $producto->nombre_nomen, $producto->nomenclatura);

      $proveedor_total += $producto->monto;
    }

    if(isset($producto))
    {
      $aux_proveedor_total = $proveedor_total;
      $this->getRptMovimientosTotales($pdf, $proveedor_total, $total_nomenclatura, $aux_categoria, $producto);

      $this->getRptRemisionesTotales($pdf, $res['remisiones'], $aux_proveedor_total, $producto->id_categoria);
    }

    $pdf->Output('ingresos_caja.pdf', 'I');
  }

  public function getRptIngresosXls(){
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=ingresos_caja.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $res = $this->getRptIngresosData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Reporte de Ingresos';
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";

    $html = '<table>
      <tbody>
        <tr>
          <td colspan="6" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="6" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="6" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="6"></td>
        </tr>';
    $aux_categoria = '';
    $total_nomenclatura = array();
    $aux_proveedor_total = $proveedor_total = 0;
    foreach($res['movimientos'] as $key => $producto){

      if($key==0 || $aux_categoria != $producto->id_categoria) {
        if($aux_categoria != $producto->id_categoria && $key > 0)
        {
          $aux_proveedor_total = $proveedor_total;
          $html .= $this->getRptMovimientosTotalesXls($proveedor_total, $total_nomenclatura, $aux_categoria, $producto);

          $html .= $this->getRptRemisionesTotalesXls($res['remisiones'], $aux_proveedor_total, $producto->id_categoria);
        }elseif($key == 0)
          $aux_categoria = $producto->id_categoria;

        $html .= '<tr style="font-weight:bold">
          <td colspan="6"></td>
        </tr>
        <tr style="font-weight:bold">
          <td colspan="6" style="background-color: #cccccc;">'.$producto->categoria.'</td>
        </tr>
        <tr style="font-weight:bold">
          <td style="border:1px solid #000;background-color: #cccccc;">Fecha</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Nomenclatura</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Poliza</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Concepto</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Importe</td>
        </tr>';
      }

      $html .= '<tr style="">
          <td style="border:1px solid #000;">'.$producto->fecha.'</td>
          <td style="border:1px solid #000;">'.$producto->nomenclatura.'</td>
          <td style="border:1px solid #000;">'.$producto->poliza.'</td>
          <td style="border:1px solid #000;">'.$producto->concepto.'</td>
          <td style="border:1px solid #000;">'.$producto->monto.'</td>
        </tr>';

      if(array_key_exists($producto->id_nomenclatura, $total_nomenclatura))
        $total_nomenclatura[$producto->id_nomenclatura][0] += $producto->monto;
      else
        $total_nomenclatura[$producto->id_nomenclatura] = array($producto->monto, $producto->nombre_nomen, $producto->nomenclatura);

      $proveedor_total += $producto->monto;
    }

    if(isset($producto))
    {
      $aux_proveedor_total = $proveedor_total;
      $html .= $this->getRptMovimientosTotalesXls($proveedor_total, $total_nomenclatura, $aux_categoria, $producto);

      $html .= $this->getRptRemisionesTotalesXls($res['remisiones'], $aux_proveedor_total, $producto->id_categoria);
    }

    $html .= '
      </tbody>
    </table>';

    echo $html;
  }

  public function getRptMovimientosTotales(&$pdf, &$proveedor_total, &$total_nomenclatura, &$aux_categoria, &$producto)
  {
    if($pdf->GetY()+6 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B',8);
    $datos = array('Total Reposicion',
      String::formatoNumero(($proveedor_total), 2, '', false),
    );
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetWidths(array(167, 35));
    $pdf->Row($datos, false);

    if($pdf->GetY()+6 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(150));
    $pdf->Row(array('DESGLOSE DE INGRESOS'), false, false);

    if($pdf->GetY()+6 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('C', 'L', 'R'));
    $pdf->SetWidths(array(25, 50, 50));
    $pdf->Row(array('Nomenclatura', 'Concepto', 'Total por concepto'), false, false);
    foreach ($total_nomenclatura as $keyn => $nomen)
    {
      if($pdf->GetY()+6 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetXY(6, $pdf->GetY()-2);
      $pdf->Row(array($nomen[2], $nomen[1], String::formatoNumero($nomen[0], 2, '', false) ), false, false);
    }
    if($pdf->GetY()+6 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetWidths(array(75, 50));
    $pdf->Row(array('', String::formatoNumero(($proveedor_total), 2, '', false)), false);

    $aux_categoria      = $producto->id_categoria;
    $proveedor_total    = 0;
    $total_nomenclatura = array();

    $pdf->SetXY(6, $pdf->GetY()+8);
  }

  public function getRptRemisionesTotales(&$pdf, &$remisiones, $proveedor_total, $id_categoria)
  {
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'L', 'L', 'L', 'R');
    $widths = array(20, 25, 96, 25, 35);
    $header = array('Fecha', 'Remision', 'Nombre', 'Folio', 'Importe');

    $entro = false;
    $total_nomenclatura = array();
    $remisiones_total = 0;
    foreach($remisiones as $key => $producto){
      if($producto->id_categoria == $id_categoria)
      {
        if($pdf->GetY() >= $pdf->limiteY || !$entro){ //salta de pagina si exede el max
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $pdf->SetFont('Arial','B',8);
          $pdf->SetTextColor(255,255,255);
          $pdf->SetFillColor(160,160,160);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);
          $pdf->SetY($pdf->GetY()+2);
          $entro = true;
        }

        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Arial','',8);
        $datos = array($producto->fecha,
          $producto->serie.$producto->folio,
          $producto->observacion,
          $producto->folio_factura,
          String::formatoNumero($producto->monto, 2, '', false),
          );
        $pdf->SetXY(6, $pdf->GetY()-2);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($datos, false, false);

        $remisiones_total += $producto->monto;

        unset($remisiones[$key]);
      }
    }

    if($pdf->GetY()+6 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B',8);
    $datos = array('Total Remisiones',
      String::formatoNumero(($remisiones_total), 2, '', false),
    );
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetWidths(array(167, 35));
    $pdf->Row($datos, false);

    if($pdf->GetY()+6 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B',8);
    $datos = array('Total General',
      String::formatoNumero(($remisiones_total+$proveedor_total), 2, '', false),
    );
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetWidths(array(167, 35));
    $pdf->Row($datos, false);

    $pdf->SetXY(6, $pdf->GetY()+8);
  }

  public function getRptMovimientosTotalesXls(&$proveedor_total, &$total_nomenclatura, &$aux_categoria, &$producto)
  {
    $html = '
    <tr style="font-weight:bold">
      <td colspan="4">Total Reposicion</td>
      <td style="border:1px solid #000;">'.($proveedor_total).'</td>
    </tr>
    <tr style="font-weight:bold">
      <td colspan="5">DESGLOSE DE INGRESOS</td>
    </tr>
    <tr style="font-weight:bold">
      <td style="border:1px solid #000;background-color: #cccccc;">Nomenclatura</td>
      <td style="border:1px solid #000;background-color: #cccccc;">Concepto</td>
      <td style="border:1px solid #000;background-color: #cccccc;">Total por concepto</td>
    </tr>
    ';

    foreach ($total_nomenclatura as $keyn => $nomen)
    {
      $html .= '<tr style="font-weight:bold">
        <td style="border:1px solid #000;">'.$nomen[2].'</td>
        <td style="border:1px solid #000;">'.$nomen[1].'</td>
        <td style="border:1px solid #000;">'.$nomen[0].'</td>
      </tr>';
    }

    $html .= '<tr style="font-weight:bold">
        <td colspan="2" style="border:1px solid #000;background-color: #cccccc;">Total</td>
        <td style="border:1px solid #000;background-color: #cccccc;">'.$proveedor_total.'</td>
      </tr>';

    $aux_categoria      = $producto->id_categoria;
    $proveedor_total    = 0;
    $total_nomenclatura = array();
    return $html;
  }

  public function getRptRemisionesTotalesXls(&$remisiones, $proveedor_total, $id_categoria)
  {
    $html = '
    <tr style="font-weight:bold">
      <td style="border:1px solid #000;background-color: #cccccc;">Fecha</td>
      <td style="border:1px solid #000;background-color: #cccccc;">Remision</td>
      <td style="border:1px solid #000;background-color: #cccccc;">Nombre</td>
      <td style="border:1px solid #000;background-color: #cccccc;">Folio</td>
      <td style="border:1px solid #000;background-color: #cccccc;">Importe</td>
    </tr>
    ';

    $entro = false;
    $total_nomenclatura = array();
    $remisiones_total = 0;
    foreach($remisiones as $key => $producto)
    {
      $html .= '<tr style="font-weight:bold">
        <td style="border:1px solid #000;">'.$producto->fecha.'</td>
        <td style="border:1px solid #000;">'.$producto->serie.$producto->folio.'</td>
        <td style="border:1px solid #000;">'.$producto->observacion.'</td>
        <td style="border:1px solid #000;">'.$producto->folio_factura.'</td>
        <td style="border:1px solid #000;">'.$producto->monto.'</td>
      </tr>';

      $remisiones_total += $producto->monto;
      unset($remisiones[$key]);
    }

    $html .= '<tr style="font-weight:bold">
        <td colspan="2" style="border:1px solid #000;background-color: #cccccc;">Total Remisiones</td>
        <td style="border:1px solid #000;background-color: #cccccc;">'.$remisiones_total.'</td>
      </tr>
      <tr style="font-weight:bold">
        <td colspan="2" style="border:1px solid #000;background-color: #cccccc;">Total General</td>
        <td style="border:1px solid #000;background-color: #cccccc;">'.($remisiones_total+$proveedor_total).'</td>
      </tr>';

    // $aux_categoria      = $producto->id_categoria;
    // $proveedor_total    = 0;
    // $total_nomenclatura = array();
    return $html;
  }


}

/* End of file caja_chica_model.php */
/* Location: ./application/models/caja_chica_model.php */