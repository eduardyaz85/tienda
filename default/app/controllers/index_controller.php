<?php

/**
 * Controller por defecto si no se usa el routes
 *
 */
Load::models('sistema/usuarios');

class IndexController extends AppController {

    public $page_title = 'Store Asdin';

    /**
     * Método que se ejecuta antes de cualquier acción
     */
    protected function before_filter() {
        //Se cambia el nombre del módulo actual
        $this->modulo = Router::get('controller_path');
        if (Input::isAjax()) {
            View::select(NULL, NULL);
        }
    }

    /**
     * Página principal tienda
     */
    public function index() {
        try {
            if (!APP_UPDATE) {
                View::template('frontend/home');
            }
            $this->catalogo = (new CatWs)->getCatalogoWs();
            $this->page_title = 'Productos en Stock';
        } catch (KumbiaException $e) {
            View::exception($e);
        }
    }

    /**
     * Método para buscar por categoria
     */
    public function categoria($value = 'none', $order = 'order.descripcion.asc', $page = 1) {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;
        $value = (Input::hasPost('value')) ? Input::post('value') : $value;

        $articulos = (new CatMaster)->getListadoCatalogo('categoria', $value, $order, $page);
        if (empty($articulos->items)) {
            Flash::info('No se han encontrado registros');
        }

        $this->catalogo = $articulos;
        $this->order = $order;
        $this->value = $value;
        $this->page_title = 'Búsqueda Categoria';
        $this->titulo = 'Categorías';
    }

    /**
     * Método para buscar
     */
    public function buscar($value = '', $page = 1) {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;
        $value = (Input::hasPost('value')) ? Input::post('value') : $value;

        if ($value == 'Buscar Producto...') {
            Flash::info('La Accion solicitada es incorrecta!<br>Ingrese un nombre');
            return DwRedirect::to('index');
        }
        return Redirect::toAction("result/$value");
    }

    /**
     * Método para buscar
     */
    public function result($value = '', $page = 1) {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;
        $value = (Input::hasPost('value')) ? Input::post('value') : $value;

        try {
            $articulo = new CatMaster();
            $articulos = $articulo->getBuscaFrontEnd($value, $order = 'order.id.asc', $page);
            if (empty($articulos->items)) {
                Flash::info('No se han encontrado registros');
            }

            $this->catalogo = $articulos;
        } catch (KumbiaException $e) {
            View::exception($e);
        }

        $this->order = $order;
        $this->value = $value;
        $this->titulo = 'Busqueda finalizada por: ' . $value;
    }

    /**
     * Catalogo de productos
     */
    public function producto($acccion = '', $page = 1) {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;
        $acccion = empty($acccion) ? 'catalogo' : Filter::get($acccion, 'string');

        if (empty($acccion)) {
            Flash::info('La Accion solicitada es incorrecta!');
            return DwRedirect::to('index');
        }
        $catalogo = CatWs::getLeeCatalogo($acccion, $page);

        $this->catalogo = $catalogo;
        $this->accion = $acccion;
    }

    /**
     * Gestiona el Detalle del producto
     */
    public function detalle($id, $marca = '') {
        $articulos = new CatMaster();
        if (!$articulos->getArticuloFrontEnd($id, $marca)) {
            Flash::error('Lo sentimos, no se ha podido establecer la información del articulo');
            return Redirect::toAction('index');
        }

        $this->articulo = $articulos;
        $this->catalogo = (new CatWs)->getCatalogoWs();
        $this->page_title = 'Información del Producto';
    }

    /**
     * Método para suscripcion
     */
    public function boletines() {
        ActiveRecord::beginTrans();
        if (Input::hasPost('correo') && Input::hasPost('suscribe')) {
            if ($rs = Suscripcion::setSuscripcion('create', Input::post('suscribe'), array('correo' => Input::post('correo'), 'estatus' => 'a', 'estado' => Suscripcion::ACTIVO))) {
                if (!empty($rs->id)) {
                    ActiveRecord::commitTrans();
                    Flash::valid('Su correo se ha registrado correctamente!');
                    Input::delete('correo');
                    $this->estatus = 1;
                } else {
                    ActiveRecord::rollbackTrans();
                    $this->correo = Input::post('correo');
                    $this->estatus = 0;
                }
            }
        }
    }

    /*
     * ========================================================================
     *  MANEJO ACCESO USUARIOS
     * ======================================================================== 
     */

    /**
     * Método para registro cuenta cliente nuevo
     */
    public function registro() {
        ActiveRecord::beginTrans();
        if (Input::hasPost('cuenta')) {
            ActiveRecord::beginTrans();
            if (Usuarios::setUsuario('create', Input::post('cuenta'), array('cliente' => 's', 'perfil_id' => Perfil::CLIENTE))) {
                ActiveRecord::commitTrans();
                Flash::valid('Su cuenta, se ha creado correctamente.');
                return Redirect::to('cuenta/login/entrar/');
            } else {
                ActiveRecord::rollbackTrans();
            }
        }
        $this->page_title = 'Agregar';
    }

    /**
     * Método para recuperar la contraseña de usuario de cliente
     */
    public function recuperar() {
        if (Input::hasPost('email_or_username')) {
            try {
                $usuario = new Usuarios();
                if ($usuario->resetClaveByEmailOrUsername(Input::post('email_or_username'))) {
                    Flash::valid('<b>Revise su correo para poder recuperar la contraseña.</b>');
                    Input::delete('email_or_username');
                    return Redirect::to('cuenta/login/entrar/');
                }
            } catch (KumbiaException $kex) {
                Flash::error($kex->getMessage());
            }
        }
    }

    /**
     * Método para cambiar la clave del cliente
     */
    public function cambioclave($key, $reset_clave) {
        if (!$id = Security::getKey($key, 'cla_cuenta', 'int')) {
            return Redirect::toAction('index');
        }
        $usuario = new Usuarios();
        if (!$usuario->getInformacionUsuario($id)) {
            Flash::error('Lo sentimos, no se ha podido establecer la información de la cuenta');
            return Redirect::toAction('index');
        }
        if ($usuario->estado_usuario != '1') {
            Flash::error('Lo sentimos, la cuenta se encuentra suspendida');
            return FALSE;
        }

        if ($usuario->reset == $reset_clave) {
            if (Input::post('password') && Input::post('repassword')) {
                $usuario = Usuarios::setUsuario('update', $usuario, array('repassword' => Input::post('repassword'), 'password' => Input::post('password'), 'id' => $usuario->id, 'email' => $usuario->email, 'nombre' => $usuario->nombre, 'apellido' => $usuario->apellido, 'login' => $usuario->login, 'perfil_id' => $usuario->perfil_id, 'nueva_clave' => '1', 'reset' => NULL));
                if ($usuario) {
                    Flash::valid('Se ha cambiado correctamente su clave.');
                    return Redirect::to('cuenta/login/entrar/');
                } else {
                    Flash::error('Oops! Ha ocurrido un error.');
                }
            }
        } else {
            Flash::error('La clave para reseteo es incorrecta o ya fue utilizada.');
            return Redirect::toAction('index');
        }

        $this->titulo = 'Cambiar Contraseña';
        $this->page_title = 'Reseteo Contraseña';
    }

}
