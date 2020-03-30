<?php

/**
 * Asdin - Web | App
 *
 * Descripcion: Modelo encargado de registrar contenido de tablas pequeñas
 *
 * @category    
 * @package     Models 
 * @author      Eduardo Yánez (asdin_shop@yahoo.com)
 * @copyright   Copyright (c) 2019 Asdin
 * @revision    1.0
 */
class TablasTipos extends ActiveRecord {

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
     * Constante para cargar campos de la tabla TIPO DOCUMENTO
     */
    const TIPO_CLIENTE = 'TCL';

    /**
     * Constante para cargar campos MOVIMIENTOS EN KARDEX
     */
    const MOVIMIENTO_INVENTARIO = 'mvi';

    /**
     * Constante para cargar campos de la tabla TIPO DOCUMENTO
     */
    const TIPO_IDENTIFICACION = 'tid';

    /**
     * Constante para identificar una Factura
     */
    const FACTURA = 'fac';

    /**
     * Constante para identificar una Nota de Credito
     */
    const NOTA_CREDITO = 'ncr';

    /**
     * Constante para identificar una Nota de Debito
     */
    const NOTA_DEBITO = 'ndb';

    /**
     * Constante para identificar una Guia de Remision
     */
    const GUIA_REMISION = 'gre';

    /**
     * Constante para identificar un Comprobante de Retencion
     */
    const RETENCIONES = 'crt';

    /**
     * Constante para cargar tipos de articulos
     */
    const TIPO_ARTICULO = 'tar';

    /**
     * Constante para cargar campos de la tabla FORMA DE PAGO
     */
    const METODO_PAGO = 'mpa';

    /**
     * Constante para cargar campos de la tabla FORMA DE PAGO
     */
    const FORMA_PAGO = 'fdp';

    /**
     * Constante para cargar campos de la tabla CARGOS
     */
    const CARGOS = 'crg';

    /**
     * Constante para cargar campos de la tabla tipo de impuestos
     */
    const IMPUESTOS = 'imi';

    /**
     * Constante para cargar campos de la tabla tipo de impuestos
     */
    const IMPUESTOS_RETENCION_IVA = 'riv';

    /**
     * Constante para cargar campos de la tabla tipo de impuestos
     */
    const IMPUESTOS_RETENCION_RENTA = 'ren';

    /**
     * Constante para cargar campos de la tabla tipo de ambiente (FACTURA ELECTRONICA)
     */
    const TIPO_AMBIENTE = 'tam';

    /**
     * Constante para cargar campos de la tabla tipo de ambiente (FACTURA ELECTRONICA)
     */
    const FIRMA_DIGITAL = 'tfi';

    /**
     * Constante para cargar campos de la tabla tipo de emision (FACTURA ELECTRONICA)
     */
    const TIPO_EMISION = 'tem';

    /**
     * Constante para cargar campos de la tabla tipo de comprobante (FACTURA ELECTRONICA)
     */
    const TIPO_COMPROBANTE = 'tco';

    /**
     * Constante para cargar campos de la tabla METODOS DE VALORACION
     */
    const METODO = 'mva';

    /**
     * Método que se ejecuta antes de inicializar cualquier acción
     */
    public function initialize() {
        $this->has_many('tablas');
        $this->validates_presence_of('titulo', 'message: Ingresa el nombre del titulo');
    }

    /**
     * Método que devuelve una lista de acuerdo a la tabla solicida en el parametro
     * @return ActiveRecord
     */
    public function getListadoTablasTipos($parametro = '', $codigo = '') {
        $colm = 'tablas_tipos.*, tablas.abreviatura ';
        $join = "INNER JOIN tablas ON tablas.id = tablas_tipos.tablas_id ";
        if ($codigo) {
            $cond = "tablas.abreviatura = '" . $parametro . "' AND tablas_tipos.codigo = '" . $codigo . "' AND tablas_tipos.activo IS NOT NULL";
        } else {
            $cond = "tablas.abreviatura = '" . $parametro . "' AND tablas_tipos.activo = 1";
        }
        $order = 'orden';
        return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
    }

