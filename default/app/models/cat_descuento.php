<?php
/**
 * Asdin - Web | App
 *
 * Descripcion: Modelo encargado de registrar descuantos para el catalogo
 *
 * @category    
 * @package     Models 
 * @author      Eduardo Yánez (asdin_shop@yahoo.com)
 * @copyright   Copyright (c) 2019 Asdin
 * @revision    1.0
 */
class CatDescuento extends ActiveRecord {

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
        $this->validates_presence_of('fecha_inicio', 'message: Ingresa el nombre de la fecha_inicio');
    }

    /**
     * Método para listar las marcas
     * @return array
     */
    public function getListadoDescuentoCatalogo($estado = 'todos', $order = '', $page = 0) {
        $colm = "catalogo_descuento.*";
        $conditions = "catalogo_descuento.descuento != 'NULL' ";
        if ($estado != 'todos') {
//            $conditions .= ($estado == self::estado) ? " AND estado=" . self::estado : " AND estado=" . self::INACTIVO;
        }
        $order = $this->get_order($order, 'id');
        if ($page) {
            return $this->paginated("columns: $colm", "conditions: $conditions", "order: $order", "page: $page");
        }
        return $this->find("columns: $colm", "conditions: $conditions", "order: $order");
    }

    /**
     * Método verificar el descuento vigente
     */
    public function getListadoDescuentoCatalogoPdf($page = 0) {
        $colum = "catalogo_descuento.*";
        $conditions = "catalogo_descuento.descuento != 'NULL' ";
        $order = "catalogo_descuento.fecha_inicio desc";
        return $this->find("columns: $colum", "join: $join", "conditions: $conditions", "order: $order");
    }

    /**
     * Método verificar el descuento vigente
     */
    public function getDescuentoCatalogo() {
        $fecha = date('Y-m-d');
        $colum = "catalogo_descuento.* ";
        $condi = "'$fecha'"." BETWEEN catalogo_descuento.fecha_inicio AND catalogo_descuento.fecha_fin";
        return $this->find_first("columns: $colum", "conditions: $condi", 'order: id DESC', "limit: 1");
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
    public static function setCatalogoDescuento($method, $data, $optData = null) {
        $obj = new CatDescuento($data); //Se carga los datos con los de las tablas        
        if ($optData) { //Se carga información adicional al objeto
            $obj->dump_result_self($optData);
        }
        $obj->usuario_conexion = Session::get('login');
        //Verifico que no exista otra fecha_inicio, y si se encuentra inactivo lo active
        $conditions = empty($obj->id) ? "fecha_inicio='$obj->fecha_inicio'" : "fecha_inicio='$obj->fecha_inicio' AND id != '$obj->id'";
        $old = new CatDescuento();
        if ($old->find_first($conditions)) {
            if ($method == 'create') {
                $obj->id = $old->id;
                $method = 'update';
            } else {
                Flash::info('Ya existe un descuento registrado bajo esos parámetros.');
                return FALSE;
            }
        }
        return ($obj->$method()) ? $obj : FALSE;
    }

}
