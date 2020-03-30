<?php

/**
 * Asdin - Web | App
 *
 * Descripcion: Modelo encargado de registrar los estados de las ordenes
 *
 * @category    
 * @package     Models 
 * @author      Eduardo Yánez (asdin_shop@yahoo.com)
 * @copyright   Copyright (c) 2019 Asdin
 * @revision    1.0
 */
class OrdEstados extends ActiveRecord {

    //Se desabilita el logger para no llenar el archivo de "basura"
    public $logger = FALSE;

    /**
     * Constante para describir el estado GENERADO
     */
    const GENERADO = 1;

    /**
     * Constante para describir el estado PROCESADO
     */
    const PROCESADO = 2;

    /**
     * Constante para describir el estado APROBADO
     */
    const APROBADO = 3;

    /**
     * Constante para describir el estado FACTURADO
     */
    const FACTURADO = 4;

    /**
     * Constante para describir el estado CANCELADO
     */
    const CANCELADO = 5;

    /**
     * Constante para describir el estado ELIMINADO
     */
    const ELIMINADO = 0;

    /**
     * Método para definir las relaciones y validaciones
     */
    protected function initialize() {
        $this->belongs_to('ord_cabecera');
    }

    /**
     * Método para obtener el estado de una orden
     * @param int $orden
     * @return string
     */
    public function getEstadoOrden($orden_id) {
        $orden_id = Filter::get($orden_id, 'numeric');
        $condicion = "ord_cabecera_id = '$orden_id'";
        $sql = $this->find_first("conditions: $condicion", 'order: id DESC');
        return ($sql) ? $sql : FALSE;
    }

    /**
     * Método para listar todos los estados de las ordenes
     * @param int $orden_id 
     * @param int $pag Número de la página. Si es mayor que 0 se utiliza el paginador
     * @return EstadoOrden
     */
    public function getListadoEstadoOrden($orden_id, $page = 0) {
        $orden_id = Filter::get($orden_id, 'numeric');
        $sql = "SELECT id, estado_orden, motivo, estado_material_at FROM ord_estados WHERE ord_cabecera_id = '$orden_id' ORDER BY id DESC";
        return ($page) ? $this->paginated_by_sql($sql, "page: $page") : $this->find_all_by_sql($sql);
    }

    /**
     * Método para registrar un estado a una orden
     */
    public static function setEstadoOrden($accion, $data, $optData = NULL) {
        $accion = strtolower($accion);
        $obj = new OrdEstados($data);
        if ($optData) {
            $obj->dump_result_self($optData);
        }
//        DwOnline::pr($obj);
//        die();
        //Verifico el estado actual        
        $old = new OrdEstados();
        $actual = $old->getEstadoOrden($obj->ord_cabecera_id);

        //Verifico las acciones
        if ($accion == 'registrar') {
            $obj->estado_orden = self::GENERADO;
        } else if (($accion == 'procesado') && (empty($actual) OR $actual->estado_orden == self::GENERADO )) {
            $obj->estado_orden = self::PROCESADO;
        } else if (($accion == 'aprobado') && ($actual->estado_orden == self::PROCESADO)) {
            $obj->estado_orden = self::APROBADO;
        } else if (($accion == 'facturado') && ($actual->estado_orden == self::APROBADO)) {
            $obj->estado_orden = self::FACTURADO;
        } else if (($accion == 'cancelado') && $actual->estado_orden != self::APROBADO OR $actual->estado_orden != self::FACTURADO) {
            $obj->estado_orden = self::CANCELADO;
        } else {
            return FALSE;
        }
        $obj->usuarios_id = Session::get('id');
//        DwOnline::pr($obj);
//        die();
        return $obj->create();
    }

    /**
     * Callback que se ejecuta antes de crear un registro
     */
    public function before_create() {
        $this->motivo = Filter::get($this->motivo, 'string');
    }

}
