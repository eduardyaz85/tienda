<?php

class FacturaDetalle extends ActiveRecord {

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
        $this->has_many('factura_cabecera');
        $this->validates_presence_of('cantidad', 'message: Ingresa la cantidad');
    }

    /**
     * Método para setear
     * 
     * @param array $data
     * @return
     */
    public static function setFacturaDetalle($method, $data, $optData = null, $tipo = '') {
        $obj = new FacturaDetalle($data);
        $obj->tipo_doc = $tipo;
        if ($optData) {
            $obj->dump_result_self($optData);
        }
        if ($method != 'delete') {
            if ($tipo == 'ncr') {
                if (empty($obj->valor_total)) {
                    Flash::info('Indica el valor de la nota de credito');
                    return FALSE;
                }
            } else if ($tipo == 'ndb') {
                if (empty($obj->valor_total)) {
                    Flash::info('Indica el valor de la nota de debito');
                    return FALSE;
                }
                if (empty($obj->detalle)) {
                    Flash::info('Indica el detalle de la nota de debito');
                    return FALSE;
                }
            } else {
                if ($obj->estado != 'NULL') {
                    if (empty($obj->valor_unitario)) {
                        Flash::info('Indica el precio del articulo');
                        return FALSE;
                    }
                    if (empty($obj->cantidad)) {
                        Flash::info('Indica la cantidad');
                        return FALSE;
                    }
                    $articulos = new Articulos();
                    if ($obj->cantidad) {
                        if (!empty($obj->descuento)) {
                            $descuento = round(($obj->valor_unitario * $obj->descuento) / 100, 2);
                            $total_dsc = $descuento * $obj->cantidad;
                            $nuevo = $obj->valor_unitario - $descuento;
                            $obj->descuento = $total_dsc;
                            $obj->valor_total = $obj->cantidad * $nuevo;
                        } else {
                            $obj->valor_total = $obj->cantidad * $obj->valor_unitario;
                        }
                    }
                    if (!is_numeric($obj->producto_venta)) {
                        $obj->articulos_id = $articulos->getProductoID($obj->producto_venta)->id;
                    } else {
                        $obj->articulos_id = $obj->articulos_id;
                    }
                } else {
                    $obj->estado = '';
                }
            }
        } else {
            //Valido el ID
            $obj->id = Filter::get($obj->id, 'int');
            if (empty($obj->id)) {
                Flash::error('No se pudo verificar el articulo');
                return FALSE;
            }

            $articulo = $obj->getInformacionDetalleFactura($obj->id);
        }
        $rs = $obj->$method();
        if ($rs) {
            if ($method != 'delete') {
                $articulo = $obj->getInformacionDetalleFactura($obj->id);
                ($method == 'create') ? DwAudit::debug("Se ha registrado un articulo $articulo->nombre ") : DwAudit::debug("Se ha modificado el articulo  $articulo->nombre");
            } else if ($method != 'create') {
                ($method == 'delete') ? DwAudit::debug("Se ha eliminado el articulo id $obj->id, detalle $articulo->nombre") : 0;
                return ($rs) ? $obj : FALSE;
            }
        }
        return ($rs) ? $obj->getInformacionDetalleFactura($obj->id) : NULL;
    }

    /**
     * Callback que se ejecuta después de guardar/crear
     */
    public function after_save() {
        if (!empty($this->articulos_id)) {
            $articulo = (new Articulos);
            $articulos = $articulo->find_first($this->articulos_id);
            if ($articulos->tipo_articulo == 'A' && $articulos->estado != 'NULL' && $this->tipo_doc != 'ncr') {
                DwKardex::kardex($this->articulos_id, KardexDetalle::VENTAS, $this->cantidad, $this->valor_unitario);
            } else {
                DwKardex::kardex($this->articulos_id, KardexDetalle::DEVOLUCION_VENTAS, $this->cantidad, $this->valor_unitario);
            }
        }
    }

    /**
     * Método que devuelve las facturas paginadas o para un select
     * @param int $pag Número de página a mostrar.
     * @return ActiveRecord
     */
    public function getListadoDetalleFactura($factura_id, $page = 0, $order = '') {
        $colm = "factura_detalle.*, factura_detalle.detalle as detalle_factura, umedida.unidad,  ";
        $colm .= "articulos.mpn, articulos.nombre, articulos.detalle, articulos.sku ";
        $join = 'LEFT JOIN articulos ON articulos.id = factura_detalle.articulos_id ';
        $join .= 'LEFT JOIN umedida ON umedida.id = articulos.umedida_id ';
        $cond = "factura_detalle.factura_cabecera_id = $factura_id AND factura_detalle.estado = " . FacturaDetalle::ACTIVO;
        $order = $this->get_order($order, 'nombre');
        if ($page) {
            return $this->paginated("columns: $colm", "join: $join", "conditions: $cond", "order: $order", "page: $page");
        }
        return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
    }

    /**
     * Método para obtener la información del detalle de una factura
     * @return type
     */
    public function getInformacionDetalleFactura($factura_id, $tipo = '') {
        $factura_id = Filter::get($factura_id, 'int');
        if (!$factura_id) {
            return NULL;
        }
        $colm = "factura_detalle.*, articulos.nombre, articulos.mpn, articulos.codigo, articulos.precio_venta, articulos.tipo_articulo ";
        $join = 'LEFT JOIN articulos ON articulos.id = factura_detalle.articulos_id ';
        if ($tipo != '') {
            $cond = "factura_detalle.factura_cabecera_id = $factura_id";
        } else {
            $cond = "factura_detalle.id = $factura_id";
        }
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond");
    }

    /**
     * Método para obtener los totales del detalle factura
     */
    public function getTotalDetalleFactura($factura_id) {
        $columns = 'factura_detalle.id, factura_detalle.factura_cabecera_id,factura_detalle.valor_total, sum(factura_detalle.valor_total) AS total, sum(factura_detalle.descuento) AS total_dsc ';
        $conditions = "factura_detalle.factura_cabecera_id = $factura_id AND factura_detalle.estado = " . FacturaDetalle::ACTIVO;
        return $this->find_first("columns: $columns", "conditions: $conditions");
    }

}
