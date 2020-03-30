<?php

/**
 * Asdin - Web | App
 *
 * Descripcion: Modelo encargado de registrar los impuestos
 *
 * @category    
 * @package     Models 
 * @author      Eduardo Yánez (asdin_shop@yahoo.com)
 * @copyright   Copyright (c) 2019 Asdin
 * @revision    1.0
 */
class Impuestos extends ActiveRecord {

    //Se desabilita el logger para no llenar el archivo de "basura"
    public $logger = FALSE;

    /**
     * Constante para definir un menú como estado
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
        $this->belongs_to('tablas_tipos');
        $this->validates_presence_of('titulo', 'message: Ingresa el Nombre del Impuesto');
    }

    /**
     * Método para listar los Impuestos
     * @return ActiveRecord
     */
    public function getListadoImpuestos($impuesto, $estado = 'todos', $order = '', $page = 0) {
        $columns = 'impuestos.*, tablas_tipos.codigo, tablas_tipos.titulo ';
        $join = 'INNER JOIN tablas_tipos ON tablas_tipos.id = impuestos.impuesto_id ';
        $conditions = "impuestos.impuesto_id = $impuesto AND estado IS NOT NULL";
        $order = $this->get_order($order, 'impuesto', array(
            'impuesto' =>
            array(
                'ASC' => 'impuestos.impuesto ASC, tablas_tipos.titulo ASC',
                'DESC' => 'impuestos.impuesto DESC, tablas_tipos.titulo ASC'),
            'titulo' =>
            array(
                'ASC' => 'tablas_tipos.titulo ASC, impuestos.impuesto ASC',
                'DESC' => 'tablas_tipos.titulo DESC, impuestos.impuesto ASC')
        ));

        if ($estado != 'todos') {
            $conditions .= ($estado == self::ACTIVO) ? " AND impuestos.estado=" . self::ACTIVO : " AND impuestos.estado=" . self::INACTIVO;
        }

        if ($page) {
            return $this->paginated("columns: $columns", "join: $join", "conditions: $conditions", "order: $order", "page: $page");
        } else {
            return $this->find("columns: $columns", "join: $join", "conditions: $conditions", "order: $order");
        }
    }

    /**
     * Método que devuelve una lista de acuerdo a la tabla solicida en el parametro
     * @return ActiveRecord
     */
    public function getListaTablasImpuestos($codigo = '', $tipo = '') {
        $colm = 'impuestos.*, tablas_tipos.titulo ';
        $join = "INNER JOIN tablas_tipos ON tablas_tipos.id = impuestos.impuesto_id ";
        $join .= "LEFT JOIN tablas ON tablas.id = tablas_tipos.tablas_id ";
        $cond = "tablas_tipos.codigo = '$codigo' AND impuestos.tipo_impuesto = '$tipo' AND impuestos.fecha_fin IS NULL";
        return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: id");
    }

    /**
     * Método para ver la información del impuestos
     * @param int|string $id
     * @return Establecimientos
     */
    public function getInformacionImpuesto($emision_id) {
        $columnas = 'impuestos.*, tablas_tipos.codigo, tablas_tipos.titulo ';
        $join = 'INNER JOIN tablas_tipos ON tablas_tipos.id = impuestos.impuesto_id ';
        $condicion = "impuestos.id = '$emision_id' AND estado IS NOT NULL";
        return $this->find_first("columns: $columnas", "join: $join", "conditions: $condicion");
    }

    /**
     * Método para registrar
     */
    public static function setImpuesto($method, $data, $optData = NULL) {
        $obj = new Impuestos($data);
        if ($optData) {
            $obj->dump_result_self($optData);
        }

        if ($method != 'delete') {
            if (empty($obj->impuesto_id)) {
                Flash::info('Indica la Tabla Impuesto');
                return FALSE;
            }
            if (empty($obj->codigo_impuesto)) {
                Flash::info('Indica el codigo  de impuesto');
                return FALSE;
            }
            if (empty($obj->valor_retencion)) {
                Flash::info('Indica el valor a retener');
                return FALSE;
            }
            if (empty($obj->impuesto)) {
                Flash::info('Indica el nombre del impuesto');
                return FALSE;
            }
            if (empty($obj->fecha_inicio)) {
                Flash::info('Indica la fecha de inicio');
                return FALSE;
            }

            $old = (isset($obj->id)) ? $obj->count("fecha_inicio = '$obj->fecha_inicio' AND impuesto_id = '$obj->impuesto_id' AND impuesto = '$obj->impuesto' AND codigo_impuesto = '$obj->codigo_impuesto' AND id!= $obj->id") : $obj->count("fecha_inicio = '$obj->fecha_inicio' AND impuesto_id = '$obj->impuesto_id'AND impuesto = '$obj->impuesto' AND codigo_impuesto = '$obj->codigo_impuesto'");
            if ($old) {
                Flash::info('Lo sentimos, pero ya se encuentra un impuesto registrado bajo el mismo nombre');
                return FALSE;
            }
        } else {
            //Valido el ID
            $obj->id = Filter::get($obj->id, 'int');
            if (empty($obj->id)) {
                Flash::error('No se ha podido establcer la informacion');
                return FALSE;
            }
            $obj->find_first("columns: impuestos.*", "conditions: impuestos.id = $obj->id");
            if ((Session::get('perfil_id') > Perfil::ADMIN)) {
                Flash::error('Tu no tienes los permisos para anular los registros del impuesto');
                return FAlSE;
            }
        }

        return ($obj->$method()) ? $obj : FALSE;
    }

    /**
     * Callback que se ejecuta antes de guardar/modificar
     */
    protected function before_save() {
        $this->impuesto = filter_var($this->impuesto, FILTER_SANITIZE_STRING);
        $this->codigo_numerico = filter_var($this->codigo_numerico, FILTER_SANITIZE_STRING);
        $this->tipo_impuesto = strtoupper($this->tipo_impuesto);
        $this->valor_retencion = Filter::get($this->valor_retencion, 'numeric');
    }

    /**
     * Callback que se ejecuta antes de eliminar
     */
    public function before_delete() {
        if ($this->id == 1) { //Para no eliminar la información del establecimiento
            Flash::warning('Lo sentimos, pero este impuesto no se puede eliminar.');
            return 'cancel';
        }
    }

    /**
     * getById
     * busca la descripcion del Impuesto usando el id
     */
    public function getById($impuesto = '') {
        return $this->find_by_id($impuesto);
    }

}
