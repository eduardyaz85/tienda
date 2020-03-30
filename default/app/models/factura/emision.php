<?php

/**
 * Asdin - Web | App
 *
 * Descripcion: Modelo encargado de registrar los niveles (titulos)
 *
 * @category    
 * @package     Models 
 * @author      Eduardo Yánez (asdin_shop@yahoo.com)
 * @copyright   Copyright (c) 2019 Asdin (asdin_shop@yahoo.com) 
 * @revision    1.0
 */
class Emision extends ActiveRecord {

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
        $this->belongs_to('establecimientos');
        $this->validates_presence_of('codigo_emision', 'message: Ingresa el Codigo de Emision');
    }

    /**
     * Método para listar los Emision
     * @return ActiveRecord
     */
    public function getListadoEmision($establecimientos_id = NULL, $estado = 'todos', $order = '', $page = 0) {
        $columns = 'emision.*, establecimientos.codigo_establecimiento, establecimientos.sucursal ';
        $join = 'INNER JOIN establecimientos ON establecimientos.id = emision.establecimientos_id ';
        $conditions = "establecimientos.id = $establecimientos_id AND emision.estado IS NOT NULL ";

        $order = $this->get_order($order, 'sucursal', array(
            'codigo_emision' =>
            array(
                'ASC' => 'emision.codigo_emision ASC, establecimientos.sucursal ASC',
                'DESC' => 'emision.codigo_emision DESC, establecimientos.sucursal ASC'),
            'sucursal' =>
            array(
                'ASC' => 'establecimientos.sucursal ASC, emision.codigo_emision ASC',
                'DESC' => 'establecimientos.sucursal DESC, emision.codigo_emision ASC')
        ));

        if ($estado != 'todos') {
            $conditions .= ($estado == self::ACTIVO) ? " AND emision.estado=" . self::ACTIVO : " AND emision.estado=" . self::INACTIVO;
        }

        if ($page) {
            return $this->paginated("columns: $columns", "join: $join", "conditions: $conditions", "order: $order", "page: $page");
        } else {
            return $this->find("columns: $columns", "join: $join", "conditions: $conditions", "order: $order");
        }
    }

    /**
     * Método para ver la información del emision
     * @param int|string $id
     * @return Establecimientos
     */
    public function getInformacionEmision($emision_id) {
        $columnas = 'emision.*, establecimientos.codigo_establecimiento ';
        $join = 'INNER JOIN establecimientos ON establecimientos.id = emision.establecimientos_id ';
        $condicion = "emision.id = '$emision_id'";
        return $this->find_first("columns: $columnas", "join: $join", "conditions: $condicion");
    }

    /**
     * Método para registrar
     */
    public static function setEmision($method, $data, $optData = NULL) {
        $obj = new Emision($data);
        if ($optData) {
            $obj->dump_result_self($optData);
        }

        if ($method != 'delete') {
            if (empty($obj->establecimientos_id)) {
                Flash::info('Indica el establecimiento');
                return FALSE;
            }
            if (empty($obj->codigo_emision)) {
                Flash::info('Indica el codigo  de emision');
                return FALSE;
            }
            if (empty($obj->codigo_numerico)) {
                Flash::info('Indica el codigo  numerico');
                return FALSE;
            }
            if (empty($obj->tiempo_espera)) {
                Flash::info('Indica el tiempo de espera del ws');
                return FALSE;
            }

            $old = (isset($obj->id)) ? $obj->count("establecimientos_id = '$obj->establecimientos_id' AND codigo_emision = '$obj->codigo_emision' AND id!= $obj->id") : $obj->count("establecimientos_id = '$obj->establecimientos_id' AND codigo_emision = '$obj->codigo_emision'");
            if ($old) {
                Flash::info('Lo sentimos, pero ya se encuentra un punto de emision registrado bajo el mismo nombre');
                return FALSE;
            }
        } else {
            //Valido el ID
            $obj->id = Filter::get($obj->id, 'int');
            if (empty($obj->id)) {
                Flash::error('No se ha podido establcer la informacion');
                return FALSE;
            }
            $obj->find_first("columns: emision.*", "conditions: emision.id = $obj->id");
            if ((Session::get('perfil_id') > Perfil::ADMIN)) {
                Flash::error('Tu no tienes los permisos para anular los registros del punto de emision');
                return FAlSE;
            }
        }

        return ($obj->$method()) ? $obj : FALSE;
    }

    /**
     * Callback que se ejecuta antes de guardar/modificar
     */
    protected function before_save() {
        $this->codigo_emision = filter_var($this->codigo_emision, FILTER_SANITIZE_STRING);
        $this->codigo_numerico = filter_var($this->codigo_numerico, FILTER_SANITIZE_STRING);
        $this->tiempo_espera = Filter::get($this->tiempo_espera, 'numeric');
    }

    /**
     * Callback que se ejecuta antes de eliminar
     */
    public function before_delete() {
        if ($this->id == 1) { //Para no eliminar la información del establecimiento
            Flash::warning('Lo sentimos, pero este punto de emision no se puede eliminar.');
            return 'cancel';
        }
    }

}
