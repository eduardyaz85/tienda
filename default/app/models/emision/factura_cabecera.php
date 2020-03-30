<?php

class FacturaCabecera extends ActiveRecord {

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
    const ENVIADO = 2;

    /**
     * Constante para definir el estado de un documento Electronico
     */
    const GENERADO = 3;

    /**
     * Constante para definir el estado de un documento Electronico
     */
    const FINALIZADO = 4;

    /**
     * Constante para definir el estado de un documento Electronico
     */
    const ANULADO = 5;

    /**
     * Método que se ejecuta antes de inicializar cualquier acción
     */
    public function initialize() {
        $this->has_many('empresas');
        $this->validates_presence_of('fecha_emision', 'message: Ingresa una fecha de emisión');
    }

    /**
     * Método para lista de facturas
     */
    public function getListadoFacturas($estado, $order = '', $page = 0, $tipo = TablasTipos::FACTURA) {
        $tipo_comprobante = DwOnline::setCodigoDocumento($codigo = '', $tipo)->id;
        $colm = "factura_cabecera.*, empresas.ruc, empresas.nombres, empresas.razon_social ";
        $join = 'INNER JOIN empresas ON empresas.id = factura_cabecera.empresas_id ';
        $cond = "factura_cabecera.estado IS NOT NULL and factura_cabecera.tipo_comprobante = $tipo_comprobante ";

        $order = $this->get_order($order, 'fecha_emision', array(
            'fecha_emision' => array(
                'ASC' => 'factura_cabecera.fecha_emision ASC, empresas.nombres ASC, empresas.razon_social DESC',
                'DESC' => 'factura_cabecera.fecha_emision DESC, empresas.nombres DESC, empresas.razon_social DESC'
            ),
            'numero' => array(
                'ASC' => 'factura_cabecera.numero ASC',
                'DESC' => 'factura_cabecera.numero DESC'
            ),
            'tipo_comprobante' => array(
                'ASC' => 'factura_cabecera.tipo_comprobante ASC, factura_cabecera.fecha_emision ASC',
                'DESC' => 'factura_cabecera.tipo_comprobante DESC, factura_cabecera.fecha_emision DESC'
            ),
            'razon_social' => array(
                'ASC' => 'empresas.nombres ASC, empresas.razon_social ASC',
                'DESC' => 'empresas.nombres DESC, empresas.razon_social DESC'
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
    public function getInformacionFactura($factura_id, $tipo_comprobante = '') {
        $factura_id = Filter::get($factura_id, 'int');
        if (!$factura_id) {
            return NULL;
        }
        $colm = "factura_cabecera.*, ciudad.ciudad, empresas.nombres, empresas.razon_social, empresas.ruc, empresas.tipo_documento, empresas.celular, empresas.email, empresas.email2, empresas.direccion, empresas.telefono, empresas.contabilidad, factura_pagos.propina ";
        $join = 'INNER JOIN empresas ON empresas.id = factura_cabecera.empresas_id  ';
        $join .= 'INNER JOIN ciudad ON ciudad.id = empresas.ciudad_id ';
        $join .= 'LEFT JOIN factura_pagos ON factura_pagos.factura_cabecera_id = factura_cabecera.id ';
        if ($tipo_comprobante != '') {
            $cond = "factura_cabecera.documento_modifica = $factura_id and factura_cabecera.estado = 1";
        } else {
            $cond = "factura_cabecera.id = $factura_id";
        }
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond");
    }

    /**
     * Método para setear
     * @param array $data
     * @return
     */
    public static function setCabeceraFactura($method, $data, $optData = NULL) {
        $obj = new FacturaCabecera($data);
        if ($optData) {
            $obj->dump_result_self($optData);
        }

        if ($method != 'delete') {
            if (empty($obj->empresas_id)) {
                Flash::info('Selecciona un Cliente');
                return FALSE;
            }
            if (empty($obj->fecha_emision)) {
                Flash::info('Selecciona una Fecha de Emision');
                return FALSE;
            }
            if (empty($obj->tipo_comprobante)) {
                Flash::info('Selecciona un Documento');
                return FALSE;
            }

            //obtener id de la transaccion
            if (!is_numeric($obj->tipo_comprobante)) {
                $obj->tipo_comprobante = DwOnline::setCodigoDocumento($codigo = '', $obj->tipo_comprobante)->id;
            } else {
                $obj->tipo_comprobante;
            }

            //verfico el estado de la factura electronica
            if (empty($obj->electronica)) {
                $obj->electronica = FacturaCabecera::PENDIENTE;
            }

            //genero el numero de documento
            if ($method == 'create') {
                $obj->numero = $obj->getNumeroDocumento($obj->tipo_comprobante);
            }

            //Verifico que no exista otra factura, y si se encuentra inactivo lo active
            if ($obj->documento_modifica) {
                $old = (isset($obj->id)) ? $obj->count("empresas_id = '$obj->empresas_id' AND numero = '$obj->numero' AND tipo_comprobante = '$obj->tipo_comprobante' AND id!= $obj->id") : $obj->count("empresas_id = '$obj->empresas_id' AND numero = '$obj->numero' AND tipo_comprobante = '$obj->tipo_comprobante'");
            } else {
                $old = (isset($obj->id)) ? $obj->count("empresas_id = '$obj->empresas_id' AND numero = '$obj->numero' AND id!= $obj->id") : $obj->count("empresas_id = '$obj->empresas_id' AND numero = '$obj->numero'");
            }
            if ($old) {
                Flash::error('Ya existe un documento ' . $obj->numero . '<br> registrado bajo esos parámetros.');
                return FALSE;
            }
        } else {
            //Valido el ID
            $obj->id = Filter::get($obj->id, 'int');
            if (empty($obj->id)) {
                Flash::error('No se ha podido establecer la informacion del documento');
                return FALSE;
            }
            $obj->find_first("columns: factura_cabecera.*", "conditions: factura_cabecera.id = $obj->id");
            if ((Session::get('perfil_id') > Perfil::ADMIN)) {
                Flash::error('Tu no tienes los permisos para anular los registros del Documento');
                return FAlSE;
            }
        }

        return ($obj->$method()) ? $obj : FALSE;
    }

    /**
     * Callback que se ejecuta después de guardar/crear
     */
    public function after_save() {
        
    }

    /**
     * Método para obtener la información de una factura
     * @return type
     */
    public function getInformacionFacturaXml($factura_id) {
        $factura_id = Filter::get($factura_id, 'int');
        if (!$factura_id) {
            return NULL;
        }
        $colm = "factura_cabecera.id as id_factura, ciudad.ciudad, empresas.nombres, empresas.razon_social, empresas.ruc, empresas.tipo_documento, empresas.email, empresas.email2, empresas.direccion, ";
        $colm .= "comprobantes.fecha_emision, comprobantes.tipo_comprobante, comprobantes.tipo_ambiente, comprobantes.serie, comprobantes.numero_secuencial, comprobantes.codigo_numerico, comprobantes.tipo_emision, comprobantes.clave_acceso ";
        $join = 'INNER JOIN empresas ON empresas.id = factura_cabecera.empresas_id ';
        $join .= 'INNER JOIN ciudad ON ciudad.id = empresas.ciudad_id ';
        $join .= 'LEFT JOIN comprobantes ON comprobantes.factura_cabecera_id = factura_cabecera.id ';
        $cond = "factura_cabecera.id = $factura_id";
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond");
    }

    /**
     * Método para obtener la información de varias facturas
     * @return type
     */
    public function getInformacionFacturaAll($arrayFactura) {
        if (!is_array($arrayFactura) || empty($arrayFactura)) {
            return NULL;
        }
        $ids_factura = implode(',', $arrayFactura);
        $colm = "factura_cabecera.*, ciudad.ciudad, empresas.nombres, empresas.razon_social, empresas.ruc, empresas.tipo_documento, empresas.celular, empresas.email, empresas.email2, empresas.direccion, empresas.telefono, comprobantes.fecha_emision, comprobantes.tipo_comprobante, comprobantes.tipo_ambiente, comprobantes.serie, comprobantes.numero_secuencial, comprobantes.tipo_emision, comprobantes.clave_acceso, comprobantes.digito_verificador, tablas_tipos.valida ";
        $join = 'INNER JOIN empresas ON empresas.id = factura_cabecera.empresas_id ';
        $join .= 'INNER JOIN ciudad ON ciudad.id = empresas.ciudad_id ';
        $join .= 'INNER JOIN comprobantes ON comprobantes.cotizacion_cabecera_id = factura_cabecera.id ';
        $join .= 'INNER JOIN tablas_tipos ON tablas_tipos.id = empresas.tipo_documento ';
        $cond = "factura_cabecera.id in($ids_factura)";
        return $this->find("columns: $colm", "join: $join", "conditions: $cond");
    }

    /**
     * Método para obtener la información de una factura
     * @return type
     */
    public function getInformacionDocumentoElectronico($factura_id) {
        $factura_id = Filter::get($factura_id, 'int');
        if (!$factura_id) {
            return NULL;
        }
        $colm = "factura_cabecera.*, ciudad.ciudad, empresas.nombres, empresas.razon_social, empresas.ruc, empresas.tipo_documento, empresas.celular, empresas.email, empresas.email2, empresas.direccion, empresas.telefono, comprobantes.fecha_emision, comprobantes.tipo_comprobante, comprobantes.tipo_ambiente, comprobantes.serie, comprobantes.numero_secuencial, comprobantes.tipo_emision, comprobantes.clave_acceso, comprobantes.digito_verificador ";
        $join = 'INNER JOIN empresas ON empresas.id = factura_cabecera.empresas_id ';
        $join .= 'INNER JOIN ciudad ON ciudad.id = empresas.ciudad_id ';
        $join .= 'LEFT JOIN comprobantes ON comprobantes.cotizacion_cabecera_id = factura_cabecera.id ';
        $cond = "factura_cabecera.id = $factura_id";
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond");
    }

    /**
     * Método para obtener la información del numero de documento
     * @return type
     */
    function getNumeroDocumento($movimiento) {
        $colu = "factura_cabecera.id, factura_cabecera.numero";
        $cond = "factura_cabecera.tipo_comprobante = '$movimiento' ";
        $rs = $this->find_first("columns: $colu", "conditions: $cond", "order: id desc");
        if (!empty($rs->numero)) {
            $codigo = $rs->numero;
            $nuevo = DwOnline::setNumeroDocumento($codigo);
        } else {
            $codigo = '000000000';
        }
        return $nuevo;
    }

    /**
     * Método para buscar documentos
     */
    public function getAjaxBuscaDocumento($field, $value, $order = '', $page = 0, $tipo = TablasTipos::FACTURA) {
        $tipo_comprobante = DwOnline::setCodigoDocumento($codigo = '', $tipo)->id;
        $value = Filter::get($value, 'string');
        if (strlen($value) <= 2 OR ( $value == 'none')) {
            return NULL;
        }
        $colm = "factura_cabecera.*, empresas.ruc, empresas.nombres, empresas.razon_social, tablas_tipos.titulo ";
        $join = 'INNER JOIN empresas ON empresas.id = factura_cabecera.empresas_id ';
        $join .= 'INNER JOIN tablas_tipos ON tablas_tipos.id = factura_cabecera.tipo_comprobante ';
        $cond = "factura_cabecera.id IS NOT NULL and factura_cabecera.tipo_comprobante = $tipo_comprobante ";

        $order = $this->get_order($order, 'fecha_emision', array('id' => 'factura_cabecera.id',
            'numero' => array(
                'ASC' => 'factura_cabecera.fecha_emision ASC, factura_cabecera.numero DESC',
                'DESC' => 'factura_cabecera.fecha_emision DESC, factura_cabecera.numero DESC')));
//Defino los campos habilitados para la búsqueda
        $fields = array('fecha_emision', 'numero', 'titulo', 'razon_social', 'ruc', 'estado');
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
