<?php

class FacturaPagos extends ActiveRecord {

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
     * Constante para definir un recurso como pagado en comprobantes
     */
    const GENERADO = 3;

    /**
     * Método que se ejecuta antes de inicializar cualquier acción
     */
    public function initialize() {
        $this->has_many('factura_cabecera');
        $this->validates_presence_of('fecha_emision', 'message: Ingresa una fecha de emisión');
    }

    /**
     * Método para setear
     * 
     * @param array $data
     * @return
     */
    public static function setFacturaPagos($method, $data, $optData = NULL, $tipo = '') {
        $obj = new FacturaPagos($data);
        if ($optData) {
            $obj->dump_result_self($optData);
        }
        if ($method != 'delete') {
            if ($tipo == 'ndb') {
                if (empty($obj->monto)) {
                    Flash::info('Indica el valor de la nota de debito');
                    return FALSE;
                }
            } else {
                if (empty($obj->monto)) {
                    Flash::info('Indica el valor a cobrar');
                    return FALSE;
                }
                if (empty($obj->entrega)) {
                    Flash::info('Indica la cantidad entrega por el cliente');
                    return FALSE;
                }

                $old = (isset($obj->id)) ? $obj->count("factura_cabecera_id='$obj->factura_cabecera_id' AND forma_pago='$obj->forma_pago' AND id!= $obj->id") : $obj->count("factura_cabecera_id='$obj->factura_cabecera_id' AND forma_pago='$obj->forma_pago'");
                if ($old) {
                    Flash::info('Lo sentimos, pero ya se encuentra un pago registrado bajo el mismo metodo');
                    return FALSE;
                }
            }
        } else {
            //Valido el ID
            $obj->id = Filter::get($obj->id, 'int');
            if (empty($obj->id)) {
                Flash::error('El id no existe');
                return FALSE;
            }
            $obj->find_first("columns: factura_pagos.*, factura_cabecera.id", "join: INNER JOIN factura_cabecera ON factura_cabecera.id = factura_pagos.factura_cabecera_id", "conditions: factura_pagos.id = $obj->id");
            if ((Session::get('perfil_id') >= Perfil::ADMIN)) {
                Flash::error('Tu no tienes los permisos para anular los registros de este pago');
                return FAlSE;
            }
        }

        if ($method == 'delete') {
            $rs = $obj->$method();
            if ($rs) {
                ($method == 'delete') ? DwAudit::debug("Se ha eliminado el pago $obj->id") : 0;
            }
            return ($rs) ? $obj : FALSE;
        } else {
            ($method == 'create') ? DwAudit::debug("Se ha registrado un pago $obj->id, valor $obj->monto") : DwAudit::debug("Se ha modificado el pago $obj->id, valor $obj->monto");
        }
        
        return ($obj->$method()) ? $obj->getInformacionPagoFactura($obj->id) : FALSE;
    }

    /**
     * Callback que se ejecuta después de guardar/crear
     */
    public function after_save() {
        //registro la factura como cobrado por el cajero
        if ($this->id) {
            $factura = (new FacturaCabecera)->find_first($this->factura_cabecera_id);
            if ($factura->documento_modifica) {
                $factura->estado = FacturaCabecera::GENERADO;
            } else {
                $factura->estado = FacturaCabecera::ACTIVO;
            }
            try {
                if ($factura->update()) {
                    ActiveRecord::commitTrans();
                    Flash::valid('Se ha modificado el estado de la factura!');
                } else {
                    Flash::warning('Lo sentimos, pero esta factura no se puede modificar.');
                }
            } catch (KumbiaException $e) {
                Flash::error('Esta factura no se puede eliminar porque se encuentra relacionado con otro registro.');
            }
//            FacturaCabecera::setCabeceraFactura('update', NULL, array('id' => $this->id, 'estado' => FacturaCabecera::ACTIVO));
        }
    }

