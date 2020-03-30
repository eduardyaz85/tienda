<?php

Load::models('sistema/auditorias', 'sistema/usuarios');

class AuditoriasController extends BackendController {

    /**
     * Método que se ejecuta antes de cualquier acción
     */
    protected function before_filter() {
        //Setear nombre del módulo actual
        $this->modulo = Router::get('controller_path');
        //Se cambia el nombre del módulo actual
        $this->page_module = 'Gestión Auditorias';
    }

    /**
     * Método principal
     */
    public function index() {
        Redirect::toAction('listar');
    }

    /**
     * Método para listar las autitorías del sistema
     * @param type $fecha
     * @return type
     */
    public function listar($order = 'order.id.asc', $page = 'pag.1') {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;
        $fecha = empty($fecha) ? date("Y-m-d") : Filter::get($fecha, 'date');
        try {
            Session::delete('filtro_auditorias_usuario');
            $usr = new Usuarios();
            $this->audits = $usr->getListadoNumeroAcciones('todos', $order, $page);
        } catch (KumbiaException $e) {
            View::exception($e);
        }
        $this->fecha = $fecha;
        $this->order = $order;
        $this->page_title = 'Listado';
    }

    public function usuario($key, $order = 'order.id.acs', $page = 'pag.1') {
        if (!$id = Security::getKey($key, 'shw_auditoria', 'int')) {
            return Redirect::toAction('listar');
        }
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;

        try {
            $this->usuario = (new Usuarios)->find_first($id);
            $this->auditorias = (new Auditorias)->getListadoAuditorias($id, 'todos', $order, $page);

            if (empty($this->auditorias)) {
                Flash::info('Este usuario no ha realizado ninguna acción en el sistema...!!!');
                return Redirect::toAction('listar');
            }
        } catch (KumbiaException $e) {
            View::exception($e);
        }
        $this->keyUsr = $key;
        $this->order = $order;
    }

}
