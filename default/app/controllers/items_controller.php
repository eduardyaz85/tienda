<?php

class ItemsController extends AppController {

    /**
     * Variable para almacenar la data recibida
     */
    public $input;

    /**
     * Método que se ejecuta antes de cualquier acción
     */
    protected function before_filter() {
        View::template(NULL);
    }

    /**
     * Método que se devuelve los inputs de acuerdo al tipo de documento
     */
    public function input() {
        if (!Input::isAjax()) {
            return Redirect::to('index');
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
            return Redirect::to('index');
        }
        $data = Filter::get(Input::post('tipo_id'), 'string');
        if (empty($data)) {
            $this->costo = NULL;
        } else {
            $this->tabla = (new TablasTipos)->find_first($data);
        }
    }

    /**
     * Método que se devuelve los inputs de acuerdo al tipo de documento
     */
    public function region() {
        if (!Input::isAjax()) {
            return Redirect::to('index');
        }

        $ciudad_id = Input::post('ciudad_id');
        $data = array();
        if (empty($ciudad_id)) {
            $data['region'] = NULL;
        } else {
            $data['region'] = (new Ciudad)->find_by_id($ciudad_id)->region;
        }
        View::json($data);
    }

    protected function after_filter() {
        Flash::clean();
    }

}
