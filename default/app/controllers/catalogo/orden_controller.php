<?php

Load::models('sistema/perfil', 'sistema/usuarios', 'utils/html_to_pdf');

class OrdenController extends BackendController {

    public $page_title = 'Ordenes';

    /**
     * Método que se ejecuta antes de cualquier acción
     */
    protected function before_filter() {
        //Se cambia el nombre del módulo actual
        $this->page_module = 'Gestión de Ordenes';
        $this->modulo = Router::get('controller_path');
    }

    /**
     * Método principal
     */
    public function index() {
        Redirect::to('index');
    }

    /**
     * Método para listar
     */
    public function listar($order = 'order.id.desc', $page = 'pag.1') {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;

        $ordenes = new OrdCabecera();
        $this->listados = $ordenes->getListadoOrdenes('todos', $order, $page);
//        DwOnline::pr($this->listados);
//        die();
        $this->order = $order;
        $this->page_title = 'Lista';
    }

    /**
     * Método para buscar
     */
    public function buscar($value = '', $page = 1) {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;
        $value = (Input::hasPost('value')) ? Input::post('value') : $value;

        try {
            $orden = new OrdCabecera();
            $ordenes = $orden->getAjaxBuscaDocumento($value, $order = 'order.id.asc', $page);
            if (empty($ordenes->items)) {
                Flash::info('No se han encontrado registros');
            }

            $this->listados = $ordenes;
        } catch (KumbiaException $e) {
            View::exception($e);
        }

        $this->order = $order;
        $this->value = $value;
        $this->titulo = 'Busqueda finalizada por: ' . $value;
    }

    /**
     * Método para agregar
     */
    public function agregar() {
        $obj = (new OrdCabecera);
        if (Input::hasPost('orden')) {
            ActiveRecord::beginTrans();
            //Guardo el documento
            if ($rs = OrdCabecera::setCatalogoCabecera('create', Input::post('orden'), array('backend' => '1', 'tipo_orden' => 'c'))) {
                ActiveRecord::commitTrans();
                $key = Security::setKey($rs->id, 'shw_orden');
                Flash::info('Orden Creada Correctamente! <br>Ingrese el detalle');
                return Redirect::toAction("detalle/$key/");
            } else {
                Flash::error('No se ha podido guardar la orden.');
                return FALSE;
            }
            ActiveRecord::rollbackTrans();
        }
        //genero el numero de documento
        $codSis = $obj->getNumeroDocumento('c');
        $codigo = empty($codSis->numero) ? 'COT-00000' : $codSis->numero;
        $this->orden['numero'] = DwOnline::setNumeroSecuencial($codigo);
        $this->page_title = 'Agregar';
    }

    /**
     * Método para editar
     */
    public function editar($key) {
        if (!$id = Security::getKey($key, 'upd_orden', 'int')) {
            return Redirect::toAction('listar');
        }

        $orden = new OrdCabecera();
        if (!$orden->getInformacionOrden($id)) {
            Flash::error('Lo sentimos, no se pudo establecer la información de la orden');
            return Redirect::toAction('listar');
        }

        $key = Security::setKey($orden->id, 'shw_orden');

        if (Input::hasPost('orden')) {
            if (OrdCabecera::setCatalogoCabecera('update', Input::post('orden'), array('id' => $orden->id, 'backend' => '1', 'tipo_orden' => $orden->tipo_orden))) {
                Flash::valid('El cliente se ha actualizado correctamente!');
                return Redirect::toAction("detalle/$key/");
            }
        }
        View::select('agregar');
        $this->orden = $orden;
        $this->page_title = 'Actualizar';
    }

    /**
     * Método para revisar los items del carro
     */
    public function revision() {
        $this->titulo = 'Mi Carro';
        $this->page_title = 'Resumen';
    }

    /**
     * Método para genera una orden de compra con los items del carrito
     */
    public function add() {
        if (DwAuth::isLogged()) {
            $url = Input::post('publicados');
            $jsonPHP = json_decode($url);
            if (!empty($jsonPHP) && Input::post('orden')) {
                //proceso carro de compras backend
                $cliente = (new Empresas)->getGuardaDatosClientes(Input::post('orden'));
                $rs = (new OrdCabecera)->getProcesaCarroCompras($cliente, Input::post('orden'));

                if (!empty($rs->id)) {
                    ActiveRecord::commitTrans();
                    Input::delete('empresa');
                    Flash::valid("Se ha generado correctamente su Orden!<br><script>$(function () {return  paypal.minicart.reset()});</script>");
                    Flash::info("Gracias por su visita!<br><script>$(function () {return  actualizaCarrito();});</script>");
                    $key = Security::setKey($rs->id, 'shw_orden');
                    return Redirect::toAction("detalle/$key/");
                } else {
                    ActiveRecord::rollbackTrans();
                    Flash::error("Ha ocurrido un error el generar su orden!");
                }
            }
        } else {
            Flash::error('Inicie Sesión para que pueda hacer su pedido.');
            Flash::info('Si no tiene cuenta. Por Favor Complete el siguiente formulario....');
        }
        return Redirect::toAction('revision');
    }

