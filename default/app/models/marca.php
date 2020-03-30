<?php

/**
 * Asdin - Web | App
 *
 * Descripcion: Modelo encargado de registrar marcas
 *
 * @category    
 * @package     Models 
 * @author      Eduardo Yánez (asdin_shop@yahoo.com)
 * @copyright   Copyright (c) 2019 Asdin
 * @revision    1.0
 */
class Marca extends ActiveRecord {

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
        $this->validates_presence_of('marca', 'message: Ingresa el nombre de la marca');
    }

    /**
     * Método para listar las marcas
     * @return array
     */
    public function getListadoMarcas($estado = 'todos', $order = '', $page = 0) {
        $conditions = "marca.activo != 'NULL' ";
        if ($estado != 'todos') {
            $conditions .= ($estado == self::ACTIVO) ? " AND activo=" . self::ACTIVO : " AND activo=" . self::INACTIVO;
        }
        $order = $this->get_order($order, 'marca');
        if ($page) {
            return $this->paginated("conditions: $conditions", "order: $order", "page: $page");
        }
        return $this->find("conditions: $conditions", "order: $order");
    }

    /**
     * Método para buscar marcas
     */
    public function getAjaxMarcas($field, $value, $order = '', $page = 0) {
        $value = Filter::get($value, 'string');
        if (strlen($value) <= 1 OR ( $value == 'none')) {
            return NULL;
        }
        $columns = "marca.*";
        $conditions = "marca.activo != 'NULL' ";

        $order = $this->get_order($order, 'marca');
        //Defino los campos habilitados para la búsqueda
        $fields = array('marca', 'activo');
        if (!in_array($field, $fields)) {
            $field = 'marca';
        }

        $conditions .= " AND UPPER($field) LIKE '%$value%'";

        if ($page) {
            return $this->paginated("columns: $columns", "conditions: $conditions", "order: $order", "page: $page");
        } else {
            return $this->find("columns: $columns", "conditions: $conditions", "order: $order");
        }
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
    public static function setMarca($method, $data, $optData = null) {
        $obj = new Marca($data); //Se carga los datos con los de las tablas        
        if ($optData) { //Se carga información adicional al objeto
            $obj->dump_result_self($optData);
        }
        //Verifico que no exista otra marca, y si se encuentra inactivo lo active
        $conditions = empty($obj->id) ? "marca='$obj->marca' AND activo='$obj->activo'" : "marca='$obj->marca' AND activo='$obj->activo' AND id != '$obj->id'";
        $old = new Marca();
        if ($old->find_first($conditions)) {
            if ($method == 'create' && $old->activo != Marca::ACTIVO) {
                $obj->id = $old->id;
                $obj->activo = Marca::ACTIVO;
                $method = 'update';
            } else {
                Flash::info('Ya existe una marca registrada bajo esos parámetros.');
                return FALSE;
            }
        }
        return ($obj->$method()) ? $obj : FALSE;
    }

    /**
     * Callback que se ejecuta antes de guardar/modificar
     */
    public function before_save() {
        $this->marca = filter_var(strtoupper($this->marca), FILTER_SANITIZE_STRING);
    }

    /**
     * Método consultar las marcas
     */
    public function getInformacionMarcas($id_marca) {
        $colum = "marca.id, marca.marca";
        $condi = "marca.marca = '$id_marca'";
        return $this->find_first("columns: $colum", "conditions: $condi");
    }

}