    /**
     * Método para lista de pagos recibidos
     */
    public function getListadoPagosFactura($estado, $order = '', $page = 0) {
        $colm = "factura_cabecera.*, clientes.ruc, clientes.nombres, clientes.apellidos ";
        $join = 'INNER JOIN clientes ON clientes.id = factura_cabecera.clientes_id ';
        $cond = 'factura_cabecera.estado IS NOT NULL';

        $order = $this->get_order($order, 'fecha_emision', array(
            'fecha_emision' => array(
                'ASC' => 'factura_cabecera.fecha_emision ASC, clientes.nombres ASC, clientes.apellidos DESC',
                'DESC' => 'factura_cabecera.fecha_emision DESC, clientes.nombres DESC, clientes.apellidos DESC'
            ),
            'numero' => array(
                'ASC' => 'factura_cabecera.numero ASC',
                'DESC' => 'factura_cabecera.numero DESC'
            ),
            'tipo_comprobante' => array(
                'ASC' => 'factura_cabecera.tipo_comprobante ASC, factura_cabecera.fecha_emision ASC',
                'DESC' => 'factura_cabecera.tipo_comprobante DESC, factura_cabecera.fecha_emision DESC'
            ),
            'apellidos' => array(
                'ASC' => 'clientes.nombres ASC, clientes.apellidos ASC',
                'DESC' => 'clientes.nombres DESC, clientes.apellidos DESC'
            ),
            'estado' => array(
                'ASC' => 'factura_cabecera.estado ASC',
                'DESC' => 'factura_cabecera.estado DESC'
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
    public function getInformacionPagoFactura($pago_id) {
        $pago_id = Filter::get($pago_id, 'int');
        if (!$pago_id) {
            return NULL;
        }
        $colm = "factura_pagos.*, factura_cabecera.numero, factura_cabecera.fecha_emision ";
        $join = 'INNER JOIN factura_cabecera ON factura_cabecera.id = factura_pagos.factura_cabecera_id ';
        $cond = "factura_pagos.id = $pago_id";
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond");
    }

    /**
     * Método para obtener listado de pagos de una factura
     * @return type
     */
    public function getInformacionPagos($factura_id) {
        $factura_id = Filter::get($factura_id, 'int');
        if (!$factura_id) {
            return NULL;
        }
        $colm = "factura_pagos.*, factura_cabecera.numero, factura_cabecera.fecha_emision ";
        $join = 'INNER JOIN factura_cabecera ON factura_cabecera.id = factura_pagos.factura_cabecera_id ';
        $cond = "factura_pagos.factura_cabecera_id = $factura_id";
        return $this->find("columns: $colm", "join: $join", "conditions: $cond");
    }

    /**
     * Método para buscar documentos
     */
    public function getAjaxBuscaPagoFactura($field, $value, $order = '', $page = 0) {
        $value = Filter::get($value, 'string');
        if (strlen($value) <= 2 OR ( $value == 'none')) {
            return NULL;
        }
        $colm = "factura_cabecera.*, clientes.ruc, clientes.nombres, clientes.apellidos, tablas_tipos.titulo ";
        $join = 'INNER JOIN clientes ON clientes.id = factura_cabecera.clientes_id ';
        $join .= 'INNER JOIN tablas_tipos ON tablas_tipos.id = factura_cabecera.tipo_comprobante ';
        $cond = "factura_cabecera.id IS NOT NULL ";

        $order = $this->get_order($order, 'fecha_emision', array('id' => 'factura_cabecera.id',
            'numero' => array(
                'ASC' => 'factura_cabecera.fecha_emision ASC, factura_cabecera.numero DESC',
                'DESC' => 'factura_cabecera.fecha_emision DESC, factura_cabecera.numero DESC')));
        //Defino los campos habilitados para la búsqueda
        $fields = array('fecha_emision', 'numero', 'titulo', 'apellidos', 'ruc', 'estado');
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
