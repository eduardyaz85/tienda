<?php

/**
 * Asdin - Web | App
 *
 * Descripcion: Modelo encargado de registrar ciudades
 *
 * @category    
 * @package     Models 
 * @author      Eduardo Yánez (asdin_shop@yahoo.com)
 * @copyright   Copyright (c) 2019 Asdin
 * @revision    1.0
 */
class Ciudad extends ActiveRecord {

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
        $this->has_many('sucursal');
        $this->validates_presence_of('ciudad', 'message: Ingresa el nombre de la ciudad');
    }

    /**
     * Método para setear
     * 
     * @param array $data
     * @return
     */
    public static function setCiudad($method, $data, $optData = null) {
        $obj = new Ciudad($data); //Se carga los datos con los de las tablas        
        if ($optData) { //Se carga información adicional al objeto
            $obj->dump_result_self($optData);
        }
        //Verifico que no exista otra Ciudad, y si se encuentra inactivo lo active
        $conditions = empty($obj->id) ? "ciudad='$obj->ciudad'" : "ciudad='$obj->ciudad' AND id != '$obj->id'";
        $old = new Ciudad();
        if ($old->find_first($conditions)) {
            if ($method == 'create') {
                $obj->id = $old->id;
                $method = 'update';
            } else {
                Flash::info('Ya existe una ciudad registrada bajo esos parámetros.');
                return FALSE;
            }
        }
        return ($obj->$method()) ? $obj : FALSE;
    }

    /**
     * Callback que se ejecuta antes de guardar/modificar
     */
    public function before_save() {
        $this->ciudad = filter_var(strtoupper($this->ciudad), FILTER_SANITIZE_STRING);
    }

    /**
     * Método que devuelve las ciudades paginadas o para un select
     * @param int $pag Número de página a mostrar.
     * @return ActiveRecord
     */
    public function getListadoCiudad($estado = 'todos', $order = '', $page = 0) {
        $conditions = 'ciudad.activo IS NOT NULL';
        $order = $this->get_order($order, 'ciudad');
        if ($page) {
            return $this->paginated("conditions: $conditions", "order: $order", "page: $page");
        }
        return $this->find("conditions: $conditions", "order: $order");
    }

    /**
     * Método para buscar ciudades
     */
    public function getAjaxCiudad($field, $value, $order = '', $page = 0) {
        $value = Filter::get($value, 'string');
        if (strlen($value) <= 1 OR ( $value == 'none')) {
            return NULL;
        }
        $columns = "ciudad.*";
        $conditions = "ciudad.activo != 'NULL' ";

        $order = $this->get_order($order, 'id');
        //Defino los campos habilitados para la búsqueda
        $fields = array('ciudad', 'activo');
        if (!in_array($field, $fields)) {
            $field = 'ciudad';
        }
        if (!($value == 'todas')) {
            $conditions .= " AND $field LIKE '%$value%'";
        }
        if ($page) {
            return $this->paginated("columns: $columns", "conditions: $conditions", "order: $order", "page: $page");
        } else {
            return $this->find("columns: $columns", "conditions: $conditions", "order: $order");
        }
    }

    /**
     * Método para obtener las ciudades como json
     * @return type
     */
    public function getCiudadesToJson() {
        $rs = $this->find("columns: ciudad", 'group: ciudad', 'order: ciudad ASC');
        $ciudades = array();
        foreach ($rs as $ciudad) {
            $ciudades[] = $ciudad->ciudad;
        }
        return json_encode($ciudades);
    }

    /**
     * Método para verificar la ciudad
     * @return type
     */
    public function getVerificaCiudad($data) {
        $obj = new Ciudad($data); //Se carga los datos con los de las tablas

        if (empty($obj->ciudad)) {
            Flash::info('Indica la ciudad');
            return FALSE;
        }
        if (empty($obj->region)) {
            Flash::info('Indica la region');
            return FALSE;
        }
        $nombre = strtoupper($obj->ciudad);
        $busca_ciudad = (new Ciudad)->find_by_ciudad("$nombre, EC");

        $id_ciudad = 0;
        if (empty($busca_ciudad)) {
            //registramos la ciudad
            $rs = Ciudad::setCiudad('create', NULL, array('ciudad' => "$nombre, EC", 'region' => $obj->region, 'activo' => Ciudad::ACTIVO));
            $id_ciudad = $rs->id;
        } else {
            $id_ciudad = $busca_ciudad->id;
        }
        return $id_ciudad;
    }

}