    /**
     * Método para ver detalle del carro de compras
     */
    public function detalle($key, $order = 'order.id.asc', $page = 'page.1') {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;
        if (!$id = Security::getKey($key, 'shw_orden', 'int')) {
            return Redirect::toAction('listar');
        }

        if (DwAuth::isLogged()) {
            $orden = new OrdCabecera();
            if (!$orden->getInformacionOrden($id)) {
                Flash::error('Lo sentimos, no se pudo establecer la información de la orden');
                return Redirect::toAction('listar');
            }
            $this->detalle = (new OrdDetalle)->getListadoDetalleCotizacion($orden->id);
            $this->orden = $orden;
        } else {
            Flash::error('Inicie Sesión para que pueda hacer su pedido.');
            Flash::info('Si no tiene cuenta. Por Favor Regístrese....');
        }
        $this->order = $order;
        $this->page_title = 'Información de la Compra';
        $this->titulo = 'Detalle Carrito ORDEN: ';
        $this->key = $key;
    }

    /**
     * Método para editar el item de la orden
     */
    public function modificar($key) {
        if (!$id = Security::getKey($key, 'upd_orden', 'int')) {
            return Redirect::toAction('listar');
        }

        $orden = new OrdCabecera();
        if (!$orden->getInformacionOrden($id)) {
            Flash::error('Lo sentimos, no se pudo establecer la información de la orden');
            return Redirect::toAction('listar');
        }

        $key = Security::setKey($orden->id, 'shw_orden');

        if (Input::hasPost('orden')) {
            if (OrdCabecera::setCatalogoCabecera('update', Input::post('orden'), array('id' => $orden->id, 'backend' => '1', 'tipo_orden' => $orden->tipo_orden))) {
                Flash::valid('El cliente se ha actualizado correctamente!');
                return Redirect::toAction("detalle/$key/");
            }
        }

        $this->orden = $orden;
        $this->page_title = 'Actualizar';
    }

    /**
     * Método para cambiar el estado
     */
    public function estado($tipo, $key) {
        if (!$id = Security::getKey($key, $tipo . '_orden', 'int')) {
            return Redirect::toAction('listar');
        }

        $key = Security::setKey($id, 'shw_orden');

        $orden = new OrdCabecera();
        if (!$orden->getInformacionOrden($id)) {
            Flash::error('Lo sentimos, no se pudo establecer la información de la orden');
        } else {
            //detalle del carrito
            $detalle = (new OrdDetalle)->getListadoDetalleCotizacion($orden->id);
            if ($orden->id <= 0) {
                Flash::warning('Lo sentimos, pero este material no se puede editar.');
                return Redirect::toAction('listar');
            }

            if (empty($detalle)) {
                Flash::warning('Lo sentimos, pero este carro se encuentra vacio.');
                return Redirect::toAction("detalle/$key/");
            }

            if ($tipo == 'procesado' && $orden->estado_orden == OrdEstados::PROCESADO) {
                Flash::info('La orden ya fue finalizada');
            } else {
                if ($tipo == 'procesado') {
                    $empresa = (new Empresas)->getInformacionEmpresa(Empresas::EMPRESA);

                    $pdf = 0;
                    //Llama a crear un PDF
                    $pdf = HtmlToPdf::getOrdenPdf($empresa, $orden, $detalle, $tipo_doc = 'F');
                    if (!empty($pdf)) {
                        Flash::valid('<b>Revise su Correo!, gracias por su confianza.</b>');
                    }
                }
                if ($tipo == 'facturado') {
                    DwOnline::pr('emitir factura');
                    DwOnline::pr($tipo);
                    die();
                }
                if (OrdEstados::setEstadoOrden($tipo, NULL, array('ord_cabecera_id' => $orden->id, 'motivo' => 'Generado de forma manual'))) {
                    Flash::valid('Orden procesad correctamente!');
                    return Redirect::toAction("detalle/$key/");
                }
            }
        }
        return Redirect::toAction("detalle/$key/");
    }

    /**
     * Método para completar la informacion del cliente
     */
    public function getDatosCliente() {
        if (!empty(Input::post('cliente'))) {
            $this->orden = (New Empresas)->getInformacionEmpresas(Input::post('cliente'));
        }
    }

    /**
     * Método para obtener el precio del producto
     */
    public function getPrecioProducto() {
        if (!empty(Input::post('producto'))) {
            $this->detalle = (New CatMaster)->getArticuloBackEnd(Input::post('producto'));
        }
    }

}
