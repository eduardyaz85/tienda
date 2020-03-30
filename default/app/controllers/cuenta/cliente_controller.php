<?php

Load::models('sistema/usuarios');

class ClienteController extends AppController {

    public $page_title = 'Cuenta';

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
     * Método modificar datos de la cuenta
     */
    public function editar($key) {
        if (!$id = Security::getKey($key, 'upd_datos', 'int')) {
            return Redirect::to('cuenta/cliente/datos');
        }
        if (DwAuth::isLogged()) {
            $usuario = new Usuarios();
            if (!$usuario->getInformacionUsuario(Session::get('id'))) {
                Flash::info('Lo sentimos pero no se ha podido establecer tu información');
                return Redirect::to('cuenta/cliente/datos');
            }
            if (Input::hasPost('cuenta')) {
                if (Usuarios::setUsuario('update', Input::post('cuenta'), array('id' => Session::get('id'), 'cliente' => 's', 'perfil_id' => Perfil::CLIENTE))) {
                    Flash::valid('Los datos se han actualizado correctamente');
                    return Redirect::to('cuenta/cliente/datos');
                } else {
                    Flash::error('Error al actualizar la cuenta.');
                    return FALSE;
                }
            }

            $this->cuenta = $usuario;
        } else {
            Flash::error('Inicie Sesión para que pueda acceder a su cuenta.');
            return Redirect::to('cuenta/login/entrar/');
        }
        $this->page_title = 'Mi Cuenta';
        $this->titulo = 'Bienvenido ';
    }

    /**
     * Método ver datos de la cuenta
     */
    public function datos() {
        if (DwAuth::isLogged()) {
            $usuario = new Usuarios();
            if (!$usuario->getInformacionUsuario(Session::get('id'))) {
                Flash::info('Lo sentimos pero no se ha podido establecer tu información');
                return Redirect::to('cuenta/cliente/datos');
            }
//            DwOnline::pr($usuario);
//            die();

            $this->usuario = $usuario;
        } else {
            Flash::error('Inicie Sesión para que pueda acceder a su cuenta.');
            return Redirect::to('cuenta/login/entrar/');
        }
        $this->page_title = 'Mi Cuenta';
        $this->titulo = 'Bienvenido ';
    }

    /**
     * Método para subir una foto
     */
    public function foto($key) {
        if (!$id = Security::getKey($key, 'upd_foto', 'int')) {
            return Redirect::to('cuenta/cliente/datos');
        }
        if (DwAuth::isLogged()) {
            ActiveRecord::beginTrans();
            $usuario = new Usuarios();
            if (!$usuario->getInformacionUsuario(Session::get('id'))) {
                Flash::info('Lo sentimos pero no se ha podido establecer tu información');
                return Redirect::to('cuenta/cliente/datos');
            }

            $this->usuario = $usuario;
        } else {
            Flash::error('Inicie Sesión para que pueda acceder a su cuenta.');
            return Redirect::to('cuenta/login/entrar/');
        }
        $this->page_title = 'Subir Imagen';
    }

    /**
     * Método para subir imágenes
     */
    public function uploadimg($key) {
        if (!$id = Security::getKey($key, 'upl_foto', 'int')) {
            return Redirect::to('cuenta/cliente/datos');
        }

        $usuario = new Usuarios();
        if (!$usuario->getInformacionUsuario(Session::get('id'))) {
            Flash::error('Ops ha ocurrido un Error! id no encontrado');
            return Redirect::to('cuenta/cliente/datos');
        }

        if (PRODUCTION) {
            $upload = new DwUpload('fotografia', '../../img/upload/personas/');
        } else {
            $upload = new DwUpload('fotografia', 'img/upload/personas/');
        }

        $upload->setAllowedTypes('png|jpg|gif|jpeg');
        $upload->setEncryptName(TRUE);
        $upload->setSize('10MB', 390, 400, TRUE);
        if (!$data = $upload->save()) { //retorna un array('path'=>'ruta', 'name'=>'nombre.ext');
            $data = array('error' => $upload->getError());
        }

        if (!empty($data)) {
            $usuario->update([
                'id' => $usuario->id,
                'fotografia' => $data['name']
            ]);
        }

        sleep(1); //Por la velocidad del script no permite que se actualize el archivo
        View::json($data);
    }

    /**
     * Método para cargar una lista de deseos
     */
    public function deseos() {
        
    }

    /**
     * Método para cargar la informacion del cliente
     */
    public function getDatos() {
        $result = '';
        if (!empty(Input::post('empresa_id'))) {
            $cliente = new Empresas();
            if (!$cliente->getInformacionEmpresas(Input::post('empresa_id'))) {
                return Redirect::toAction('listar');
            }
            if (empty($cliente->especial) OR empty($cliente->direccion) OR empty($cliente->email) OR empty($cliente->telefono) OR empty($cliente->credito)) {
                $result = $cliente;
                Flash::warning('Favor actualice la informacion del cliente');
            } else {
                $result = 0;
            }
        }
//        DwOnline::pr($result);
//        die();
        $this->empresa = $result;
        View::template(NULL);
    }

}
