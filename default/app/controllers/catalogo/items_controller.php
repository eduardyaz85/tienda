<?php

class ItemsController extends BackendController {

    /**
     * Variable para almacenar la data recibida
     */
    public $input;

    /**
     * Método que se ejecuta antes de cualquier acción
     */
    protected function before_filter() {
        Rest::init();
        Rest::accept('html');
        $this->input = Rest::param();
        $this->set_title = FALSE;
    }

    /**
     * Método para agregar
     */
    public function agregar($key) {
        if (!Input::isAjax()) {
            return DwRedirect::to('catalogo/orden/listar');
        }
        $data = array();
        if (!$id = Security::getKey($key, 'add_items', 'int')) {
            $data['message'] = Flash::toString();
        } else {
            $input = $this->input['detalle'];
            $input['estado'] = OrdDetalle::ACTIVO;

            $rs = OrdDetalle::setOrdenDetalle('create', NULL, $input);
            if ($rs) {
                Flash::clean();
                $data['success'] = TRUE;
                $data['id'] = $rs->id;
                $data['cat_master_id'] = $rs->cat_master_id;
                $data['mpn'] = $rs->mpn;
                $data['descripcion'] = $rs->descripcion;
                $data['cantidad'] = $rs->cantidad;
                $data['precio_compra'] = $rs->precio_compra;
                $data['porcentaje'] = $rs->porcentaje;
                $data['descuento'] = $rs->descuento;
                $data['utilidad'] = $rs->utilidad;
                $data['precio_venta'] = $rs->precio_venta;
                $data['precio_distribuidor'] = $rs->precio_distribuidor;
                $data['valor_total'] = $rs->valor_total;
                $data['key_upd'] = Security::setKey($rs->id, 'upd_item');
            } else {
                $data['message'] = Flash::toString();
            }
        }
        View::json($data);
    }

    /**
     * Método para anular
     */
    public function anular($key) {
        if (!Input::isAjax()) {
            return DwRedirect::to('catalogo/orden/listar');
        }

        $data = array();
        if (!$id = Security::getKey($key, 'del_item', 'int')) {
            $data['message'] = Flash::toString();
        } else {
            $id = $this->input['items'];

            $rs = OrdDetalle::setOrdenDetalle('delete', array('id' => $id));
            if ($rs) {
                Flash::clean();
                $data['success'] = TRUE;
                $data['message'] = 'Articulo eliminado correctamente: ' . $rs->id;
            } else {
                $data['message'] = Flash::toString();
            }
        }
        View::json($data);
    }

    /**
     * Método para editar
     */
    public function editar($key) {
        if (!Input::isAjax()) {
            return DwRedirect::to('catalogo/orden/listar');
        }
        $data = array();
        if (!$id = Security::getKey($key, 'upd_item', 'int')) {
            $data['message'] = DwMessage::toString();
            return View::json($data);
        }

        $ordDetalle = new OrdDetalle();
        if (!$ordDetalle->getInformacionDetalleCotizacion($id)) {
            DwMessage::error('Lo sentimos, pero no se ha podido establecer la información del costo.');
            $data['message'] = Flash::toString();
            return View::json($data);
        }

        if ($this->input) {
            $input = $this->input['detalle'];
            $input['id'] = $id;
            $rs = OrdDetalle::setOrdenDetalle('update', NULL, $input);
            if ($rs) {
                Flash::clean();
                $data['success'] = TRUE;
                $data['id'] = $rs->id;
                $data['cat_master_id'] = $rs->cat_master_id;
                $data['mpn'] = $rs->mpn;
                $data['descripcion'] = $rs->descripcion;
                $data['cantidad'] = $rs->cantidad;
                $data['precio_compra'] = $rs->precio_compra;
                $data['porcentaje'] = $rs->porcentaje;
                $data['descuento'] = $rs->descuento;
                $data['utilidad'] = $rs->utilidad;
                $data['precio_venta'] = $rs->precio_venta;
                $data['precio_distribuidor'] = $rs->precio_distribuidor;
                $data['valor_total'] = $rs->valor_total;
                $data['key_upd'] = Security::setKey($rs->id, 'upd_item');
            } else {
                $data['message'] = Flash::toString();
            }
        } else {
            $data['id'] = $ordDetalle->id;
            $data['cat_master_id'] = $ordDetalle->cat_master_id;
            $data['cantidad'] = $ordDetalle->cantidad;
            $data['precio_compra'] = $ordDetalle->precio_compra;
            $data['porcentaje'] = $ordDetalle->porcentaje;
            $data['utilidad'] = $ordDetalle->utilidad;
            $data['precio_venta'] = $ordDetalle->precio_venta;
            $data['precio_distribuidor'] = $ordDetalle->precio_distribuidor;
            $data['valor_total'] = round((($ordDetalle->precio_venta * $ordDetalle->valor) / 100) + $ordDetalle->precio_venta, 2);
            $data['impuestos_id'] = $ordDetalle->impuestos_id;
            $data['instock'] = $ordDetalle->instock;
            $data['valor'] = $ordDetalle->valor;
        }
        View::json($data);
    }

    protected function after_filter() {
        Flash::clean();
    }

}
