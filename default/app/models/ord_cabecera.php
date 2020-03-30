<?php

/**
 * Asdin - Web | App
 *
 * Descripcion: Modelo encargado de registrar las ordenes
 *
 * @category    
 * @package     Models 
 * @author      Eduardo Yánez (asdin_shop@yahoo.com)
 * @copyright   Copyright (c) 2019 Asdin
 * @revision    1.0
 */
class OrdCabecera extends ActiveRecord {

    //Se desabilita el logger para no llenar el archivo de "basura"
    public $logger = FALSE;

    /**
     * Método que se ejecuta antes de inicializar cualquier acción
     */
    public function initialize() {
        $this->has_many('empresas');
        $this->validates_presence_of('fecha_emision', 'message: Ingresa una fecha de emisión');
    }

    /**
     * Método que devuelve el inner join con el estado_orden
     * @return string
     */
    public static function getInnerEstado() {
        return "INNER JOIN ( SELECT ord_estados.ord_cabecera_id, ord_estados.usuarios_id, ord_estados.motivo, ord_estados.estado_orden FROM ord_estados GROUP BY ord_estados.ord_cabecera_id DESC ) eo ON eo.ord_cabecera_id = ord_cabecera.id ";
    }

    /**
     * Método para lista de ordenes
     */
    public function getListadoOrdenes($estado, $order = '', $page = 0) {
        $colm = "ord_cabecera.*, empresas.nombres, empresas.razon_social, empresas.ruc, empresas.credito, empresas.tiempo, empresas.tipo_documento, eo.estado_orden, est.direccion, est.email, est.telefono, est.celular, ciudad.ciudad ";
        $join = self::getInnerEstado();
        $join .= 'INNER JOIN empresas ON empresas.id = ord_cabecera.empresas_id ';
        $join .= 'LEFT JOIN establecimientos AS est ON est.empresas_id = empresas.id ';
        $join .= 'LEFT JOIN ciudad ON ciudad.id = est.ciudad_id ';
        $cond = 'ord_cabecera.id IS NOT NULL';
        $order = $this->get_order($order, 'fecha_emision');

        if ($page) {
            return $this->paginated("columns: $colm", "join: $join", "conditions: $cond", "order: $order", "page: $page");
        } else {
            return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
        }
    }

    /**
     * Método para obtener la información de una orden
     * @return type
     */
    public function getInformacionOrden($orden_id) {
        $colm = "ord_cabecera.*, empresas.nombres, empresas.razon_social, empresas.ruc, empresas.credito, empresas.tiempo, empresas.tipo_documento, eo.estado_orden, est.direccion, est.email, est.telefono, est.celular, ciudad.ciudad ";
        $join = self::getInnerEstado();
        $join .= 'LEFT JOIN empresas ON empresas.id = ord_cabecera.empresas_id ';
        $join .= 'LEFT JOIN establecimientos AS est ON est.empresas_id = empresas.id ';
        $join .= 'LEFT JOIN ciudad ON ciudad.id = est.ciudad_id ';
        $cond = "ord_cabecera.id = $orden_id";
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond");
    }

    /**
     * Método para buscar una orden abierta para el carro de compras
     * @return type
     */
    public function getOrdenAbierta($usuarios_id, $fecha_actual, $cliente_id) {
        $colm = "ord_cabecera.*, empresas.nombres, empresas.razon_social, empresas.ruc, empresas.tipo_documento, eo.estado_orden, est.direccion, est.email, est.telefono, est.celular ";
        $join = self::getInnerEstado();
        $join .= 'INNER JOIN empresas ON empresas.id = ord_cabecera.empresas_id ';
        $join .= 'LEFT JOIN establecimientos AS est ON est.empresas_id = empresas.id ';
        $cond = "fecha_emision = '$fecha_actual' AND eo.estado_orden = " . OrdEstados::GENERADO;
        if (!empty($usuarios_id)) {
            $cond .= " AND ord_cabecera.usuarios_id = $usuarios_id ";
        } else {
            $cond .= " AND ord_cabecera.empresas_id = $cliente_id ";
        }
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond");
    }

