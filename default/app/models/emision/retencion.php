<?php

class Retencion extends ActiveRecord {

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
     * Constante para definir el estado de un documento Electronico
     */
    const PENDIENTE = 0;

    /**
     * Constante para definir el estado de un documento Electronico
     */
    const GENERADO = 1;

    /**
     * Constante para definir el estado de un documento Electronico
     */
    const ENVIADO = 2;

    /**
     * Método que se ejecuta antes de inicializar cualquier acción
     */
    public function initialize() {
        $this->has_many('factura_cabecera');
        $this->validates_presence_of('fecha_emision', 'message: Ingresa una fecha de emisión');
    }

    /**
     * Método para setear
     * @param array $data
     * @return
     */
    public static function setRetencion($method, $data, $optData = null) {
        $obj = new Retencion($data); //Se carga los datos con los de las tablas        
        if ($optData) { //Se carga información adicional al objeto
            $obj->dump_result_self($optData);
        }
        $rs = $obj->$method();
        if ($rs) {
            if ($method == 'creado') {
                DwAudit::debug("Se ha registrado la retencion: $obj->id, codigo: $obj->codigo");
            } else {
                DwAudit::debug("Se ha modificado la retencion: $obj->id, codigo: $obj->codigo");
            }
        }
        return ($rs) ? $obj : FALSE;
    }

    /**
     * Método para setear
     * @param array $data
     * @return
     */
    public static function setDetalleRetencion($method, $data, $optData = null) {
        $obj = new RetencionDetalle($data); //Se carga los datos con los de las tablas        
        if ($optData) { //Se carga información adicional al objeto
            $obj->dump_result_self($optData);
        }
        $rs = $obj->$method();
        if ($rs) {
            if ($method == 'creado') {
                DwAudit::debug("Se ha registrado el detalle de la retencion: $obj->id");
            } else {
                DwAudit::debug("Se ha modificado el detalle de la retencion: $obj->id");
            }
        }
        return ($rs) ? $obj : FALSE;
    }


    /**
     * Método para lista de guias de retencion
     */
    public function getListadoRetencion($estado = 'todos', $order = '', $page = 0) {
        $colm = "retencion.*, retencion_detalle.* ";
        $join = 'INNER JOIN compras ON compras.id = retencion.documento_id ';
        $join .= 'INNER JOIN retencion_detalle ON retencion_detalle.retencion_id = retencion.id ';
        $cond = 'retencion.estado IS NOT NULL ';

        if ($estado != 'todos') {
            $cond .= "and compras.id = $estado ";
        }

        $order = $this->get_order($order, 'fecha_retencion');

        if ($page) {
            return $this->paginated("columns: $colm", "join: $join", "conditions: $cond", "order: $order", "page: $page");
        } else {
            return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
        }
    }

    /**
     * Método para info de guias de retencion
     */
    public function getInfoRetencion($estado = 'todos', $order = '', $page = 0) {
        $colm = "retencion.*, retencion_detalle.* ";
        $join = 'INNER JOIN compras ON compras.id = retencion.documento_id ';
        $join .= 'INNER JOIN retencion_detalle ON retencion_detalle.retencion_id = retencion.id ';
        $cond = 'retencion.estado IS NOT NULL ';

        if ($estado != 'todos') {
            $cond .= "and retencion.id = $estado ";
        }

        $order = $this->get_order($order, 'fecha_retencion');

        if ($page) {
            return $this->paginated("columns: $colm", "join: $join", "conditions: $cond", "order: $order", "page: $page");
        } else {
            return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
        }
    }

    /**
     * Método para info de guias de retencion
     */
    public function getInfoRetencionVentas($estado = 'todos', $order = '', $page = 0) {
        $colm = "retencion.*, retencion_ventas.* ";
        $join = 'INNER JOIN factura_cabecera ON factura_cabecera.id = retencion.documento_id ';
        $join .= 'INNER JOIN retencion_ventas ON retencion_ventas.retencion_id = retencion.id ';
        $cond = 'retencion.estado IS NOT NULL ';

        if ($estado != 'todos') {
            $cond .= "and retencion.documento_id = $estado ";
        }

        $order = $this->get_order($order, 'fecha_retencion');

        if ($page) {
            return $this->paginated("columns: $colm", "join: $join", "conditions: $cond", "order: $order", "page: $page");
        } else {
            return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
        }
    }



    /**
     * Método para obtener la información de una factura
     * @return type
     */
    public function getInformacionFactura($factura_id) {
        $factura_id = Filter::get($factura_id, 'int');
        if (!$factura_id) {
            return NULL;
        }
        $colm = "retencion_ventas.*, ciudad.ciudad, clientes.nombres, clientes.apellidos, clientes.ruc, clientes.tipo_documento, clientes.celular, clientes.email, clientes.email2, clientes.direccion, clientes.telefono ";
        $join = 'INNER JOIN clientes ON clientes.id = retencion_ventas.factura_cabecera_id ';
        $join .= 'INNER JOIN ciudad ON ciudad.id = clientes.ciudad_id ';
        $cond = "retencion_ventas.id = $factura_id";
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond");
    }

    /**
     * Método para obtener la información del numero_retencion de documento
     * @return type
     */
    function getNumeroDocumento($movimiento) {
        $colu = "retencion_ventas.id, retencion_ventas.numero_retencion";
        $cond = "cabecera_cotizacion.codigo != 'NULL' ";
        return $this->find_first("columns: $colu", "conditions: $cond", "order: id desc");
    }

    /**
     * Método para buscar documentos
     */
    public function getAjaxBuscaDocumento($field, $value, $order = '', $page = 0) {
        $value = Filter::get($value, 'string');
        if (strlen($value) <= 2 OR ( $value == 'none')) {
            return NULL;
        }
         $colm = "remision.estado, remision.electronica, remision.transporte_id, remision.motivo, remision.ruta, remision.fecha_ini, remision.fecha_fin, factura_cabecera.id, factura_cabecera.clientes_id, transporte.razon_social, transporte.identificacion_transportista, transporte.ruc, transporte.contabilidad, transporte.especial, transporte.placa, transporte.direccion ";
        $join = 'INNER JOIN factura_cabecera ON factura_cabecera.id = remision.factura_cabecera_id ';
        $join .= 'INNER JOIN clientes ON clientes.id = factura_cabecera.clientes_id ';
        $join .= 'INNER JOIN transporte ON transporte.id = remision.transporte_id ';
        $cond = 'remision.estado IS NOT NULL';

        $order = $this->get_order($order, 'razon_social');
//Defino los campos habilitados para la búsqueda
        $fields = array('fecha_emision', 'numero','apellidos', 'factura_cabecera.ruc');
        if (!in_array($field, $fields)) {
            $field = 'numero';
        }
        if (!($field == 'numero' && $value == 'todas')) {
            $cond .= " AND $field LIKE '%$value%'";
        }
        if ($page) {
            return $this->paginated("columns: $colm", "join: $join", "conditions: $cond", "order: $order", "page: $page");
        } else {
            return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
        }
    }

}
