<?php

class Comprobantes extends ActiveRecord {

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
//        $this->has_many('sucursal');        
        $this->validates_presence_of('fecha_emision', 'message: Ingresa la fecha de emision');
    }

    /**
     * Método para setear
     * 
     * @param array $data
     * @return
     */
    public static function setComprobantes($method, $data, $optData = null) {
        $obj = new Comprobantes($data); //Se carga los datos con los de las tablas        
        if ($optData) { //Se carga información adicional al objeto
            $obj->dump_result_self($optData);
        }
        $tablasTipos = new TablasTipos();
        if ($obj->fecha_emision) {
            $obj->fecha_emision = DwOnline::checkFechaDocumentoElectronico($obj->fecha_emision);
        }
        //genero el numero de documento
        if ($method == 'create') {
            $tipoComprobantes = $tablasTipos->getInformacionTablasTipos($obj->tipo_comprobante);
            $numeroDocumento = $obj->getNumeroSecuencialDocumento($tipoComprobantes->valida);
            $codigo = empty($numeroDocumento->numero_secuencial) ? '000000000' : $numeroDocumento->numero_secuencial;
            $nuevo = DwOnline::setNumeroDocumento($codigo);
            $obj->numero_secuencial = $nuevo;
            $obj->crea_documento = 'create';
        }

//        $tipoComprobante = $tablasTipos->getInformacionTablasTipos($obj->tipo_comprobante);
        $tipoAmbiente = $tablasTipos->getInformacionTablasTipos($obj->tipo_ambiente);
        $tipoEmision = $tablasTipos->getInformacionTablasTipos($obj->tipo_emision);
        //010320180105026587680011001001000000001000000351

        $empresa = new Empresas();
        $infoEmpresa = $empresa->getInformacionEmpresa($type = Empresas::EMPRESA);

        if (empty($obj->clave_acceso)) {
            $clave_acceso = $obj->fecha_emision . $tipoComprobantes->valida . $infoEmpresa->ruc . $tipoAmbiente->valida . $obj->serie . $obj->numero_secuencial . $obj->codigo_numerico . $tipoEmision->valida;
            $obj->tipo_comprobante = $tipoComprobantes->valida;
            $obj->tipo_ambiente = $tipoAmbiente->valida;
            $obj->tipo_emision = $tipoEmision->valida;
            $digito_verificador = DwFirma::cadenaDeVerificacion($clave_acceso);
            $obj->clave_acceso = $clave_acceso . $digito_verificador;
        }

        //Verifico que no exista otra Comprobantes, y si se encuentra inactivo lo active
        $cond = empty($obj->id) ? "serie='$obj->serie' AND numero_secuencial='$obj->numer_secuencial'" : "serie='$obj->serie' AND numero_secuencial='$obj->numer_secuencial' AND id != '$obj->id'";
        $old = new Comprobantes();
        if ($old->find_first($cond)) {
            if ($method == 'create') {
//                $obj->id = $old->id;
//                $method = 'update';
            } else {
                Flash::info('Ya existe un comprobante registrado bajo esos parámetros.');
                return FALSE;
            }
        }
        return ($obj->$method()) ? $obj : FALSE;
    }

    /**
     * Callback que se ejecuta después de guardar/crear
     */
    public function after_save() {
        if (!empty($this->id) && $this->crea_documento == 'create') {
            $documentos = (new Comprobantes);
            $data = $documentos->getInformacionComprobante($this->factura_cabecera_id, $this->tipo_comprobante);
            if ($data->tipo_comprobante == '01') {
                DwFirma::Factura($data);
            } else if ($data->tipo_comprobante == '04') {
                DwFirma::notaCredito($data);
            } else if ($data->tipo_comprobante == '05') {
                DwFirma::notaDebito($data);
            } else if ($data->tipo_comprobante == '06') {
                DwFirma::guiaRemision($data);
            } else if ($data->tipo_comprobante == '07') {
                DwFirma::comprobanteRetencion($data);
            }
        }
    }

    /**
     * Método que devuelve los comprobantes paginados o para un select
     * @param int $pag Número de página a mostrar.
     * @return ActiveRecord
     */
    public function getListadoComprobantes($estado = 'todos', $order = '', $page = 0) {
        $colm = "comprobantes.*, (factura_cabecera.tipo_comprobante) AS comprobante_tipo ";
        $join = 'INNER JOIN factura_cabecera ON factura_cabecera.id = comprobantes.factura_cabecera_id ';
        $cond = 'comprobantes.fecha_emision IS NOT NULL';
        $order = $this->get_order($order, 'fecha_emision');
        if ($page) {
            return $this->paginated("columns: $colm", "join: $join", "conditions: $cond", "order: $order", "page: $page");
        }
        return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
    }

    /**
     * Método que devuelve la informacion del comprobante
     * @param int $comprobante_id Número de página a mostrar.
     * @return ActiveRecord
     */
    public function getInformacionComprobante($comprobante_id, $tipo_comprobante = '') {
        $colm = "comprobantes.*, (factura_cabecera.tipo_comprobante) AS comprobante_tipo, factura_cabecera.documento_modifica ";
        $join = 'LEFT JOIN factura_cabecera ON factura_cabecera.id = comprobantes.factura_cabecera_id ';
        if ($tipo_comprobante != '') {
            $cond = "comprobantes.factura_cabecera_id = $comprobante_id and comprobantes.tipo_comprobante = $tipo_comprobante";
        } else {
            $cond = "comprobantes.factura_cabecera_id = $comprobante_id";
        }
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond");
    }

    /**
     * Método para buscar ciudades
     */
    public function getAjaxComprobantes($field, $value, $order = '', $page = 0) {
        $value = Filter::get($value, 'string');
        if (strlen($value) <= 1 OR ( $value == 'none')) {
            return NULL;
        }
        $columns = "comprobantes.*";
        $cond = "comprobantes.activo != 'NULL' ";

        $order = $this->get_order($order, 'id');
        //Defino los campos habilitados para la búsqueda
        $fields = array('fecha_emision', 'ruc');
        if (!in_array($field, $fields)) {
            $field = 'fecha_emision';
        }
        if (!($value == 'todas')) {
            $cond .= " AND $field LIKE '%$value%'";
        }
        if ($page) {
            return $this->paginated("columns: $columns", "conditions: $cond", "order: $order", "page: $page");
        } else {
            return $this->find("columns: $columns", "conditions: $cond", "order: $order");
        }
    }

    /**
     * Método para obtener la información del numero de documento
     * @return type
     */
    function getNumeroSecuencialDocumento($movimiento) {
        $colu = "comprobantes.id, comprobantes.tipo_comprobante, comprobantes.numero_secuencial";
        $cond = "comprobantes.tipo_comprobante = '$movimiento' ";
        return $this->find_first("columns: $colu", "conditions: $cond", "order: id desc");
    }

}
