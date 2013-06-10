<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class bascula extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'bascula/ajax_get_areas/',
    'bascula/ajax_get_empresas/',
    'bascula/ajax_get_proveedores/',
    'bascula/ajax_get_choferes/',
    'bascula/ajax_get_camiones/',
    'bascula/ajax_get_calidades/',
    'bascula/ajax_get_precio_calidad/',

    'bascula/show_view_agregar_proveedor/',
    'bascula/show_view_agregar_chofer/',
    'bascula/show_view_agregar_camion/',
    );

  public function _remap($method){

    $this->load->model("usuarios_model");
    if($this->usuarios_model->checkSession()){
      $this->usuarios_model->excepcion_privilegio = $this->excepcion_privilegio;
      $this->info_empleado                         = $this->usuarios_model->get_usuario_info($this->session->userdata('id_usuario'), true);

      if($this->usuarios_model->tienePrivilegioDe('', get_class($this).'/'.$method.'/')){
        $this->{$method}();
      }else
        redirect(base_url('panel/home?msg=1'));
    }else
      redirect(base_url('panel/home'));
  }

  public function index()
  {
    $this->carabiner->js(array(
        array('general/msgbox.js')
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Administración de Pesadas'
    );

    $this->load->model('bascula_model');
    $params['basculas'] = $this->bascula_model->getBasculas(true);

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/bascula/admin', $params);
    $this->load->view('panel/footer');
  }

  /**
   * Visualiza el formulario para agregar o editar una entrada|salida.
   * @return void
   */
  public function agregar()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));
    $this->carabiner->js(array(
      array('libs/jquery.uniform.min.js'),
      array('libs/jquery.numeric.js'),
      array('general/supermodal.js'),
      array('general/buttons.toggle.js'),
      array('panel/bascula/agregar.js'),
    ));

    $this->load->model('bascula_model');
    $this->load->model('areas_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar Bascula'
    );

    $params['next_folio'] = $this->bascula_model->getSiguienteFolio();
    $params['areas']      = $this->areas_model->getAreas();

    $this->configAddModBascula();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $this->load->model('bascula_model');
      $res_mdl = $this->bascula_model->addBascula();

      if( ! $res_mdl['error'])
        redirect(base_url('panel/bascula/agregar/?'.String::getVarsLink(array('msg', 'fstatus')).'&msg='.$res_mdl['msg']));
    }

    $params['accion'] = 'n'; // indica que es nueva entrada
    $params['idb']    = '';
    $params['param_folio'] = '';
    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));

    if (isset($_GET['folio']))
    {
      $info = $this->bascula_model->getBasculaInfo(0, $_GET['folio']);

      if (count($info['info']) > 0)
      {
        $this->load->model('empresas_model');
        $empresa = $this->empresas_model->getInfoEmpresa($info['info'][0]->id_empresa, true);

        $this->load->model('proveedores_model');
        $proveedor = $this->proveedores_model->getProveedorInfo($info['info'][0]->id_proveedor, true);

        $this->load->model('choferes_model');
        $chofer = $this->choferes_model->getChoferInfo($info['info'][0]->id_chofer, true);

        $this->load->model('camiones_model');
        $camion = $this->camiones_model->getCamionInfo($info['info'][0]->id_camion, true);

        $params['param_folio'] = '?folio='.$_GET['folio'];
        $params['idb']         = $info['info'][0]->id_bascula;
        $params['accion']      = $info['info'][0]->accion;

        $_POST['ptipo']         = $info['info'][0]->tipo;
        $_POST['parea']         = $info['info'][0]->id_area;
        $_POST['pempresa']      = $empresa['info']->nombre_fiscal; // falta
        $_POST['pid_empresa']   = $info['info'][0]->id_empresa;
        $_POST['pproveedor']    = $proveedor['info']->nombre_fiscal;
        $_POST['pid_proveedor'] = $info['info'][0]->id_proveedor;
        $_POST['pchofer']       = $chofer['info']->nombre;
        $_POST['pid_chofer']    = $info['info'][0]->id_chofer;
        $_POST['pcamion']       = $camion['info']->placa;
        $_POST['pid_camion']    = $info['info'][0]->id_camion;

        $params['next_folio'] = $info['info'][0]->folio;
        $params['fecha']      =  str_replace(' ', 'T', substr($info['info'][0]->fecha_bruto, 0, 19));

        $_POST['pkilos_brutos'] = $info['info'][0]->kilos_bruto;
        $_POST['pkilos_tara']   = $info['info'][0]->kilos_tara;
        $_POST['pkilos_neto']   = $info['info'][0]->kilos_neto;

        if ( ! isset($_POST['pcajas']) )
        {
          foreach ($info['cajas'] as $key => $c)
          {
            $_POST['pcajas'][]       = $c->cajas;
            $_POST['pcalidad'][]     = $c->id_calidad;
            $_POST['pcalidadtext'][] = $c->calidad;
            $_POST['pkilos'][]       = $c->kilos;
            $_POST['ppromedio'][]    = $c->promedio;
            $_POST['pprecio'][]      = $c->precio;
            $_POST['pimporte'][]     = $c->importe;
          }
        }

        $_POST['ptotal_cajas']   = $info['info'][0]->total_cajas;
        $_POST['ppesada']        = $info['info'][0]->kilos_neto2;
        $_POST['ptotal']         = $info['info'][0]->importe;
        $_POST['pobcervaciones'] = $info['info'][0]->obcervaciones;

      }
      else {
        $_GET['msg'] = '11';
      }

      // echo "<pre>";
      //   var_dump($info);
      // echo "</pre>";exit;
    }

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/bascula/agregar', $params);
    $this->load->view('panel/footer');
  }

  public function modificar()
  {
    if (isset($_GET['folio'][0]))
    {
      redirect(base_url('panel/bascula/agregar/?folio='.$_GET['folio']));
    }
  }

  /*
   |------------------------------------------------------------------------
   | Metodos para mostrar los formulario en el supermodal.
   |------------------------------------------------------------------------
   */

  /**
   * Muestra formulario agregar empresa.
   * @return void
   */
  public function show_view_agregar_empresa()
  {
  }

  /**
   * Muestra formulario agregar area.
   * @return void
   */
  public function show_view_agregar_proveedor()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));
    $this->carabiner->js(array(
      array('libs/jquery.uniform.min.js'),
      array('panel/bascula/fix_proveedores.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar Proveedor'
    );

    $this->configAddModProveedor();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $this->load->model('proveedores_model');
      $res_mdl = $this->proveedores_model->addProveedor();

      if(!$res_mdl['error'])
        redirect(base_url('panel/bascula/show_view_agregar_proveedor/?'.String::getVarsLink(array('msg')).'&msg=4'));
    }

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $params['template'] = $this->load->view('panel/proveedores/agregar', $params, true);

    $this->load->view('panel/bascula/supermodal', $params);
  }

  /**
   * Muestra formulario agregar chofer.
   * @return void
   */
  public function show_view_agregar_chofer()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));
    $this->carabiner->js(array(
      array('libs/jquery.uniform.min.js'),
      array('panel/bascula/fix_choferes.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar chofer'
    );

    $this->configAddModChofer();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $this->load->model('choferes_model');
      $res_mdl = $this->choferes_model->addChofer();

      if(!$res_mdl['error'])
        redirect(base_url('panel/bascula/show_view_agregar_chofer/?'.String::getVarsLink(array('msg')).'&msg=5'));
    }

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $params['template'] = $this->load->view('panel/choferes/agregar', $params, true);

    $this->load->view('panel/bascula/supermodal', $params);
  }

  /**
   * Muestra formulario agregar camion.
   * @return void
   */
  public function show_view_agregar_camion()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));
    $this->carabiner->js(array(
      array('libs/jquery.uniform.min.js'),
      array('panel/bascula/fix_camiones.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar camion'
    );

    $this->configAddModCamion();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $this->load->model('camiones_model');
      $res_mdl = $this->camiones_model->addCamion();

      if(!$res_mdl['error'])
        redirect(base_url('panel/bascula/show_view_agregar_camion/?'.String::getVarsLink(array('msg')).'&msg=6'));
    }

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $params['template'] = $this->load->view('panel/camiones/agregar', $params, true);

    $this->load->view('panel/bascula/supermodal', $params);
  }

  /*
   |------------------------------------------------------------------------
   | Metodos con validaciones de formulario.
   |------------------------------------------------------------------------
   */

  public function configAddModBascula()
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'ptipo',
            'label' => 'Tipo',
            'rules' => 'required'),
      array('field' => 'pfolio',
            'label' => 'Folio',
            'rules' => 'required|is_natural_no_zero'),
      array('field' => 'pfecha',
            'label' => 'Fecha',
            'rules' => 'required'),

      array('field' => 'parea',
            'label' => 'Area',
            'rules' => 'required'),
      array('field' => 'pid_empresa',
            'label' => 'Empresa',
            'rules' => 'required'),
      array('field' => 'pempresa',
            'label' => '',
            'rules' => ''),
      array('field' => 'pid_proveedor',
            'label' => 'Proveedor',
            'rules' => 'required'),
      array('field' => 'pproveedor',
            'label' => '',
            'rules' => ''),
      array('field' => 'pid_chofer',
            'label' => 'Chofer',
            'rules' => 'required'),
      array('field' => 'pchofer',
            'label' => '',
            'rules' => ''),
      array('field' => 'pid_camion',
            'label' => 'Camión',
            'rules' => 'required'),
      array('field' => 'pcamion',
            'label' => '',
            'rules' => ''),

      array('field' => 'pkilos_brutos',
            'label' => '',
            'rules' => ''),
      array('field' => 'pkilos_tara',
            'label' => '',
            'rules' => ''),
      array('field' => 'pkilos_neto',
            'label' => '',
            'rules' => ''),

      array('field' => 'ptotal_cajas',
            'label' => '',
            'rules' => ''),
      array('field' => 'ppesada',
            'label' => '',
            'rules' => ''),
      array('field' => 'ptotal',
            'label' => '',
            'rules' => ''),
      array('field' => 'pobcervaciones',
            'label' => 'Observaciones',
            'rules' => 'max_length[254]'),

      array('field' => 'pcajas[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'pcalidad[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'pcalidadtext[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'pkilos[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'ppromedio[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'pprecio[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'pimporte[]',
            'label' => '',
            'rules' => ''),

    );

    if (isset($_POST['paccion']))
    {
      if ($_POST['paccion'] == 'n')
      {
        $rules[] = array('field' => 'pkilos_brutos',
                         'label' => 'Kilos Brutos',
                         'rules' => 'required');
      }
      else if ($_POST['paccion'] == 'en' || $_POST['paccion'] == 'sa')
      {
        $rules[] = array('field' => 'pkilos_brutos',
                         'label' => 'Kilos Brutos',
                         'rules' => 'required');

        $rules[] = array('field' => 'pkilos_tara',
                         'label' => 'Kilos Tara',
                         'rules' => 'required');

        $rules[] = array('field' => 'pkilos_neto',
                         'label' => 'Kilos Neto',
                         'rules' => 'required');
      }
    }


    $this->form_validation->set_rules($rules);
  }

  /*
  | Asigna las reglas para validar un articulo al agregarlo
  */
  public function configAddModProveedor($accion='agregar')
  {
    $this->load->library('form_validation');
    $rules = array(
      array('field' => 'fnombre_fiscal',
            'label' => 'Nombre fiscal',
            'rules' => 'required|max_length[140]'),
      array('field' => 'fcalle',
            'label' => 'Calle',
            'rules' => 'max_length[60]'),
      array('field' => 'fno_exterior',
            'label' => 'No. exterior',
            'rules' => 'max_length[7]'),
      array('field' => 'fno_interior',
            'label' => 'No. interior',
            'rules' => 'max_length[7]'),
      array('field' => 'fcolonia',
            'label' => 'Colonia',
            'rules' => 'max_length[60]'),
      array('field' => 'flocalidad',
            'label' => 'Localidad',
            'rules' => 'max_length[45]'),
      array('field' => 'fmunicipio',
            'label' => 'Municipio',
            'rules' => 'max_length[45]'),
      array('field' => 'festado',
            'label' => 'Estado',
            'rules' => 'max_length[45]'),

      array('field' => 'frfc',
            'label' => 'RFC',
            'rules' => 'max_length[13]'),
      array('field' => 'fcurp',
            'label' => 'CURP',
            'rules' => 'max_length[35]'),
      array('field' => 'fcp',
            'label' => 'CP',
            'rules' => 'max_length[10]'),
      array('field' => 'ftelefono',
            'label' => 'Telefono',
            'rules' => 'max_length[15]'),
      array('field' => 'fcelular',
            'label' => 'Celular',
            'rules' => 'max_length[20]'),

      array('field' => 'femail',
            'label' => 'Email',
            'rules' => 'max_length[70]|valid_email'),
      array('field' => 'ftipo_proveedor',
            'label' => 'Tipo de proveedor',
            'rules' => 'required|max_length[2]'),
      array('field' => 'fcuenta_cpi',
            'label' => 'Cuenta ContpaqI',
            'rules' => 'max_length[12]'),
    );

    $this->form_validation->set_rules($rules);
  }

  /*
  | Asigna las reglas para validar un articulo al agregarlo
  */
  public function configAddModChofer($accion='agregar')
  {
    $this->load->library('form_validation');
    $rules = array(
      array('field' => 'fnombre',
            'label' => 'Nombre',
            'rules' => 'required|max_length[120]'),
    );

    $this->form_validation->set_rules($rules);
  }

  public function configAddModCamion($accion='agregar')
  {
    $this->load->library('form_validation');
    $rules = array(
      array('field' => 'fplacas',
            'label' => 'Placas',
            'rules' => 'required|max_length[15]'),
      array('field' => 'fmodelo',
            'label' => 'Modelo',
            'rules' => 'max_length[15]'),
      array('field' => 'fmarca',
            'label' => 'Marca',
            'rules' => 'max_length[15]'),
    );

    $this->form_validation->set_rules($rules);
  }

  /*
   |------------------------------------------------------------------------
   | Metodos para peticiones Ajax.
   |------------------------------------------------------------------------
   */

   /**
    * Obtiene las areas por peticion Ajax.
    * @return void
    */
  public function ajax_get_areas()
  {
    $this->load->model('areas_model');
    echo json_encode($this->areas_model->getEmpresasAjax());
  }

   /**
    * Obtiene las empresas por peticion Ajax.
    * @return void
    */
  public function ajax_get_empresas()
  {
    $this->load->model('empresas_model');
    echo json_encode($this->empresas_model->getEmpresasAjax());
  }

  /**
    * Obtiene los proveedores por peticion Ajax.
    * @return void
    */
  public function ajax_get_proveedores()
  {
    $this->load->model('proveedores_model');
    echo json_encode($this->proveedores_model->getProveedoresAjax());
  }

  /**
    * Obtiene los choferes por peticion Ajax.
    * @return void
    */
  public function ajax_get_choferes()
  {
    $this->load->model('choferes_model');
    echo json_encode($this->choferes_model->getChoferesAjax());
  }

  /**
    * Obtiene los choferes por peticion Ajax.
    * @return void
    */
  public function ajax_get_camiones()
  {
    $this->load->model('camiones_model');
    echo json_encode($this->camiones_model->getCamionesAjax());
  }

  /**
    * Obtiene las calidades de un area por peticion Ajax.
    * @return void
    */
  public function ajax_get_calidades()
  {
    $this->load->model('calidades_model');
    echo json_encode($this->calidades_model->getCalidades($_GET['id'], false));
  }

 /**
  * Obtiene las calidades de un area por peticion Ajax.
  * @return void
  */
  public function ajax_get_precio_calidad()
  {
    $this->load->model('calidades_model');
    echo json_encode($this->calidades_model->getCalidadInfo($_GET['id'], true));
  }


  /*
   |------------------------------------------------------------------------
   | Mensajes.
   |------------------------------------------------------------------------
   */
  private function showMsgs($tipo, $msg='', $title='Usuarios')
  {
    switch($tipo){
      case 1:
        $txt = 'El campo ID es requerido.';
        $icono = 'error';
        break;
      case 2: //Cuendo se valida con form_validation
        $txt = $msg;
        $icono = 'error';
        break;
      case 3:
        $txt = 'La empresa se agregó correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'El proveedor se agregó correctamente.';
        $icono = 'success';
        break;
      case 5:
        $txt = 'El chofer se agregó correctamente.';
        $icono = 'success';
        break;
      case 6:
        $txt = 'El camión se activó correctamente.';
        $icono = 'success';
        break;

      case 7:
        $txt = 'La entrada se agrego correctamente.';
        $icono = 'success';
        break;
      case 8:
        $txt = 'La salida se agrego correctamente.';
        $icono = 'success';
        break;

      case 9:
        $txt = 'La operación se realizo correctamente.';
        $icono = 'success';
        break;
      case 10:
        $txt = 'La clasificacion se activó correctamente.';
        $icono = 'success';
        break;

      case 11:
        $txt = 'No existe ninguna entrada con el folio especificado.';
        $icono = 'success';
        break;
      case 12:
        $txt = 'La clasificacion se modificó correctamente.';
        $icono = 'success';
        break;

      case 13:
        $txt = 'La calidad se agregó correctamente.';
        $icono = 'success';
        break;
      case 14:
        $txt = 'La clasificacion se agregó correctamente.';
        $icono = 'success';
        break;
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }

}

/* End of file bascula.php */
/* Location: ./application/controllers/panel/bascula.php */