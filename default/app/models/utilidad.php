<?php

/**
 * Asdin - Web | App
 *
 * Descripcion: Modelo encargado de registrar la utilidad en venta
 *
 * @category    
 * @package     Models 
 * @author      Eduardo Yánez (asdin_shop@yahoo.com)
 * @copyright   Copyright (c) 2019 Asdin
 * @revision    1.0
 */
class Utilidad extends ActiveRecord {

    //Se desabilita el logger para no llenar el archivo de "basura"
    public $logger = FALSE;

    /**
     * Método para listar las marcas
     * @return array
     */
    public function getListadoUtilidad($estado = 'todos', $order = '', $page = 0) {
        $conditions = "utilidad.id != 'NULL' ";
        if ($estado != 'todos') {
//            $conditions.= ($estado == self::hasta) ? " AND hasta=" . self::hasta : " AND hasta=" . self::INACTIVO;
        }
        $order = $this->get_order($order, 'desde');
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
    public static function setUtilidad($method, $data, $optData = null) {
        $obj = new Utilidad($data); //Se carga los datos con los de las tablas        
        if ($optData) { //Se carga información adicional al objeto
            $obj->dump_result_self($optData);
        }
        //Verifico que no exista otra utilidad, y si se encuentra inactivo lo active
        $conditions = empty($obj->id) ? "desde='$obj->desde' AND hasta='$obj->hasta'" : "desde='$obj->desde' AND hasta='$obj->hasta' AND id != '$obj->id'";
        $old = new Utilidad();
        if ($old->find_first($conditions)) {
            if ($method == 'create') {
                $obj->id = $old->id;
                $method = 'update';
            } else {
                Flash::info('Ya existe una utilidad registrada bajo esos parámetros.');
                return FALSE;
            }
        }
        return ($obj->$method()) ? $obj : FALSE;
    }

    /**
     * Método para obtener la utilidad de un producto
     */
    public function obtenerPrecioIva($tipo) {
        return $this->find_first("conditions: tipo = '$tipo'");
    }

}
