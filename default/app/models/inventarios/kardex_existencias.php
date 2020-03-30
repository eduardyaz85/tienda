<?php

class KardexExistencias extends ActiveRecord {

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

    public function ultimosPrecios() {
        $precios = array();
        foreach ($this->find() as $e) {
            $precios["{{$e->costo_unitario}"] = $e->costo_unitario;
        }
        return $precios;
    }

    /**
     * Método para setear
     * 
     * @param array $data
     * @return
     */
    public static function setKardexInicial($method, $data, $optData = NULL) {
        $obj = new Kardex($data);
        if ($optData) {
            $obj->dump_result_self($optData);
        }
        if ($method != 'delete') {
            if (empty($obj->minimo)) {
                Flash::info('Indica el stock minimo');
                return FALSE;
            }
            if (empty($obj->minimo)) {
                Flash::info('Indica el stock maximo');
                return FALSE;
            }
            if (empty($obj->bodegas_id)) {
                Flash::info('Indica la bodega');
                return FALSE;
            }
            $old = (isset($obj->id)) ? $obj->count("articulos_id='$obj->articulos_id' AND id!= $obj->id") : $obj->count("articulos_id='$obj->articulos_id'");
            if ($old) {
                Flash::info('Lo sentimos, pero ya se encuentra un articulo registrado con este nombre en el Kardex');
                return FALSE;
            }
        } else {
            //Valido el ID antes de eliminar
            $obj->id = Filter::get($obj->id, 'int');
            if (empty($obj->id)) {
                Flash::error('El id del kardex no existe');
                return FALSE;
            }
            $obj->find_first("columns: kardex.*", "conditions: kardex.id = $obj->id");
            if ((Session::get('perfil_id') >= Perfil::ADMIN)) {
                Flash::error('Tu no tienes los permisos para anular los registros de este articulo');
                return FAlSE;
            }
        }

        $rs = $obj->$method();
        if ($rs) {
            ($method == 'create') ? DwAudit::debug("Se ha registrado un item en el kardex $obj->numero, id $obj->id") : DwAudit::debug("Se ha modificado la información del kardex $obj->numero, id $obj->id");
        }
        return ($rs) ? $obj : FALSE;
    }

    /**
     * Método consultar el stock actual del articulo
     */
    public function getInformacionStockActual($producto, $limit = '') {
        $colum = "kardex_existencias.id, kardex_existencias.cantidad_existencia, kardex_existencias.costo_unitario, kardex.articulos_id, articulos.sku, articulos.nombre, articulos.precio_venta, articulos.tipo_articulo ";
        $join = 'INNER JOIN kardex_detalle ON kardex_detalle.id = kardex_existencias.kardex_detalle_id ';
        $join .= 'LEFT JOIN kardex ON kardex.id = kardex_detalle.kardex_id ';
        $join .= 'LEFT JOIN articulos ON articulos.id = kardex.articulos_id ';
        if (!is_numeric($producto)) {
            $producto = $this->getProductoID($producto)->id;
        }
        $condi = "kardex.articulos_id = '$producto' ";
        $order = "kardex_existencias.id desc";
        if ($limit == '3') {
            return $this->find("columns: $colum", "join: $join", "conditions: $condi", "order: $order", "limit: $limit");
        } else {
            return $this->find_first("columns: $colum", "join: $join", "conditions: $condi", "order: $order", "limit: $limit");
        }
    }

}
