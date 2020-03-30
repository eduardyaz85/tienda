<?php

/**
 * Controller por defecto si no se usa el routes
 *
 */
Load::models('sistema/usuarios', 'utils/html_to_pdf');

class OrdenController extends AppController {

    public $page_title = 'Store Asdin';

    /**
     * Método que se ejecuta antes de cualquier acción
     */
    protected function before_filter() {
        //Se cambia el nombre del módulo actual
        $this->modulo = Router::get('controller_path');
        if (Input::isAjax()) {
            View::select(NULL, NULL);
        }
    }

    /**
     * Página principal ordenes
     */
    public function index() {
        if (DwAuth::isLogged()) {
            if (Session::get('perfil_id') <= Perfil::SUPERVISOR) {
                return Redirect::to("catalogo/orden/revision/");
            }
        }

        $this->titulo = 'Mi Carro';
        $this->page_title = 'Resumen';
    }

    /**
     * Método para pedir datos del cliente
     */
    public function factura() {
//        DwPayphone::getVenta();
        if (Input::hasPost('cuenta') && Input::hasPost('mode') && Input::hasPost('publicados')) {
            ActiveRecord::beginTrans();
            //verificamos que los datos sean correctos
            if (!empty(Input::hasPost('cuenta'))) {
                $rs = (new CatCliente)->getOrdenCliente(Input::post('cuenta'));
                if (!empty($rs->id)) {
                    ActiveRecord::commitTrans();
                    Input::delete('empresa');
                    Flash::valid("Se ha generado correctamente su Orden!<br><script>$(function () {return  paypal.minicart.reset()});</script>");
                    Flash::info("Gracias por su visita!<br><script>$(function () {return  actualizaCarrito();});</script>");
                    $key_shw = Security::setKey($rs->id, 'shw_orden');
                    Redirect::toAction("resumen/$key_shw");
                } else {
                    ActiveRecord::rollbackTrans();
                    Flash::error("Ha ocurrido un error el generar su orden!");
                }
            }
        }
        $this->cuenta = Input::post('cuenta');
    }

    /**
     * Método para pedir datos del cliente
     */
    public function cotizacion() {
        if (Input::hasPost('cuenta') && Input::hasPost('mode') && Input::hasPost('publicados')) {
            ActiveRecord::beginTrans();
            //verificamos que los datos sean correctos
            if (!empty(Input::hasPost('cuenta'))) {
                $rs = (new CatCliente)->getOrdenCliente(Input::post('cuenta'));
                if (!empty($rs->id)) {
                    ActiveRecord::commitTrans();
                    Input::delete('empresa');
                    Flash::valid("Se ha generado correctamente su Orden!<br><script>$(function () {return  paypal.minicart.reset()});</script>");
                    Flash::info("Gracias por su visita!<br><script>$(function () {return  actualizaCarrito();});</script>");
                    $key_shw = Security::setKey($rs->id, 'shw_orden');
                    Redirect::toAction("resumen/$key_shw");
                } else {
                    ActiveRecord::rollbackTrans();
                    Flash::error("Ha ocurrido un error el generar su orden!");
                }
            }
        }
        $this->cuenta = Input::post('cuenta');
    }

    /**
     * Método para ver el resumen de la compra
     */
    public function resumen($key) {
        if (!$id = Security::getKey($key, 'shw_orden', 'int')) {
            return Redirect::toAction('index');
        }

        $orden = new OrdCabecera();
        if (!$orden->getInformacionOrden($id)) {
            Flash::error('Lo sentimos, no se pudo establecer la información de la orden');
            return Redirect::to('index/');
        }
        $this->detalle = (new OrdDetalle)->getListadoDetalleCotizacion($orden->id);
        $this->empresa = (new Empresas)->getInformacionEmpresa(Empresas::EMPRESA);
        $this->orden = $orden;
    }

}
