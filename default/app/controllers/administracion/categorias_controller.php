<?php

class CategoriasController extends BackendController {

    public $page_title = 'Categorias';

    /**
     * Método que se ejecuta antes de cualquier acción
     */
    protected function before_filter() {
        //Setear nombre del módulo actual
        $this->modulo = Router::get('controller_path');
        //Se cambia el nombre del módulo actual
        $this->page_module = 'Gestión de Categorias';
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
    public function listar($order = 'order.category.asc', $page = 'pag.1') {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;
        $categoria = new Categorias();
        $this->NomCat = $categoria;
        $this->categorias = $categoria->getListadoCategorias('todos', $order, $page);
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

        $categoria = new Categorias();
        $categorias = $categoria->getAjaxCategorias($field, $value, $order, $page);
        if (empty($categorias->items)) {
           Flash::info('No se han encontrado registros de categorias');
        }
        $this->categorias = $categoria;
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
        if (Input::hasPost('categoria')) {
            if (Categorias::setCategorias('create', Input::post('categoria'))) {
                ActiveRecord::commitTrans();
                Flash::valid('La categoria se ha creado correctamente.');
                return Redirect::toAction('listar');
            }
        }
        $this->page_title = 'Agregar';
    }

    /**
     * Método para editar
     */
    public function editar($key) {
        if (!$id = Security::getKey($key, 'upd_categoria', 'int')) {
            return Redirect::toAction('listar');
        }

        $categoria = new Categorias();
        if (!$categoria->find_first($id)) {
            Flash::error('No hay informacion de la categoria solicitada');
            return Redirect::toAction('listar');
        }

        if (Input::hasPost('categoria')) {
            if (Categorias::setCategorias('update', Input::post('categoria'), array('id' => $id))) {
                Flash::valid('La categoria se ha actualizado correctamente!');
                return Redirect::toAction('index');
            }
        }

        $this->categoria = $categoria;
        $this->page_title = 'Actualizar';
    }

}
