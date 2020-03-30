<?php

/**
 * Asdin - Web | App
 *
 * Descripcion: Modelo encargado de registrar los establecimientos
 *
 * @category    
 * @package     Models 
 * @author      Eduardo Yánez (asdin_shop@yahoo.com)
 * @copyright   Copyright (c) 2019 Asdin
 * @revision    1.0
 */
class Establecimientos extends ActiveRecord {

    //Se desabilita el logger para no llenar el archivo de "basura"
    public $logger = FALSE;

    /**
     * Constante para definir un menú como activo
     */
    const ACTIVO = 1;

    /**
     * Constante para definir un menú como inactivo
     */
    const INACTIVO = 0;

    /**
     * Constante para definir el id de la oficina principal
     */
    const OFICINA_PRINCIPAL = 1;

    /**
     * Método para definir las relaciones y validaciones
     */
    protected function initialize() {
        $this->belongs_to('empresas');
        $this->belongs_to('ciudad');

//        $this->validates_presence_of('direccion', 'message: Ingresa la dirección del establecimiento.');
        $this->validates_presence_of('ciudad_id', 'message: Indica la ciudad de ubicación del establecimiento.');
    }

    /**
     * Método para listar los establecimientos
     * @return ActiveRecord
     */
    public function getListadoEstablecimientos($empresas_id = NULL, $estado = 'todos', $order = '', $page = 0) {
        $columns = 'establecimientos.*, empresas.nombres, empresas.razon_social, ciudad.ciudad ';
        $join = 'INNER JOIN empresas ON empresas.id = establecimientos.empresas_id ';
        $join .= 'INNER JOIN ciudad ON ciudad.id = establecimientos.ciudad_id ';
        if (!empty($empresas_id)) {
            $conditions = "empresas.id = $empresas_id AND establecimientos.estado IS NOT NULL ";
        } else {
            $conditions = "empresas.type = '" . Empresas::EMPRESA . "' AND establecimientos.estado IS NOT NULL ";
            $order = 'empresas.nombres';
        }

        $order = $this->get_order($order, 'sucursal', array('sucursal' =>
            array('ASC' => 'establecimientos.sucursal ASC, ciudad.ciudad ASC, empresas.nombres ASC',
                'DESC' => 'establecimientos.sucursal DESC, ciudad.ciudad ASC, empresas.nombres ASC'),
            'ciudad' => array('ASC' => 'ciudad.ciudad ASC, establecimientos.direccion ASC, establecimientos.sucursal ASC, empresas.nombres ASC',
                'DESC' => 'ciudad.ciudad DESC, establecimientos.direccion ASC, establecimientos.sucursal ASC, empresas.nombres ASC'), 'telefono', 'fax', 'direccion'));

        if ($estado != 'todos') {
            $conditions .= ($estado == self::ACTIVO) ? " AND establecimientos.estado=" . self::ACTIVO : " AND establecimientos.estado=" . self::INACTIVO;
        }

        if ($page) {
            return $this->paginated("columns: $columns", "join: $join", "conditions: $conditions", "order: $order", "page: $page");
        } else {
            return $this->find("columns: $columns", "join: $join", "conditions: $conditions", "order: $order");
        }
    }

    /**
     * Método para ver la información del establecimiento
     * @param int|string $id
     * @return Establecimientos
     */
    public function getInformacionEstablecimiento($sucursal_id) {
        $columnas = 'establecimientos.*, empresas.razon_social, empresas.nombres, ciudad.ciudad';
        $join = 'INNER JOIN empresas ON empresas.id = establecimientos.empresas_id ';
        $join .= 'INNER JOIN ciudad ON ciudad.id = establecimientos.ciudad_id ';
        $condicion = "establecimientos.id = '$sucursal_id'";
        return $this->find_first("columns: $columnas", "join: $join", "conditions: $condicion");
    }

    /**
     * Método para ver la información del establecimiento
     * @param int|string $id
     * @return Establecimientos
     */
    /*    public function getInformacionSucursal($id, $isSlug = false) {
      $id = ($isSlug) ? Filter::get($id, 'string') : Filter::get($id, 'numeric');
      $columnas = 'establecimientos.*, empresas.razon_social, empresas.siglas, empresas.legal, ciudad.ciudad';
      $join = 'INNER JOIN empresas ON empresas.id = establecimientos.empresas_id ';
      $join .= 'INNER JOIN ciudad ON ciudad.id = establecimientos.ciudad_id ';
      $condicion = ($isSlug) ? "establecimientos.slug = '$id'" : "establecimientos.id = '$id'";
      return $this->find_first("columns: $columnas", "join: $join", "conditions: $condicion");
      } */

    /**
     * Método que devuelve las sucursales
     * @param string $order
     * @param int $page 
     * @return ActiveRecord
     */
    /*    public function getListadoSucursal($order = 'order.sucursal.asc', $page = '', $empresa = null) {
      $empresa = Filter::get($empresa, 'int');

      $columns = 'establecimientos.*, empresas.siglas, ciudad.ciudad';
      $join = 'INNER JOIN empresas ON empresas.id = establecimientos.empresas_id ';
      $join .= 'INNER JOIN ciudad ON ciudad.id = establecimientos.ciudad_id ';
      $conditions = (empty($empresa)) ? 'establecimientos.id > 0' : "empresas.id = '$empresa'";

      $order = $this->get_order($order, 'sucursal', array(
      'sucursal' => array(
      'ASC' => 'establecimientos.sucursal ASC, ciudad.ciudad ASC, empresas.siglas ASC',
      'DESC' => 'establecimientos.sucursal DESC, ciudad.ciudad ASC, empresas.siglas ASC'),
      'ciudad' => array(
      'ASC' => 'ciudad.ciudad ASC, establecimientos.direccion ASC,'
      . ' establecimientos.sucursal ASC, empresas.siglas ASC',
      'DESC' => 'ciudad.ciudad DESC, establecimientos.direccion ASC,'
      . ' establecimientos.sucursal ASC, empresas.siglas ASC'),
      'telefono',
      'fax',
      'direccion'));
      if ($page) {
      return $this->paginated("columns: $columns", "join: $join", "conditions: $conditions", "order: $order", "page: $page");
      } else {
      return $this->find("columns: $columns", "join: $join", "conditions: $conditions", "order: $order", "page: $page");
      }
      } */