    /**
     * Método para setear
     * @param array $data
     * @return
     */
    public static function setCatalogoCabecera($method, $data, $optData = null) {
        $obj = new OrdCabecera($data); //Se carga los datos con los de las tablas        
        if ($optData) { //Se carga información adicional al objeto
            $obj->dump_result_self($optData);
        }
//        DwOnline::pr($obj);
//        die();
        if (!empty($obj->backend)) {
            $empresa = (new Empresas)->getInformacionEmpresas($obj->empresas_id);
            $obj->ciudad_id = $empresa->ciudad_id;

            if (!empty(Input::post('modifica'))) {
                $sucursal = (new Establecimientos)->find_by_empresas_id($obj->empresas_id);
                $empresa->id = $data['empresas_id'];
                $empresa->nombres = $data['nombres'];
                $empresa->razon_social = $data['razon_social'];
                $empresa->tipo_documento = $data['tipo_documento'];
                $empresa->ruc = $data['ruc'];
                if (!$empresa->update()) {
                    Flash::error('Ha ocurrido un error al actualizar el cliente');
                }

                $sucursal->direccion = $data['direccion'];
                $sucursal->email = $data['email'];
                $sucursal->telefono = $data['telefono'];
                if (!$sucursal->update()) {
                    Flash::error('Ha ocurrido un error al actualizar la direccion');
                }
            }
        }

        $obj->usuarios_id = Session::get('id');

        //genero el numero de documento
        if ($method == 'create') {
            $codSis = $obj->getNumeroDocumento($obj->tipo_orden);
            if ($obj->tipo_orden == 'o') {
                $codigo = empty($codSis->numero) ? 'ORD-00000' : $codSis->numero;
            } else if ($obj->tipo_orden == 'c') {
                $codigo = empty($codSis->numero) ? 'COT-00000' : $codSis->numero;
            } else {
                Flash::error('!Ops. Ha ocurrido un error al crear el codigo');
                return FALSE;
            }
            $nuevo = DwOnline::setNumeroSecuencial($codigo);
            $obj->numero = $nuevo;
        }

        //Verifico que no exista otra factura, y si se encuentra inactivo lo active
        $conditions = empty($obj->id) ? "empresas_id='$obj->empresas_id' AND numero='$obj->numero'" : "empresas_id='$obj->empresas_id' AND numero='$obj->numero' AND id != '$obj->id'";
        $old = new OrdCabecera();
        if ($old->find_first($conditions)) {
            if ($method == 'create') {
                $obj->id = $old->id;
                $method = 'update';
            } else {
                Flash::info('Ya existe un registro bajo esos parámetros.');
                return FALSE;
            }
        }
//        DwOnline::pr($obj);
//        die();
        $rs = $obj->$method();
        return ($rs) ? $obj->getInformacionOrden($obj->id) : FALSE;
    }

    /**
     * Método que se ejecuta antes de guardar y/o modificar     
     */
    public function before_save() {
        $this->pagos = strtolower($this->pagos);
    }

    /**
     * Callback que se ejecuta despues de insertar una orden
     */
    protected function after_create() {
        if (!empty($this->backend)) {
            $mensaje = 'Generado por backend';
        } else {
            $mensaje = 'Generado por carro de compras Cliente';
        }
        if (!OrdEstados::setEstadoOrden('registrar', array('ord_cabecera_id' => $this->id, 'motivo' => $mensaje))) {
            Flash::error('Se ha producido un error interno al registrar la orden. Por favor intenta nuevamente.');
            return 'cancel';
        }
    }

    /**
     * Callback que se ejecuta despues de modificar una orden
     */
    protected function after_update() {
//        DwOnline::pr('actualizacion');
//        DwOnline::pr($this);
//        die();
        if (Input::post('estado') == OrdEstados::PROCESADO) {
            DwOnline::pr('procesado');
            die();
            if (!OrdEstados::setEstadoOrden('mantenimiento', array('ord_cabecera_id' => $this->id, 'motivo' => 'Envio a mantenimiiento'))) {
                Flash::error('Se ha producido un error interno al enviar a mantenimineto. Pofavor intenta nuevamente.');
                return 'cancel';
            }
        }
        if (Input::post('estado') == OrdEstados::APROBADO) {
            DwOnline::pr('procesado');
            die();
            if (!OrdEstados::setEstadoMaterial('reactivar', array('ord_cabecera_id' => $this->id, 'motivo' => 'Devolucion mantenimiiento'))) {
                Flash::error('Se ha producido un error interno al enviar a mantenimineto. Pofavor intenta nuevamente.');
                return 'cancel';
            }
        }
    }

    /**
     * Método para obtener la información del numero de documento
     * @return type
     */
    function getNumeroDocumento($tipo_orden) {
        $colu = "ord_cabecera.id, ord_cabecera.numero, ord_cabecera.tipo_orden";
        $cond = "ord_cabecera.numero != 'NULL' AND tipo_orden = '$tipo_orden' ";
        return $this->find_first("columns: $colu", "conditions: $cond", "order: id desc");
    }

