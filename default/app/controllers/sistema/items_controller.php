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
     * Método que se devuelve los inputs de acuerdo al tipo de documento
     */
    public function input() {
        if (!Input::isAjax()) {
            return Redirect::to('dashboard');
        }
        $data = Filter::get(Input::post('tipo_id'), 'string');
        if (empty($data)) {
            $this->costo = NULL;
        } else {
            $this->tabla = (new TablasTipos)->find_first($data);
        }
    }

    /**
     * Método que se devuelve los inputs de acuerdo al tipo de documento en la Facturacion
     */
    public function valida() {
        if (!Input::isAjax()) {
            return Redirect::to('dashboard');
        }
        $data = Filter::get(Input::post('tipo_id'), 'string');
        if (empty($data)) {
            $this->costo = NULL;
        } else {
            $this->tabla = (new TablasTipos)->find_first($data);
        }
    }

    protected function after_filter() {
        Flash::clean();
    }

}
