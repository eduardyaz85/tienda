<?php

/**
 * Asdin - Web | App
 *
 * Descripcion: Modelo encargado de registrar los impuestos del catalogo de productos
 *
 * @category    
 * @package     Models 
 * @author      Eduardo Yánez (asdin_shop@yahoo.com)
 * @copyright   Copyright (c) 2019 Asdin
 * @revision    1.0
 */
class CatImpuestos extends ActiveRecord {

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
        $this->has_many('articulos');
    }

    /**
     * Método para setear
     * 
     * @param array $data
     * @return
     */
    public static function setArticulosImpuestos($method, $data, $optData = null) {
        $obj = new CatImpuestos($data); //Se carga los datos con los de las tablas        
        if ($optData) { //Se carga información adicional al objeto
            $obj->dump_result_self($optData);
        }
        //Verifico que no exista otro articulo con el impuesto, y si se encuentra inactivo lo active
        $conditions = empty($obj->id) ? "impuestos_id='$obj->impuestos_id' AND cat_master_id='$obj->cat_master_id'" : "impuestos_id='$obj->impuestos_id' AND cat_master_id='$obj->cat_master_id' AND id != '$obj->id'";
        $old = new CatImpuestos();
        if ($old->find_first($conditions)) {
            if ($method == 'create') {
                $obj->id = $old->id;
                $method = 'update';
            } else {
                Flash::info('Ya existe una impuesto registrado bajo esos parámetros.');
                return FALSE;
            }
        }
        return ($obj->$method()) ? $obj : FALSE;
    }

    /**
     * Método que devuelve las articulos con impuestos paginadas o para un select
     * @param int $pag Número de página a mostrar.
     * @return ActiveRecord
     */
    public function getListadoArticulosImpuestos($articulo_id) {
        $conditions = "cat_impuestos.cat_master_id = $articulo_id";
        return $this->find("conditions: $conditions");
    }

    /**
     * Método que devuelve las articulos con impuestos paginadas o para un select
     * @param int $pag Número de página a mostrar.
     * @return ActiveRecord
     */
    public function getInformacionArticulosImpuestos($articulo_id) {
        $colm = "cat_impuestos.id as id_impuesto, tablas.nombre, tablas.abreviatura, tablas.codigo as codigo_tablas ,tablas_tipos.codigo, tablas_tipos.valida, tablas_tipos.largo, tablas_tipos.detalle, tablas_tipos.titulo, tablas_tipos.id as id_impuesto_tablas, cat_impuestos.cat_master_id ";
        $join = 'INNER JOIN tablas_tipos ON tablas_tipos.id = cat_impuestos.impuestos_id ';
        $join .= 'INNER JOIN tablas ON tablas.id = tablas_tipos.tablas_id ';
        $conditions = "cat_impuestos.cat_master_id = $articulo_id";
        return $this->find("columns: $colm", "conditions: $conditions", "join: $join");
    }

}
