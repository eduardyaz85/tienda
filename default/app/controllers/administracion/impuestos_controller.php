<?php

class ImpuestosController extends BackendController {

    public $page_title = 'Impuestos';

    /**
     * Método que se ejecuta antes de cualquier acción
     */
    protected function before_filter() {
        //Setear nombre del módulo actual
        $this->modulo = Router::get('controller_path');
        //Se cambia el nombre del módulo actual
        $this->page_module = 'Gestión de Impuestos';
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

        $this->impuestos = (new TablasTipos)->getListaTablasActivas('imp');

        $this->order = $order;
        $this->page_title = 'Listado';
    }

    /**
     * Método para agregar
     */
    public function agregar() {
        ActiveRecord::beginTrans();
        if (Input::hasPost('tablas')) {
            if (TablasTipos::setTablasTipos('create', Input::post('tablas'), array('estado' => TablasTipos::ACTIVO))) {
                ActiveRecord::commitTrans();
                Flash::valid('La tabla se ha creado correctamente.');
                return Redirect::toAction('listar');
            }
        }

        $this->tabla = (new Tablas)->find_first("abreviatura = 'imp'");
        $this->page_title = 'Agregar';
    }

    /**
     * Método para editar
     */
    public function editar($key) {
        if (!$id = Security::getKey($key, 'upd_impuesto', 'int')) {
            return Redirect::toAction('listar');
        }
        ActiveRecord::beginTrans();

        $tabla = $this->tablas = (new TablasTipos)->find_first($id);
        if (!$tabla) {
            Flash::error('No se ha encontrado registros del impuesto');
            return Redirect::toAction('listar');
        }

        if (Input::hasPost('tablas')) {
            if ($rs = TablasTipos::setTablasTipos('update', Input::post('tablas'), array('id' => $id))) {
                ActiveRecord::commitTrans();
                $key = Security::setKey($rs->id, 'shw_impuesto');
                Flash::valid('El impuesto se ha actualizado correctamente!');
                return Redirect::toAction("ver/$key/");
            }
        }

        $this->tabla = (new Tablas)->find_first("abreviatura = 'imp'");
        View::select("agregar");
        $this->page_title = 'Actualizar';
    }

    /**
     * Método para ver
     */
    public function ver($key, $order = 'order.codigo_impuesto.asc', $page = 'page.1') {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;
        if (!$id = Security::getKey($key, 'shw_impuesto', 'int')) {
            return Redirect::toAction('listar');
        }

        $tablasTipo = new TablasTipos();
        if (!$tablasTipo->find_first($id)) {
            Flash::error('Lo sentimos, no se pudo establecer la información de la tabla');
            return Redirect::toAction('listar');
        }

        $this->impuestos = (new Impuestos())->getListadoImpuestos($tablasTipo->id, 'todos', $order, $page);

        $this->tablas = $tablasTipo;
        $this->order = $order;
        $this->page_title = 'Información';
        $this->key = $key;
    }

    /**
     * Método para añadir items de la tabla
     */
    public function crear($key = '') {
        if (!$id = Security::getKey($key, 'add_item', 'int')) {
            return Redirect::toAction('listar');
        }

        $tabla = $this->tabla = (new TablasTipos)->find_first($id);
        if (!$tabla) {
            Flash::error('No se ha encontrado registros de la tabla Impuesto');
            return Redirect::toAction('listar');
        }

        if (Input::hasPost('impuesto')) {
            ActiveRecord::beginTrans();
            //Guardo
            if ($rs = Impuestos::setImpuesto('create', Input::post('impuesto'))) {
                ActiveRecord::commitTrans();
                $key = Security::setKey($rs->impuesto_id, 'shw_impuesto');
                Flash::valid('El Impuesto se ha creado correctamente!');
                return Redirect::toAction("ver/$key/");
            }
            ActiveRecord::rollbackTrans();
        }

        $this->page_title = 'Contenido Tabla';
    }

    /**
     * Método para editar
     */
    public function modificar($key) {
        if (!$id = Security::getKey($key, 'mod_item', 'int')) {
            return Redirect::toAction('listar');
        }

        $impuesto = $this->impuesto = (new Impuestos)->getInformacionImpuesto($id);
        if (!$impuesto) {
            Flash::error('No se ha encontrado registros del impuesto');
            return Redirect::toAction('listar');
        }

        ActiveRecord::beginTrans();
        if (Input::hasPost('impuesto')) {
            if ($rs = Impuestos::setImpuesto('update', Input::post('impuesto'), array('id' => $impuesto->id))) {
                ActiveRecord::commitTrans();
                $key = Security::setKey($rs->impuesto_id, 'shw_impuesto');
                Flash::valid('El Impuesto se ha actualizado correctamente!');
                return Redirect::toAction("ver/$key/");
            }
        }
        $this->tabla = (new TablasTipos)->find_first($impuesto->impuesto_id);

        View::select("crear");
        $this->page_title = 'Actualizar Contenido';
    }

    /**
     * Método para inactivar/reactivar
     */
    public function estado($tipo, $key) {
        if (!$id = Security::getKey($key, $tipo . '_item', 'int')) {
            return Redirect::toAction('listar');
        }

        $impuesto = new Impuestos();
        if (!$impuesto->find_first($id)) {
            Flash::error('Lo sentimos, pero no se ha podido establecer la información del impuesto');
        } else {
            if ($tipo == 'inactivar' && $impuesto->estado == Impuestos::INACTIVO) {
                Flash::info('El impuesto ya se encuentra inactivo');
            } else if ($tipo == 'reactivar' && $impuesto->estado == Impuestos::ACTIVO) {
                Flash::info('El impuesto ya se encuentra estado');
            } else {
                $estado = ($tipo == 'inactivar') ? Impuestos::INACTIVO : Impuestos::ACTIVO;
                if (Impuestos::setImpuesto('update', $impuesto->to_array(), array('id' => $id, 'estado' => $estado))) {
                    ($estado == Impuestos::ACTIVO) ? Flash::valid('El impuesto se ha reactivado correctamente!') : Flash::valid('El impuesto se ha inactivado correctamente!');
                }
            }
        }
        $key_shw = Security::setKey($impuesto->impuesto_id, 'shw_impuesto');
        return Redirect::toAction("ver/$key_shw/");
    }

    /**
     * Método para eliminar
     */
    public function eliminar($key) {
        if (!$id = Security::getKey($key, 'eliminar_item', 'int')) {
            return Redirect::toAction('listar');
        }

        $impuesto = new Impuestos();
        if (!$impuesto->find_first($id)) {
            Flash::error('Lo sentimos, pero no se ha podido establecer la información del impuesto');
            return Redirect::toAction('listar');
        }
        $impuesto->estado = '';
        try {
            if ($impuesto->update()) {
                Flash::valid('El impuesto se ha eliminado correctamente!');
            } else {
                Flash::warning('Lo sentimos, pero este impuesto no se puede eliminar.');
            }
        } catch (KumbiaException $e) {
            Flash::error('Esta impuesto no se puede eliminar porque se encuentra relacionado con otro registro.');
        }

        $key_shw = Security::setKey($impuesto->impuesto_id, 'shw_impuesto');
        return Redirect::toAction("ver/$key_shw/");
    }

}
