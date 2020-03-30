<?php

/**
 *
 * Descripcion: Controlador que se encarga del logueo de los usuarios del sistema
 *
 * @category    
 * @package     Controllers 
 * @author      argordmel 
 */
Load::lib('security');

class LoginController extends BackendController {

    /**
     * Limite de parámetros por acción
     * @var boolean
     */
    public $limit_params = FALSE;

    /**
     * Nombre de la página
     * @var string
     */
    public $page_title = 'Entrar';

    /**
     * Método que se ejecuta antes de cualquier acción
     */
    protected function before_filter() {
        View::template('frontend/login');
        if (isset($_SERVER['HTTP_REFERER'])) {
            $this->referente = $_SERVER['HTTP_REFERER'];
        }
    }

    /**
     * Método principal     
     */
    public function index() {
        return Redirect::toAction('entrar/');
    }

    /**
     * Método para iniciar sesión
     */
    public function entrar() {
        if (Input::hasPost('login') && Input::hasPost('password') && Input::hasPost('mode')) {
            if (Usuarios::setSessionCliente('open', Input::post('login'), Input::post('password'))) {
                if (Session::get('perfil_id') <= Perfil::ADMIN) {
                    return Redirect::to('dashboard/');
                } else {
                    return Redirect::to('index/');
                }
            } else {
                //Se soluciona lo de la llave de seguridad
                Flash::error('Ops! Ha ocurrido un error intenta nuevamente');
                return Redirect::toAction('entrar/');
            }
        } else if (DwAuth::isLogged()) {
            return Redirect::to('index/');
        }
    }

    /**
     * Método para cerrar sesión
     * @param string $js Indica si está deshabilitado el js en el navegador o no
     * @return type
     */
    public function salir($js = '') {
        if (Usuarios::setSession('close')) {
            Flash::valid("Regresa pronto, vive una experiencia diferente de compra!.");
        }
        if (!empty($js)) {
            Flash::info('Activa el uso de JavaScript en su navegador para poder continuar.');
        }
        return Redirect::to('index/');
    }

}
