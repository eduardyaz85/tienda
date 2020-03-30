<?php

class CiudadesController extends BackendController {

    public $page_title = 'Ciudades';

    /**
     * Método que se ejecuta antes de cualquier acción
     */
    protected function before_filter() {
        //Setear nombre del módulo actual
        $this->modulo = Router::get('controller_path');
        //Se cambia el nombre del módulo actual
        $this->page_module = 'Gestión de Ciudades';
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
    public function listar($order = 'order.ciudad.asc', $page = 'pag.1') {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;

        $this->ciudades = (new Ciudad)->getListadoCiudad('todos', $order, $page);

        $this->order = $order;
        $this->page_title = 'Listado';
    }

    /**
     * Método para buscar
     */
    public function buscar($field = 'ciudad', $value = 'none', $order = 'order.id.asc', $page = 1) {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;
        $field = (Input::hasPost('field')) ? Input::post('field') : $field;
        $value = (Input::hasPost('field')) ? Input::post('value') : $value;

        $ciudad = new Ciudad();
        $ciudades = $ciudad->getAjaxCiudad($field, $value, $order, $page);
        if (empty($ciudades->items)) {
            Flash::info('No se han encontrado registros de ciudades');
        }
        $this->ciudades = $ciudades;
        $this->order = $order;
        $this->field = $field;
        $this->value = $value;
        $this->page_title = 'Búsqueda';
    }

    /**
     * Método para agregar
     */
    public function agregar() {
        ActiveRecord::beginTrans();
        if (Input::hasPost('ciudad')) {
            if (Ciudad::setCiudad('create', Input::post('ciudad'), array('activo' => Ciudad::ACTIVO))) {
                ActiveRecord::commitTrans();
                Flash::valid('La ciudad se ha creado correctamente.');
                return Redirect::toAction('listar');
            }
        }
        $this->page_title = 'Agregar';
    }

    /**
     * Método para editar
     */
    public function editar($key) {
        if (!$id = Security::getKey($key, 'upd_ciudad', 'int')) {
            return Redirect::toAction('listar');
        }
        ActiveRecord::beginTrans();

        $ciudad = $this->ciudad = (new Ciudad)->find_first($id);
        if (!$ciudad) {
            Flash::error('No se ha encontrado registros');
            return Redirect::toAction('listar');
        }

        if (Input::hasPost('ciudad')) {
            if (Ciudad::setCiudad('update', Input::post('ciudad'), array('id' => $id))) {
                ActiveRecord::commitTrans();
                Flash::valid('La ciudad se ha actualizado correctamente!');
                return Redirect::toAction('listar');
            }
        }

        $this->page_title = 'Actualizar';
    }

}
