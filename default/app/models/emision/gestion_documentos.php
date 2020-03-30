<?php

class GestionDocumentos extends ActiveRecord {

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
     * Constante para definir un recurso como en proceso
     */
    const EN_PROCESO = 2;

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
    public static function setGestionComprobantes($method, $data, $optData = null) {
        $obj = new GestionDocumentos($data); //Se carga los datos con los de las tablas        
        if ($optData) { //Se carga información adicional al objeto
            $obj->dump_result_self($optData);
        }
       
        return ($obj->$method()) ? $obj : FALSE;
    }

    /**
     * Callback que se ejecuta después de guardar/crear
     */
    public function after_save() {
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
    public function getInformacionGestionComprobante($comprobante_id, $tipo_comprobante = '') {
        $colm = "gestion_documentos.* ";
        $join = 'INNER JOIN factura_cabecera ON factura_cabecera.id = gestion_documentos.id_documento ';
        if ($tipo_comprobante != '') {
            $cond = "gestion_documentos.id_documento = $comprobante_id and gestion_documentos.tipo_comprobante = $tipo_comprobante ";
        } else {
            $cond = "gestion_documentos.id_documento = $comprobante_id ";
        }
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond", "order: id desc");
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
