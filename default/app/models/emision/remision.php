<?php

class Remision extends ActiveRecord {

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
    public static function setRetencionVentas($method, $data, $optData = null) {
        $obj = new RetencionVentas($data); //Se carga los datos con los de las tablas        
        if ($optData) { //Se carga información adicional al objeto
            $obj->dump_result_self($optData);
        }

        //genero el numero_retencion de documento
        if ($method == 'create') {
            $numeroDocumento = $obj->getNumeroDocumento();
            $codigo = empty($numeroDocumento->numero_retencion) ? '000000000' : $numeroDocumento->numero_retencion;
            $nuevo = DwOnline::setNumeroDocumento($codigo);
            $obj->numero_retencion = $nuevo;
        }

//Verifico que no exista otra factura, y si se encuentra inactivo lo active
        $conditions = empty($obj->id) ? "factura_cabecera_id='$obj->factura_cabecera_id' AND numero_retencion='$obj->numero_retencion'" : "factura_cabecera_id='$obj->factura_cabecera_id' AND numero_retencion='$obj->numero_retencion' AND id != '$obj->id'";
        $old = new RetencionVentas();
        if ($old->find_first($conditions)) {
            if ($method == 'create') {
                $obj->id = $old->id;
                $method = 'update';
            } else {
                Flash::info('Ya existe una retención bajo esos parámetros.');
                return FALSE;
            }
        }
        $rs = $obj->$method();
        if ($rs) {
            ($method == 'create') ? DwAudit::debug("Se ha registrado una retencion: $obj->numero_retencion, id $obj->id") : DwAudit::debug("Se ha modificado la retencion $obj->numero_retencion, id $obj->id");
        }
        return ($rs) ? $obj : FALSE;
    }

    /**
     * Método para setear
     * @param array $data
     * @return
     */
    public static function setRemisionFactura($method, $data, $optData = null) {
        $obj = new Remision($data); //Se carga los datos con los de las tablas        
        if ($optData) { //Se carga información adicional al objeto
            $obj->dump_result_self($optData);
        }
        $rs = $obj->$method();
        if ($rs) {
            if ($method == 'creado') {
                DwAudit::debug("Se ha registrado la guia de remision: $obj->id, factura: $obj->factura_cabecera_id");
            } else {
                DwAudit::debug("Se ha modificado la guia de remision: $obj->id, factura: $obj->factura_cabecera_id");
            }
        }
        return ($rs) ? $obj : FALSE;
    }

    /**
     * Método para lista de guias de remision
     */
    public function getListadoRemision($estado = 'todos', $order = '', $page = 0) {
        $colm = "remision.id as id_remision, remision.fecha_emision, remision.estado, remision.electronica, remision.transporte_id, remision.motivo, remision.ruta, remision.fecha_ini, remision.fecha_fin, factura_cabecera.id, factura_cabecera.clientes_id, transporte.razon_social, transporte.identificacion_transportista, transporte.ruc, transporte.contabilidad, transporte.especial, transporte.placa, transporte.direccion ";
        $join = 'INNER JOIN factura_cabecera ON factura_cabecera.id = remision.factura_cabecera_id ';
        $join .= 'INNER JOIN clientes ON clientes.id = factura_cabecera.clientes_id ';
        $join .= 'INNER JOIN transporte ON transporte.id = remision.transporte_id ';
        $cond = 'remision.estado IS NOT NULL ';

        if ($estado != 'todos') {
            $cond .= "and factura_cabecera.id = $estado";
        }

        $order = $this->get_order($order, 'razon_social');

        if ($page) {
            return $this->paginated("columns: $colm", "join: $join", "conditions: $cond", "order: $order", "page: $page");
        } else {
            return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
        }
    }

    /**
     * Método para informacion de guias de remision
     */
    public function getInformacionRemision($id) {
        $colm = "remision.id as id_remision, remision.estado, remision.electronica, remision.transporte_id, remision.motivo, remision.ruta, remision.fecha_ini, remision.fecha_fin, factura_cabecera.id, factura_cabecera.clientes_id, transporte.razon_social, transporte.identificacion_transportista, transporte.ruc, transporte.contabilidad, transporte.especial, transporte.placa, transporte.direccion ";
        $join = 'INNER JOIN factura_cabecera ON factura_cabecera.id = remision.factura_cabecera_id ';
        $join .= 'INNER JOIN clientes ON clientes.id = factura_cabecera.clientes_id ';
        $join .= 'INNER JOIN transporte ON transporte.id = remision.transporte_id ';
        $cond = "remision.estado IS NOT NULL and remision.id = $id";

        return $this->find("columns: $colm", "join: $join", "conditions: $cond");
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
