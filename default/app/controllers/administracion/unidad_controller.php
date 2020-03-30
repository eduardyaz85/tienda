<?php

class UnidadController extends BackendController {

    public $page_title = 'Unidad Medida';

    /**
     * Método que se ejecuta antes de cualquier acción
     */
    protected function before_filter() {
        //Setear nombre del módulo actual
        $this->modulo = Router::get('controller_path');
        //Se cambia el nombre del módulo actual
        $this->page_module = 'Gestión Unidades de Medida';
    }

    /**
     * Método principal
     */
    public function index() {
        DwRedirect::toAction('listar');
    }

    /**
     * Método para listar
     */
    public function listar($order = 'order.unidad.asc', $page = 'pag.1') {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;

        $this->umedidas = (new Umedida)->getListadoUnidad('todos', $order, $page);

        $this->order = $order;
        $this->page_title = 'Listado';
    }

    /**
     * Método para agregar
     */
    public function agregar() {
        if (Input::hasPost('unidad')) {
            if (Umedida::setUnidad('create', Input::post('unidad'), array('activo' => Umedida::ACTIVO))) {
                Flash::valid('La unidad de medida se ha creado correctamente.');
                return Redirect::toAction('listar');
            }
        }
        $this->page_title = 'Agregar';
    }

    /**
     * Método para editar
     */
    public function editar($key) {
        if (!$id = Security::getKey($key, 'upd_umedida', 'int')) {
            return Redirect::toAction('listar');
        }

        $unidad = new Umedida();
        if (!$unidad->find_first($id)) {
            Flash::error('Lo sentimos, no se pudo establecer la información de la unidad');
            return Redirect::toAction('listar');
        }

        if (Input::hasPost('unidad')) {
            if (Umedida::setUnidad('update', Input::post('unidad'), array('id' => $id))) {
                Flash::valid('La unidad se ha actualizado correctamente!');
                return Redirect::toAction('listar');
            }
        }

        $this->unidad = $unidad;
        $this->page_title = 'Actualizar';
    }

}
