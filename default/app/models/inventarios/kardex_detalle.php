<?php

class KardexDetalle extends ActiveRecord {

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
     * Constante para definir saldos iniciales
     */
    const SALDO_INICIAL = 'iin';

    /**
     * Constante para registrar las ventas
     */
    const VENTAS = 'ven';

    /**
     * Constante para registrar las ventas
     */
    const DEVOLUCION_VENTAS = 'dev';

    /**
     * Método que se ejecuta antes de inicializar cualquier acción
     */
    public function initialize() {
        
    }

    /**
     * Método que devuelve las facturas paginadas o para un select
     * @param int $pag Número de página a mostrar.
     * @return ActiveRecord
     */
    public function getListadoDetalleKardex($kardex_id, $page = 0) {
        $colm = "kardex_detalle.*, tablas_tipos.titulo, usuario.login ";
        $join = 'INNER JOIN kardex ON kardex.id = kardex_detalle.kardex_id ';
        $join .= 'INNER JOIN tablas_tipos ON tablas_tipos.id = kardex_detalle.concepto_id ';
        $join .= 'INNER JOIN usuario ON usuario.id = kardex_detalle.usuario_registra ';
        $cond = "kardex_detalle.kardex_id = $kardex_id";
        $order = 'creado_at desc';
        if ($page) {
            return $this->paginated("columns: $colm", "join: $join", "conditions: $cond", "order: $order", "page: $page");
        }
        return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
    }

}