    /**
     * Método para buscar documentos
     */
    public function getAjaxBuscaDocumento($value, $order = '', $page = 0) {
        $value = Filter::get($value, 'string');
        if (strlen($value) <= 2 OR ( $value == 'none')) {
            return NULL;
        }
        $colm = "ord_cabecera.*, empresas.nombres, empresas.razon_social, empresas.ruc, empresas.credito, empresas.tiempo, empresas.tipo_documento, eo.estado_orden, est.direccion, est.email, est.telefono, est.celular, ciudad.ciudad ";
        $join = self::getInnerEstado();
        $join .= 'LEFT JOIN empresas ON empresas.id = ord_cabecera.empresas_id ';
        $join .= 'LEFT JOIN establecimientos AS est ON est.empresas_id = empresas.id ';
        $join .= 'LEFT JOIN ciudad ON ciudad.id = est.ciudad_id ';
        $cond = "(fecha_emision LIKE '%$value%' OR "
                . "numero LIKE '%$value%' OR "
                . "empresas.ruc LIKE '%$value%' OR "
                . "empresas.nombres LIKE '%$value%' OR "
                . "empresas.razon_social LIKE '%$value%' )";

        $order = $this->get_order($order, 'fecha_emision');
        if ($page) {
            return $this->paginated("columns: $colm", "join: $join", "conditions: $cond", "order: $order", "page: $page");
        } else {
            return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
        }
    }

    /**
     * Método para obtener la información de la orden de compra
     * @return type
     */
    public function getInformacionOrdenCompraResumen($usuario_id) {
        $colm = 'ord_cabecera.*, eo.estado_orden ';
        $join = 'LEFT JOIN usuarios ON usuarios.id = ord_cabecera.usuarios_id ';
        $join .= "LEFT JOIN ( SELECT ord_cabecera_id, motivo, fecha_estado_at, estado_orden FROM ord_estados GROUP BY ord_cabecera_id DESC ) eo ON eo.ord_cabecera_id = ord_cabecera.id ";
        $cond = "usuarios_id = $usuario_id";
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond", "order: numero desc", "limit: 1");
    }

    /**
     * Método para procesar el carro de compras cliente
     * @return type
     */
    public function getProcesaCarroCompras($cliente, $orden) {
        $obj = new OrdCabecera($orden);

        $fecha_actual = date("Y-m-d");
        $usuario_id = empty(Session::get('id')) ? 0 : Session::get('id');
        //descargamos los items del carrito
        $jsonPHP = json_decode(Input::post('publicados'));

        $existe = $obj->getOrdenAbierta($usuario_id, $fecha_actual, $cliente->id);

        if (!empty($existe->id)) {//busco una orden de compras abierta y agrego mas items al carrito
            if (($existe->fecha_emision == $fecha_actual) && ($existe->estado_orden == OrdEstados::GENERADO)) {
                $orden_num = $existe;
            } else {
                $orden_num = OrdCabecera::setCatalogoCabecera('create', $orden, array('empresas_id' => $cliente->id, 'fecha_emision' => $fecha_actual));
            }
        } else {
            $orden_num = OrdCabecera::setCatalogoCabecera('create', $orden, array('empresas_id' => $cliente->id, 'fecha_emision' => $fecha_actual));
        }

        Logger::debug("********************************ORDEN $orden_num->id*******************+");
        //guardo los items del carrito
        if (!empty($orden_num->id)) {
            $data = array();
            $data['ord_cabecera_id'] = $orden_num->id;
            $rs = OrdDetalle::setRegistroItemsCarrito($data, $jsonPHP);
            if (!empty($rs)) {
                $detalle = (new OrdDetalle)->getListadoDetalleCotizacion($orden_num->id);
                $empresa = (new Empresas)->getInformacionEmpresa(Empresas::EMPRESA);

                $pdf = 0;
                //Llama a crear un PDF
                $pdf = HtmlToPdf::getOrdenPdf($empresa, $orden_num, $detalle, $tipo = 'F');
                if (!empty($pdf)) {
                    Flash::valid('<b>Revise su Correo!, gracias por su confianza.</b>');
                    if (!OrdEstados::setEstadoOrden('procesado', array('ord_cabecera_id' => $orden_num->id, 'motivo' => 'Generado de forma automatica'))) {
                        Flash::error('Se ha producido un error interno al registrar la orden. Por favor intenta nuevamente.');
                        return 'cancel';
                    }
                }
                $resul = $orden_num;
            } else {
                $resul = 0;
            }
            return $resul;
        }
    }

}
