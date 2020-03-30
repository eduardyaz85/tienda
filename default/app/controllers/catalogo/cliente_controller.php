<?php

class ClienteController extends BackendController {

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
    public function agregar() {
        if (!Input::isAjax()) {
            return DwRedirect::to('catalogo/orden/agregar');
        }
        $data = array();
        $input = $this->input['empresa'];
        $input['type'] = Empresas::CLIENTE;

        $rs = Empresas::setEmpresas('create', NULL, $input);
        if ($rs) {
            Flash::clean();
            $data['success'] = TRUE;
            $data['empresas_id'] = $rs->id;
            $data['tipo_documento'] = $rs->tipo_documento;
            $data['ruc'] = $rs->ruc;
            $data['nombres'] = $rs->nombres;
            $data['razon_social'] = $rs->razon_social;
            $data['email'] = $rs->email;
            $data['telefono'] = $rs->telefono;
            $data['celular'] = $rs->celular;
            $data['direccion'] = $rs->direccion;
        } else {
            $data['message'] = Flash::toString();
        }
        View::json($data);
    }

    protected function after_filter() {
        Flash::clean();
    }

}
