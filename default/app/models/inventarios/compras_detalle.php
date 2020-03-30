<?php

class ComprasDetalle extends ActiveRecord {

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
        $this->has_many('compras');
        $this->validates_presence_of('cantidad', 'message: Ingresa la cantidad');
    }

    /**
     * Método para setear
     * 
     * @param array $data
     * @return
     */
    public static function setComprasDetalle($method, $data, $optData = NULL) {
        $obj = new ComprasDetalle($data);
        if ($optData) {
            $obj->dump_result_self($optData);
        }
        if ($method != 'delete') {
            if (empty($obj->precio_unidad)) {
                Flash::info('Indica el precio unitario');
                return FALSE;
            }
            if (empty($obj->cantidad)) {
                Flash::info('Indica la cantidad');
                return FALSE;
            }
            $articulos = new Articulos();
            if (!is_numeric($obj->producto_compra)) {
                $obj->articulos_id = $articulos->getProductoID($obj->producto_compra)->id;
            }
            if ($obj->cantidad) {
                $obj->total = $obj->cantidad * $obj->precio_unidad;
            }
            $old = (isset($obj->id)) ? $obj->count("articulos_id='$obj->articulos_id' AND compras_id='$obj->compras_id' AND id!= $obj->id") : $obj->count("articulos_id='$obj->articulos_id' AND compras_id='$obj->compras_id'");
            if ($old) {
                Flash::info('Lo sentimos, pero ya se encuentra un articulo registrado en la orden de compra');
                return FALSE;
            }
        } else {
            //Valido el ID
            $obj->id = Filter::get($obj->id, 'int');
            if (empty($obj->id)) {
                Flash::error('El id no existe');
                return FALSE;
            }
            $obj->find_first("columns: compras_detalle.*, articulos.nombre", "join: INNER JOIN articulos ON articulos.id = compras_detalle.articulos_id", "conditions: compras_detalle.id = $obj->id");
            if ((Session::get('perfil_id') >= Perfil::ADMIN)) {
                Flash::error('Tu no tienes los permisos para anular los registros de esta orden de compra');
                return FAlSE;
            }
        }

        if ($method == 'delete') {
            $rs = $obj->$method();
            if ($rs) {
                ($method == 'delete') ? DwAudit::debug("Se ha eliminado el articulo de la orden de compra $obj->nombre, codigo $obj->codigo") : 0;
            }
            return ($rs) ? $obj : FALSE;
        } else {
            ($method == 'create') ? DwAudit::debug("Se ha registrado un item en la orden de compra $obj->codigo, articulo $obj->nombre") : DwAudit::debug("Se ha modificado la información del detalle de la orde de compra $obj->codigo, articulo $obj->nombre");
        }
        return ($obj->$method()) ? $obj->getInformacionDetalleCompra($obj->id) : FALSE;
    }

    /**
     * Método que devuelve las facturas paginadas o para un select
     * @param int $pag Número de página a mostrar.
     * @return ActiveRecord
     */
    public function getListadoComprasDetalle($compras_id, $page = 0, $order = '') {
        $colm = "compras_detalle.*, articulos.mpn, articulos.sku, articulos.nombre, articulos.detalle, articulos.tipo_articulo ";
        $join = 'INNER JOIN compras ON compras.id = compras_detalle.compras_id ';
        $join .= 'INNER JOIN articulos ON articulos.id = compras_detalle.articulos_id ';
        $cond = "compras_detalle.compras_id = $compras_id";
        $order = $this->get_order($order, 'registro_at');
        if ($page) {
            return $this->paginated("columns: $colm", "join: $join", "conditions: $cond", "order: $order", "page: $page");
        }
        return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
    }

    /**
     * Método que devuelve las facturas paginadas o para un select
     * @param int $pag Número de página a mostrar.
     * @return ActiveRecord
     */
    public function getInformacionValidar($compras_id, $campos = array(),$order = '') {
        $colm = "compras_detalle.*, articulos.mpn, articulos.sku, articulos.nombre, articulos.detalle, articulos.tipo_articulo ";
        $join = 'INNER JOIN compras ON compras.id = compras_detalle.compras_id ';
        $join .= 'INNER JOIN articulos ON articulos.id = compras_detalle.articulos_id ';
        $cond = "compras_detalle.compras_id = $compras_id ";
        if (!empty($campos)) {
            foreach ($campos as $key => $value) {
                $cond .= " AND  $key LIKE '%$value%' ";
            }
        }
        return $this->find("columns: $colm", "join: $join", "conditions: $cond");
    }

    /**
     * Método para obtener la información del detalle de una factura
     * @return type
     */
    public function getInformacionDetalleCompra($compras_id) {
        $compras_id = Filter::get($compras_id, 'int');
        if (!$compras_id) {
            return NULL;
        }
        $colm = "compras_detalle.*, articulos.nombre, articulos.mpn, articulos.codigo ";
        $join = 'INNER JOIN articulos ON articulos.id = compras_detalle.articulos_id ';
        $cond = "compras_detalle.id = $compras_id";
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond");
    }

    /**
     * Método para obtener los totales del detalle factura
     */
    public function getTotalDetalleFactura($compras_id) {
        $columns = 'compras_detalle.id, compras_detalle.compras_id, sum(compras_detalle.valor_total) AS total ';
        $conditions = "compras_detalle.compras_id = $compras_id ";
        return $this->find_first("columns: $columns", "conditions: $conditions");
    }

}
