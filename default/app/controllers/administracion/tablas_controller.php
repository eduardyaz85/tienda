<?php

class TablasController extends BackendController {

    public $page_title = 'Tablas';

    /**
     * Método que se ejecuta antes de cualquier acción
     */
    protected function before_filter() {
        //Setear nombre del módulo actual
        $this->modulo = Router::get('controller_path');
        //Se cambia el nombre del módulo actual
        $this->page_module = 'Gestión de Tablas';
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
    public function listar($order = 'order.detalle.asc', $page = 'pag.1') {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;

        $this->tablas = (new Tablas)->getListadoTablas('todos', $order, $page);

        $this->order = $order;
        $this->page_title = 'Listado';
    }

    /**
     * Método para agregar
     */
    public function agregar() {
        ActiveRecord::beginTrans();
        if (Input::hasPost('tablas')) {
            if (Tablas::setTablas('create', Input::post('tablas'), array('activo' => Tablas::ACTIVO))) {
                ActiveRecord::commitTrans();
                Flash::valid('La tabla se ha creado correctamente.');
                return Redirect::toAction('listar');
            }
        }
        $this->page_title = 'Agregar';
    }

    /**
     * Método para añadir items de la tabla
     */
    public function crear($key = '') {
        if (!$id = Security::getKey($key, 'add_item', 'int')) {
            return Redirect::toAction('listar');
        }

        $tabla = $this->tabla = (new Tablas)->find_first($id);
        if (!$tabla) {
            Flash::error('No se ha encontrado registros de la tabla');
            return Redirect::toAction('listar');
        }

        if (Input::hasPost('tablas')) {
            ActiveRecord::beginTrans();
            //Guardo la tabla
            if ($rs = TablasTipos::setTablasTipos('create', Input::post('tablas'))) {
                ActiveRecord::commitTrans();
                $key = Security::setKey($rs->tablas_id, 'shw_tabla');
                Flash::valid('El contenido se ha creado correctamente!');
                return Redirect::toAction("ver/$key/");
            }
            ActiveRecord::rollbackTrans();
        }

        $this->cabecera = (new Tablas)->getInformacionTabla($id);

        $this->page_title = 'Contenido Tabla';
    }

    /**
     * Método para editar
     */
    public function editar($key) {
        if (!$id = Security::getKey($key, 'upd_tabla', 'int')) {
            return Redirect::toAction('listar');
        }
        ActiveRecord::beginTrans();

        $tabla = $this->tablas = (new Tablas)->find_first($id);
        if (!$tabla) {
            Flash::error('No se ha encontrado registros de la tabla');
            return Redirect::toAction('listar');
        }

        if (Input::hasPost('tablas')) {
            if (Tablas::setTablas('update', Input::post('tablas'), array('id' => $id))) {
                ActiveRecord::commitTrans();
                Flash::valid('La tabla se ha actualizado correctamente!');
                return Redirect::toAction('listar');
            }
        }

        $this->page_title = 'Actualizar';
    }

    /**
     * Método para editar
     */
    public function modificar($key) {
        if (!$id = Security::getKey($key, 'mod_item', 'int')) {
            return Redirect::toAction('listar');
        }
        ActiveRecord::beginTrans();

        $tabla = $this->tablas = (new TablasTipos)->getInformacionTablasTipos($id);
        if (!$tabla) {
            Flash::error('No se ha encontrado registros del contenido');
            return Redirect::toAction('listar');
        }

        if (Input::hasPost('tablas')) {
            if ($rs = TablasTipos::setTablasTipos('update', Input::post('tablas'), array('id' => $id))) {
                ActiveRecord::commitTrans();
                $key = Security::setKey($rs->tablas_id, 'shw_tabla');
                Flash::valid('El contenido se ha actualizado correctamente!');
                return Redirect::toAction("ver/$key/");
            }
        }

        $this->page_title = 'Actualizar Contenido';
    }

    /**
     * Método para ver
     */
    public function ver($key, $order = 'order.orden.asc', $page = 'page.1') {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;
        if (!$id = Security::getKey($key, 'shw_tabla', 'int')) {
            return Redirect::toAction('listar');
        }

        $tabla = new Tablas();
        if (!$tabla->find_first($id)) {
            Flash::error('Lo sentimos, no se pudo establecer la información de la tabla');
            return Redirect::toAction('listar');
        }

        $tablastip = new TablasTipos();
        $this->tbldetalle = $tablastip->getListadoTablasTipos($tabla->abreviatura);

        $this->tablas = $tabla;
        $this->order = $order;
        $this->page_title = 'Información';
        $this->key = $key;
    }

}
