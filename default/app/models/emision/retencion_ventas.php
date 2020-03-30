<?php

class RetencionVentas extends ActiveRecord {

//Se desabilita el logger para no llenar el archivo de "basura"
    public $logger = FALSE;

    /**
     * Constante para definir un recurso como activo
     */
    const CREADO = 1;

    /**
     * Constante para definir un recurso como inactivo
     */
    const INACTIVO = 0;

    /**
     * Constante para definir el estado de un documento Electronico
     */
    const VERIFICADO = 2;

    /**
     * Constante para definir el estado de un documento Electronico
     */
    const CONFIRMADO = 3;

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
    public static function setDetalleRetencion($method, $data, $optData = null) {
        $obj = new RetencionVentas($data); //Se carga los datos con los de las tablas        
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
     * Método para lista de facturas
     */
    public function getListadoFacturas($estado, $order = '', $page = 0) {
        $colm = "retencion_ventas.*, clientes.ruc, clientes.nombres, clientes.apellidos ";
        $join = 'INNER JOIN clientes ON clientes.id = retencion_ventas.factura_cabecera_id ';
        $cond = 'retencion_ventas.estado IS NOT NULL';

        $order = $this->get_order($order, 'fecha_emision', array(
            'fecha_emision' => array(
                'ASC' => 'retencion_ventas.fecha_emision ASC, clientes.nombres ASC, clientes.apellidos DESC',
                'DESC' => 'retencion_ventas.fecha_emision DESC, clientes.nombres DESC, clientes.apellidos DESC'
            ),
            'numero_retencion' => array(
                'ASC' => 'retencion_ventas.numero_retencion ASC',
                'DESC' => 'retencion_ventas.numero_retencion DESC'
            ),
            'tipo_comprobante' => array(
                'ASC' => 'retencion_ventas.tipo_comprobante ASC, retencion_ventas.fecha_emision ASC',
                'DESC' => 'retencion_ventas.tipo_comprobante DESC, retencion_ventas.fecha_emision DESC'
            ),
            'apellidos' => array(
                'ASC' => 'clientes.nombres ASC, clientes.apellidos ASC',
                'DESC' => 'clientes.nombres DESC, clientes.apellidos DESC'
            ),
            'estado' => array(
                'ASC' => 'retencion_ventas.estado ASC',
                'DESC' => 'retencion_ventas.estado DESC'
            )
        ));

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
        $colm = "retencion_ventas.*, clientes.ruc, clientes.nombres, clientes.apellidos, tablas_tipos.titulo ";
        $join = 'INNER JOIN clientes ON clientes.id = retencion_ventas.factura_cabecera_id ';
        $join .= 'INNER JOIN tablas_tipos ON tablas_tipos.id = retencion_ventas.tipo_comprobante ';
        $cond = "retencion_ventas.id IS NOT NULL ";

        $order = $this->get_order($order, 'fecha_emision', array('id' => 'retencion_ventas.id',
            'numero_retencion' => array(
                'ASC' => 'retencion_ventas.fecha_emision ASC, retencion_ventas.numero_retencion DESC',
                'DESC' => 'retencion_ventas.fecha_emision DESC, retencion_ventas.numero_retencion DESC')));
//Defino los campos habilitados para la búsqueda
        $fields = array('fecha_emision', 'numero_retencion', 'titulo', 'apellidos', 'ruc', 'estado');
        if (!in_array($field, $fields)) {
            $field = 'numero_retencion';
        }
        if (!($field == 'numero_retencion' && $value == 'todas')) {
            $cond .= " AND $field LIKE '%$value%'";
        }
        if ($page) {
            return $this->paginated("columns: $colm", "join: $join", "conditions: $cond", "order: $order", "page: $page");
        } else {
            return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
        }
    }

}