    /**
     * Método que devuelve una lista de campos activos de las tablas
     * @return ActiveRecord
     */
    public function getListaTablasActivas($parametro = '', $codigo = '') {
        $colm = 'tablas_tipos.*, tablas.abreviatura ';
        $join = "INNER JOIN tablas ON tablas.id = tablas_tipos.tablas_id ";
        $cond = "tablas.abreviatura = '" . $parametro . "' AND tablas_tipos.activo = " . TablasTipos::ACTIVO;
        $order = 'orden';
        return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
    }

    /**
     * Método que devuelve una lista de acuerdo a la tabla solicida en el parametro
     * @return ActiveRecord
     */
    public function getInformacionTablasTipos($parametro = '') {
        $colm = 'tablas_tipos.*, tablas.abreviatura, tablas.codigo as codigo_tablas ';
        $join = "INNER JOIN tablas ON tablas.id = tablas_tipos.tablas_id ";
        $cond = "tablas_tipos.id = $parametro";
        $order = 'codigo';
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
    }

    /**
     * Método que devuelve una lista de acuerdo al parametro
     * @return ActiveRecord
     */
    public function getTablasTipos($parametro = '', $codigo = '') {
        $colm = 'tablas_tipos.*, tablas.abreviatura ';
        $join = "INNER JOIN tablas ON tablas.id = tablas_tipos.tablas_id ";
        if ($codigo) {
            $cond = "tablas.abreviatura = '" . $parametro . "' AND tablas_tipos.codigo = '" . $codigo . "' AND tablas_tipos.activo = 1";
        } else {
            $cond = "tablas.abreviatura = '" . $parametro . "' AND tablas_tipos.activo = 1";
        }
        $order = 'codigo';
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
    }

    /**
     * Método para setear
     * 
     * @param array $data
     * @return
     */
    public static function setTablasTipos($method, $data, $optData = null) {
        $obj = new TablasTipos($data); //Se carga los datos con los de las tablas        
        if ($optData) { //Se carga información adicional al objeto
            $obj->dump_result_self($optData);
        }
        $obj->titulo = strtoupper($obj->titulo);
        $obj->detalle = strtoupper($obj->detalle);
        $obj->codigo = strtolower($obj->codigo);
        //Verifico que no exista otro tipo de tabla, y si se encuentra inactivo lo active
        $conditions = empty($obj->id) ? "titulo='$obj->titulo' AND tablas_id='$obj->tablas_id'" : "titulo='$obj->titulo' AND tablas_id='$obj->tablas_id' AND id != '$obj->id'";
        $old = new TablasTipos();
        if ($old->find_first($conditions)) {
            if ($method == 'create') {
                $obj->id = $old->id;
                $method = 'update';
            } else {
                Flash::info('Ya existe una tabla tipo registrada bajo esos parámetros.');
                return FALSE;
            }
        }

        $rs = $obj->$method();
        if ($rs) {
            ($method == 'create') ? DwAudit::debug("Se ha registrado el campo $obj->titulo, codigo $obj->codigo") : DwAudit::debug("Se ha modificado el campo $obj->titulo, codigo $obj->codigo");
        }
        return ($rs) ? $obj : FALSE;
    }

    /**
     * Callback que se ejecuta antes de guardar/modificar
     */
    public function before_save() {
        $this->codigo = strtoupper($this->codigo);
        $this->titulo = filter_var(strtoupper($this->titulo), FILTER_SANITIZE_STRING);
        $this->detalle = filter_var(strtoupper($this->detalle), FILTER_SANITIZE_STRING);
    }

    /**
     * Método para obtener los nombres de la tabla tipo
     */
    public function getNombreTabla($tipo_envio) {
        $columns = 'tablas_tipos.id, tablas_tipos.titulo ';
        $conditions = "tablas_tipos.id = $tipo_envio ";
        return $this->find_first("columns: $columns", "conditions: $conditions");
    }

    /**
     * Método para obtener el ID de la tabla tipo
     */
    public function getIdTabla($tipo_envio) {
        $columns = 'tablas_tipos.id, tablas_tipos.titulo ';
        $conditions = "tablas_tipos.titulo = '$tipo_envio' ";
        return $this->find_first("columns: $columns", "conditions: $conditions");
    }

}