    /**
     * Método para registrar
     */
    public static function setEstablecimientos($method, $data, $optData = NULL) {
        $obj = new Establecimientos($data);
        if ($optData) {
            $obj->dump_result_self($optData);
        }
//        DwOnline::pr($obj);
//        die();
        if ($method != 'delete') {
            if (empty($obj->migra)) {
                if ($obj->type != 'c') {
                    if (empty($obj->sucursal)) {
                        Flash::info('Indica el nombre del establecimiento');
                        return FALSE;
                    }
                }
                if ($obj->type == 'e') {
                    if (empty($obj->codigo_establecimiento)) {
                        Flash::info('Indica el codigo  de establecimiento');
                        return FALSE;
                    }
                }
                if (empty($obj->email)) {
                    Flash::info('Indica un email');
                    return FALSE;
                }
                if (empty($obj->direccion)) {
                    Flash::info('Indica la direccion');
                    return FALSE;
                }
                /*                $ciudad = Input::post('ciudad');
                  if (!empty($ciudad['nuevo'])) {
                  $nombre = $ciudad['ciudad'];
                  $nombre = "$nombre, EC";
                  $obj->ciudad_id = (new Ciudad)->find_by_ciudad("$nombre")->id;
                  } */

                if (empty($obj->ciudad_id)) {
                    Flash::info('Indica la ciudad');
                    return FALSE;
                }
            }

            $obj->metodo = $method;

            if ($obj->type == 'c') {
                $old = (isset($obj->id)) ? $obj->count("empresas_id = '$obj->empresas_id' AND ciudad_id = '$obj->ciudad_id' AND id!= $obj->id") : $obj->count("empresas_id = '$obj->empresas_id' AND ciudad_id = '$obj->ciudad_id'");
            } else {
                $old = (isset($obj->id)) ? $obj->count("empresas_id = '$obj->empresas_id' AND ciudad_id = '$obj->ciudad_id' AND sucursal = '$obj->sucursal' AND codigo_establecimiento LIKE '$obj->codigo_establecimiento' AND id!= $obj->id") : $obj->count("empresas_id = '$obj->empresas_id' AND ciudad_id = '$obj->ciudad_id' AND sucursal = '$obj->sucursal' AND codigo_establecimiento LIKE '$obj->codigo_establecimiento'");
            }
            if ($old) {
                Flash::info('Lo sentimos, pero ya se encuentra un establecimiento registrado bajo el mismo nombre');
                return FALSE;
            }
        } else {
            //Valido el ID
            $obj->id = Filter::get($obj->id, 'int');
            if (empty($obj->id)) {
                Flash::get('id_no_found');
                return FALSE;
            }
            $obj->find_first("columns: establecimientos.*", "conditions: establecimientos.id = $obj->id");
            if ((Session::get('perfil_id') > Perfil::ADMIN)) {
                Flash::error('Tu no tienes los permisos para anular los registros del establecimiento');
                return FAlSE;
            }
        }
//        DwOnline::pr($obj);
//        die();
        return ($obj->$method()) ? $obj : FALSE;
    }

    /**
     * Callback que se ejecuta antes de guardar/modificar
     */
    protected function before_save() {
        $this->sucursal = filter_var($this->sucursal, FILTER_SANITIZE_STRING);
        $this->sucursal_slug = DwUtils::getSlug($this->sucursal);
        $this->direccion = filter_var($this->direccion, FILTER_SANITIZE_STRING);
        $this->telefono = Filter::get($this->telefono, 'numeric');
        $this->celular = Filter::get($this->celular, 'numeric');
        $this->email = Filter::get(strtolower($this->email), 'string');

        if ($this->metodo == 'create') {
            $cuenta = count((new Establecimientos())->find("empresas_id = $this->empresas_id"));
            $office = DwConfig::read('config', array('custom' => 'app_office'));
            if ($office == 'Off') {
                Flash::error('Lo sentimos, pero el establecimiento registrado no tiene permisos.');
                Flash::info('Pongase en contacto con el Administrador del Sistema.');
                return 'cancel';
            }
            $exclusion = DwConfig::read('config', array('custom' => 'establecimientos')) - 1;
            if ($office == 'On' && $cuenta >= $exclusion) {
                Flash::error('Lo sentimos, pero el establecimiento registrado no esta permitido.');
                Flash::info('Pongase en contacto con el Administrador del Sistema.');
                return 'cancel';
            }
        }
    }

    /**
     * Callback que se ejecuta antes de eliminar
     */
    public function before_delete() {
        if ($this->id == 1) { //Para no eliminar la información del establecimiento
            Flash::warning('Lo sentimos, pero este establecimiento no se puede eliminar.');
            return 'cancel';
        }
    }

}
