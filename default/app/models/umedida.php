<?php

/**
 * Asdin - Web | App
 *
 * Descripcion: Modelo encargado de registrar la unidad de medida
 *
 * @category    
 * @package     Models 
 * @author      Eduardo Yánez (asdin_shop@yahoo.com)
 * @copyright   Copyright (c) 2019 Asdin
 * @revision    1.0
 */
class Umedida extends ActiveRecord {

    //Se desabilita el logger para no llenar el archivo de "basura"
    public $logger = FALSE;

    /**
     * Constante para definir un recurso como activo
     */
    const ACTIVO = 1;

    /**
     * Constante para definir un recurso como inactivo
     */
    const INACTIVO = 0;

    /**
     * Método que se ejecuta antes de cualquier acción
     */
    protected function initialize() {
        $this->validates_presence_of('unidad', 'message: Ingresa el nombre de la unidad de medida');
    }

    /**
     * Método para listar los tipos de identificación
     * @return array
     */
    public function getListadoUnidad($estado = 'todos', $order = '', $page = 0) {
        $conditions = 'umedida.activo IS NOT NULL';
        if ($estado != 'todos') {
            $conditions .= ($estado == self::ACTIVO) ? " AND activo=" . self::ACTIVO : " AND activo=" . self::INACTIVO;
        }
        $order = $this->get_order($order, 'unidad');
        if ($page) {
            return $this->paginated("conditions: $conditions", "order: $order", "page: $page");
        }
        return $this->find("conditions: $conditions", "order: $order");
    }

    /**
     * Método para crear/modificar un objeto de base de datos
     * 
     * @param string $medthod: create, update
     * @param array $data: Data para autocargar el modelo
     * @param array $optData: Data adicional para autocargar
     * 
     * return object ActiveRecord
     */
    public static function setUnidad($method, $data, $optData = null) {
        $obj = new Umedida($data); //Se carga los datos con los de las tablas        
        if ($optData) { //Se carga información adicional al objeto
            $obj->dump_result_self($optData);
        }
        //Verifico que no exista otra utilidad, y si se encuentra inactivo lo active
        $conditions = empty($obj->id) ? "unidad='$obj->unidad' AND simbolo='$obj->simbolo'" : "unidad='$obj->unidad' AND simbolo='$obj->simbolo' AND id != '$obj->id'";
        $old = new Umedida();
        if ($old->find_first($conditions)) {
            if ($method == 'create') {
                $obj->id = $old->id;
                $method = 'update';
            } else {
                Flash::info('Ya existe una unidad de medida registrada bajo esos parámetros.');
                return FALSE;
            }
        }
        return ($obj->$method()) ? $obj : FALSE;
    }

    public function after_save() {
        $this->unidad = filter_var(strtoupper($this->unidad), FILTER_SANITIZE_STRING);
        $this->simbolo = filter_var(strtoupper($this->simbolo), FILTER_SANITIZE_STRING);
    }

    /**
     * Método consultar las unidades
     */
    public function getInformacionUnidad($unidad) {
        $colum = "umedida.id, umedida.unidad";
        $condi = "umedida.unidad = '$unidad'";
        return $this->find_first("columns: $colum", "conditions: $condi");
    }

}
