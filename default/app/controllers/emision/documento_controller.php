<?php

Load::models('empresas', 'establecimientos', 'emision/factura_cabecera', 'emision/factura_detalle', 'emision/factura_pagos', 'sistema/usuarios', 'sistema/sistema', 'emision/retencion', 'emision/retencion_ventas', 'emision/gestion_documentos');

class DocumentoController extends BackendController {

    /**
     * Método que se ejecuta antes de cualquier acción
     */
    protected function before_filter() {
        //Setear nombre del módulo actual
        $this->modulo = Router::get('controller_path');
        //Se cambia el nombre del módulo actual
        $this->page_module = 'Facturación';
    }

    /**
     * Método principal
     */
    public function index() {
        Redirect::toAction('listar');
    }

    /**
     * Método para listar
     */
    public function listar($order = 'order.numero.desc', $page = 'pag.1') {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;

        $this->facturas = (new FacturaCabecera)->getListadoFacturas('todos', $order, $page);
        $this->detallefac = (new FacturaDetalle);
//        $this->tbltipo = (new TablasTipos);
        $this->order = $order;
        $this->page_title = 'Listado';
    }

    /**
     * Método para buscar
     */
    public function buscar($field = 'numero', $value = 'none', $order = 'order.id.asc', $page = 1) {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;
        $field = (Input::hasPost('field')) ? Input::post('field') : $field;
        $value = (Input::hasPost('field')) ? Input::post('value') : $value;

        $factura = new FacturaCabecera();
        $facturas = $factura->getAjaxBuscaDocumento($field, $value, $order, $page);
        if (empty($facturas->items)) {
            Flash::info('No se han encontrado registros del documento solicitado');
        }
        $this->detallefac = (new FacturaDetalle);
        $this->tbltipo = (new TablasTipos);
        $this->facturas = $facturas;
        $this->order = $order;
        $this->field = $field;
        $this->value = $value;
        $this->page_title = 'Búsqueda';
    }

    /**
     * Método para emitir Facturas
     */
    public function documento() {
        if (Input::hasPost('documentos')) {
            if ((Input::post('modifica') == '1')) {
                if (Personas::setCliente('update', Input::post('cliente'), array('estado' => Personas::COD_ACTIVO))) {
                    Flash::valid('El cliente se ha actualizado correctamente.');
                }
            }

            ActiveRecord::beginTrans();
            //Guardo el documento
            if ($rs = FacturaCabecera::setCabeceraFactura('create', Input::post('documentos'), array('estado' => FacturaCabecera::INACTIVO, 'cliente' => Input::post('cliente_factura'), 'usuario_id' => Session::get('id'), 'tipo_comprobante' => TablasTipos::FACTURA))) {
                ActiveRecord::commitTrans();
                $key = Security::setKey($rs->id, 'det_factura');
                Flash::info('Factura Creada correctamente!<br> Ingrese el detalle');
                return Redirect::toAction("detalle/$key/");
            } else {
                $sistema = new Sistema();
                $sistema->restablecerIndiceTabla('factura_cabecera');
                Flash::error('No se ha podido guardar la factura.');
                return FALSE;
            }
            ActiveRecord::rollbackTrans();
        }

        $articulos = new Articulos();
        $this->articulos = $articulos->getListadoToJson('todos');
        $this->numero = (new FacturaCabecera())->getNumeroDocumento('10');
        $this->page_title = 'Factura Nueva';
    }

    /**
     * Método para editar
     */
    public function editar($key) {
        if (!$id = Security::getKey($key, 'upd_factura', 'int')) {
            return Redirect::toAction('listar');
        }

        $factura = new FacturaCabecera();
        if (!$factura->getInformacionFactura($id)) {
            Flash::error('Lo sentimos, no se pudo establecer la información del documento');
            return Redirect::toAction('listar');
        }

        ActiveRecord::beginTrans();
        if (Input::hasPost('factura')) {
            if (FacturaCabecera::setCabeceraFactura('update', Input::post('factura'), array('modifica' => Session::get('id')))) {
                ActiveRecord::commitTrans();
                Flash::valid('El documento se ha actualizado correctamente.');
                $key = Security::setKey($id, 'shw_factura');
                return Redirect::toAction("ver/$key/");
            }
            ActiveRecord::rollbackTrans();
        }

        $this->factura = $factura;
        $this->page_title = 'Actualizar';
    }

    /**
     * Método para ver
     */
    public function ver($key, $page = 'page.1') {
        if (!$id = Security::getKey($key, 'shw_factura', 'int')) {
            return Redirect::toAction('listar');
        }

        $factura = new FacturaCabecera();
        if (!$factura->getInformacionFactura($id)) {
            Flash::error('No se pudo verificar la informacion del documento');
            return Redirect::toAction('listar');
        }

        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;

        $estado = $this->detallefac = (new FacturaDetalle);
        $this->detalle_articulos = $estado->getListadoDetalleFactura($factura->id, $page);

        $this->key = $key;
        $this->factura = $factura;
        $this->page_title = 'Información';
    }

    /**
     * Método para ver el detalle de la factura
     */
    public function detalle($key, $page = 'page.1') {
        if (!$id = Security::getKey($key, 'det_factura', 'int')) {
            return Redirect::toAction('listar');
        }

        $factura = new FacturaCabecera();
        if (!$factura->getInformacionFactura($id)) {
            Flash::error('No se pudo verificar la informacion del documento');
            return Redirect::toAction('listar');
        }
        $this->detalle_retencion = '';
        $retencion = new Retencion();
        if ($ret = $retencion->getInfoRetencionVentas($id)) {
            $this->detalle_retencion = $ret;
        }

        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;

        $estado = new FacturaDetalle();
        $this->detalle_articulos = $estado->getListadoDetalleFactura($factura->id, $page);
        //obtengo el valor del iva
        $this->iva = (new Utilidad)->find_first("tipo = 'i'");
        $this->key = $key;
        $this->factura = $factura;
        $this->page_title = 'GENERA FACTURA';
    }

}
