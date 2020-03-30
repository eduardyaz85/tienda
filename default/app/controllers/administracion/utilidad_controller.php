<?php

/**
 * Descripcion: Controlador que se encarga de la gestión del margen de ganancia
 *
 * @category    
 * @package     Controllers  
 */

class UtilidadController extends BackendController {

    /**
     * Método que se ejecuta antes de cualquier acción
     */
    protected function before_filter() {
        //Setear nombre del módulo actual
        $this->modulo = Router::get('controller_path');
        //Se cambia el nombre del módulo actual
        $this->page_module = 'Utilidad Ventas';
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
    public function listar($order = 'order.tipo.asc', $page = 'page.1') {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;

        $this->utilidad  = (new Utilidad)->getListadoUtilidad('todos', $order, $page);
        $this->order = $order;
        $this->page_title = 'Listado';
    }

    /**
     * Método para agregar
     */
    public function agregar() {
        if (Input::hasPost('utilidad')) {
            if (Utilidad::setUtilidad('create', Input::post('utilidad'))) {
                Flash::valid('La utilidad se ha registrado correctamente!');
                return Redirect::toAction('listar');
            }
        }
        $this->page_title = 'Agregar';
    }

    /**
     * Método para editar
     */
    public function editar($key) {
        if (!$id = Security::getKey($key, 'upd_utilidad', 'int')) {
            return Redirect::toAction('listar');
        }

        $utilidad = new Utilidad();
        if (!$utilidad->find_first($id)) {
            Flash::error('Lo sentimos, no se pudo establecer la información de la utilidad');
            return Redirect::toAction('listar');
        }

        if (Input::hasPost('utilidad')) {
            if (Utilidad::setUtilidad('update', Input::post('utilidad'), array('id' => $id))) {
                Flash::valid('La utilidad se ha actualizado correctamente!');
                return Redirect::toAction('listar');
            }
        }

        $this->utilidad  = $utilidad;
        $this->page_title = 'Actualizar';
    }

}
