<?php

/**
 * Asdin - Web | App
 *
 * Descripcion: Modelo encargado de registrar nombres de tablas
 *
 * @category    
 * @package     Models 
 * @author      Eduardo Yánez (asdin_shop@yahoo.com)
 * @copyright   Copyright (c) 2019 Asdin
 * @revision    1.0
 */
class Tablas extends ActiveRecord {

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
     * Método que se ejecuta antes de inicializar cualquier acción
     */
    public function initialize() {
        
    }

    /**
     * Método para listar las marcas
     * @return array
     */
    public function getListadoTablas($estado = 'todos', $order = '', $page = 0) {
        if ($estado == 'todos') {
            $conditions = "tablas.id IS NOT NULL ";
        } else {
            $conditions = "tablas.id IS NOT NULL AND activo = " . Tablas::ACTIVO;
        }
        if ($estado != 'todos') {
            $conditions .= ($estado == self::ACTIVO) ? " AND activo=" . self::ACTIVO : " AND activo=" . self::INACTIVO;
        }
        $order = $this->get_order($order, 'nombre');
        if ($page) {
            return $this->paginated("conditions: $conditions", "order: $order", "page: $page");
        }
        return $this->find("conditions: $conditions", "order: $order");
    }

    /**
     * Método para listar las marcas
     * @return array
     */
    public function getListadoTablasMantenimiento($estado = 'todos', $order = '', $page = 0) {
        $conditions = "tablas.id IS NOT NULL ";
        if ($estado != 'todos') {
            $conditions .= ($estado == self::ACTIVO) ? " AND activo=" . self::ACTIVO : " AND activo=" . self::INACTIVO;
        }
        $order = $this->get_order($order, 'nombre');
        if ($page) {
            return $this->paginated("conditions: $conditions", "order: $order", "page: $page");
        }
        return $this->find("conditions: $conditions", "order: $order");
    }

    /**
     * Método para verificar informacion de la tabla
     * @return array
     */
    public function getInformacionTablasCodigo($codigo, $codigo_retencion) {
        $codigo = Filter::get($codigo, 'int');
        if (empty($codigo)) {
            return NULL;
        }
        $colum = 'tablas.*';
        $join = "INNER JOIN tablas_tipos ON tablas_tipos.tablas_id = tablas.id ";
        $conditions = "tablas.codigo = $codigo and tablas_tipos.valida = $codigo_retencion and tablas.detalle like '%RETENCION%'";
        return $this->find_first("columns: $colum", "join: $join", "conditions: $conditions");
    }

    /**
     * Método para verificar informacion de la tabla
     * @return array
     */
    public function getInformacionTabla($codigo) {
        $codigo = Filter::get($codigo, 'int');
        if (empty($codigo)) {
            return NULL;
        }
        $colum = 'tablas.*';
        $conditions = "tablas.id = $codigo";
        return $this->find_first("columns: $colum", "conditions: $conditions");
    }

    /**
     * Método para setear
     * 
     * @param array $data
     * @return
     */
    public static function setTablas($method, $data, $optData = null) {
        $obj = new Tablas($data); //Se carga los datos con los de las tablas        
        if ($optData) { //Se carga información adicional al objeto
            $obj->dump_result_self($optData);
        }
        $obj->nombre = strtoupper($obj->nombre);
        $obj->abreviatura = strtolower($obj->abreviatura);
        //Verifico que no exista otra tabla, y si se encuentra inactivo lo active
        $conditions = empty($obj->id) ? "nombre='$obj->nombre'" : "nombre='$obj->nombre' AND id != '$obj->id'";
        $old = new Tablas();
        if ($old->find_first($conditions)) {
            if ($method == 'create') {
                $obj->id = $old->id;
                $method = 'update';
            } else {
                Flash::info('Ya existe una tabla registrada bajo esos parámetros.');
                return FALSE;
            }
        }
        return ($obj->$method()) ? $obj : FALSE;
    }

    /**
     * Callback que se ejecuta antes de guardar/modificar
     */
    public function before_save() {
        $this->nombre = filter_var(strtoupper($this->nombre), FILTER_SANITIZE_STRING);
        $this->abreviatura = strtolower($this->abreviatura);
        $this->codigo = strtoupper($this->codigo);
    }

}
