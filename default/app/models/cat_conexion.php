<?php

/**
 * Asdin - Web | App
 *
 * Descripcion: Modelo encargado de registrar las conexiones con el Web Service
 *
 * @category    
 * @package     Models 
 * @author      Eduardo Yánez (asdin_shop@yahoo.com)
 * @copyright   Copyright (c) 2019 Asdin
 * @revision    1.0
 */
class CatConexion extends ActiveRecord {

    //Se desabilita el logger para no llenar el archivo de "basura"
    public $logger = FALSE;

    /**
     * Constante para definir un recurso como estado
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
//        $this->validates_presence_of('empresas_id', 'message: Ingresa el nombre de la empresas_id');
    }

    /**
     * Método para listar los cat_conexion
     * @return array
     */
    public function getListaConexiones($estado = 'todos', $order = '', $page = 0) {
        $colm = "cat_conexion.*, empresas.razon_social, empresas.nombres, usuarios.login ";
        $join = 'INNER JOIN empresas ON empresas.id = cat_conexion.empresas_id ';
        $join .= 'LEFT JOIN usuarios ON usuarios.id = cat_conexion.usuarios_id ';
        $cond = "cat_conexion.id != 'NULL' ";
        $order = $this->get_order($order, 'fecha_at', array('id' => 'cat_conexion.id',
            'descripcion' => array(
                'ASC' => 'cat_conexion.fecha_at ASC, empresas.razon_social DESC',
                'DESC' => 'cat_conexion.fecha_at DESC, empresas.razon_social DESC')));
        if ($estado != 'todos') {
            $cond .= ($estado == self::ACTIVO) ? " AND activo=" . self::ACTIVO : " AND activo=" . self::INACTIVO;
        }
        if ($page) {
            return $this->paginated("columns: $colm", "join: $join", "conditions: $cond", "order: fecha_at DESC", "page: $page", "per_page: 28");
        } else {
            return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: fecha_at DESC");
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
    public static function setCatalogoConexion($method, $data, $optData = null) {
        $obj = new CatConexion($data); //Se carga los datos con los de las tablas        
        if ($optData) { //Se carga información adicional al objeto
            $obj->dump_result_self($optData);
        }
        $obj->usuario_conexion = Session::get('login');
        //Verifico que no exista otra empresas_id, y si se encuentra inactivo lo active
        $conditions = empty($obj->id) ? "empresas_id='$obj->empresas_id' AND estado='$obj->estado'" : "empresas_id='$obj->empresas_id' AND estado='$obj->estado' AND id != '$obj->id'";
        $old = new CatConexion();
        if ($old->find_first($conditions)) {
            if ($method == 'create') {
                $obj->id = $old->id;
                $method = 'update';
            } else {
                Flash::info('Ya existe una sincronizacion registrada bajo esos parámetros.');
                return FALSE;
            }
        }
        return ($obj->$method()) ? $obj : FALSE;
    }

}
