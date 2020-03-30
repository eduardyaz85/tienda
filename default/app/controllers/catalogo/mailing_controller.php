<?php

class MailingController extends BackendController {

    public $page_title = 'Correo';

    /**
     * Método que se ejecuta antes de cualquier acción
     */
    protected function before_filter() {
        //Setear nombre del módulo actual
        $this->modulo = Router::get('controller_path');
        //Se cambia el nombre del módulo actual
        $this->page_module = 'Gestión de Correo';
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
    public function listar($order = 'order.id.asc', $page = 'pag.1') {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;

        $this->correos = (new Suscripcion)->getListadoCorreos('todos', $order, $page);

        $this->order = $order;
        $this->page_title = 'Listado';
    }

    /**
     * Método para buscar
     */
    public function buscar($field = 'marca', $value = 'none', $order = 'order.id.asc', $page = 1) {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;
        $field = (Input::hasPost('field')) ? Input::post('field') : $field;
        $value = (Input::hasPost('field')) ? Input::post('value') : $value;

        $marca = new Marca();
        $marcas = $marca->getAjaxMarcas($field, $value, $order, $page);
        if (empty($marcas->items)) {
            Flash::info('No se han encontrado registros de las marcas');
        }
        $this->marcas = $marcas;
        $this->order = $order;
        $this->field = $field;
        $this->value = $value;
        $this->page_title = 'Búsqueda';
    }

    /**
     * Método para validar
     */
    public function valida() {
//        $correos = (new Suscripcion)->find('estado = 0');
        $correos = (new Suscripcion)->find('estado = 0', 'limit: 2000');
        $obj = (new Suscripcion);
//        DwOnline::pr(count($correos));
//        die();
        foreach ($correos AS $row) {
            $valida = (new Misc)->isEmailToBan($row->correo);
//            $valida = (new Misc)->isEmailToBan('ifo@asdinecom.com');
            if (!empty($valida)) {
                $obj->id = $row->id;
                $obj->nombres = $row->nombres;
                $obj->correo = $row->correo;
                $obj->estatus = $row->estatus;
                $obj->promo = Suscripcion::ACTIVO;
                $obj->nuevo = Suscripcion::ACTIVO;
                $obj->estado = Suscripcion::ACTIVO;
                if (!$obj->update()) {
                    Flash::error('Correo no verificado ' . $row->correo);
                }
//                DwOnline::pr('cambia estado');
//                DwOnline::pr($obj);
//                die();
            }
        }
        Flash::error('Proceso finalizado');
        return Redirect::toAction('listar');
        $this->page_title = 'Validar';
    }

    /**
     * Método para crear boletines
     */
    public function boletines() {
        $correos = (new Suscripcion)->find('estado = 1');
        $obj = (new Suscripcion);
        DwOnline::pr(count($correos));
        die();
        foreach ($correos AS $row) {
            $valida = (new Misc)->isEmailToBan($row->correo);
//            $valida = (new Misc)->isEmailToBan('ifo@asdinecom.com');
            if (!empty($valida)) {
                $obj->id = $row->id;
                $obj->nombres = $row->nombres;
                $obj->correo = $row->correo;
                $obj->estatus = $row->estatus;
                $obj->promo = Suscripcion::ACTIVO;
                $obj->nuevo = Suscripcion::ACTIVO;
                $obj->estado = Suscripcion::ACTIVO;
                if (!$obj->update()) {
                    Flash::error('Correo no verificado ' . $row->correo);
                }
//                DwOnline::pr('cambia estado');
//                DwOnline::pr($obj);
//                die();
            }
        }
        Flash::error('Proceso finalizado');
        return Redirect::toAction('listar');
        $this->page_title = 'Validar';
    }

    /**
     * Método para editar
     */
    public function editar($key) {
        if (!$id = Security::getKey($key, 'upd_marca', 'int')) {
            return Redirect::toAction('listar');
        }
        ActiveRecord::beginTrans();

        $marca = $this->marca = (new Marca)->find_first($id);
        if (!$marca) {
            Flash::error('No se ha encontrado registros');
            return Redirect::toAction('listar');
        }

        if (Input::hasPost('marca')) {
            if (Marca::setMarca('update', Input::post('marca'), array('id' => $id))) {
                ActiveRecord::commitTrans();
                Flash::valid('La Marca se ha actualizado correctamente!');
                return Redirect::toAction('listar');
            }
        }

        $this->page_title = 'Actualizar';
    }

}
