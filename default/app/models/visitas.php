<?php

class Visitas extends ActiveRecord {

    //Se desabilita el logger para no llenar el archivo de "basura"
    public $logger = FALSE;

    /**
     * Método que se ejecuta antes de inicializar cualquier acción
     */
    public function initialize() {
        
    }

    /**
     * Método para registrar un acceso
     * @param string $url Visitante
     * @param int $usuario Usuario que accede
     * @param string $ip  Dirección ip
     */
    public static function setVisitas() {
        $obj = new Visitas();
        $obj->visitante = DwUtils::getIp();
        if (isset($_SERVER['HTTP_REFERER'])) {
            $ip = $obj->referente = $_SERVER['HTTP_REFERER'];
        }
        if (!empty(Session::get('login'))) {
            $obj->session = Session::get('login');
        } else {
            $obj->session = 'visitante';
        }

        $details = json_decode(file_get_contents("http://ipinfo.io/{$obj->visitante}"));

        $obj->navegador = DwOnline::getBrowser($_SERVER['HTTP_USER_AGENT']);
        $obj->sistema = DwOnline::getPlatform($_SERVER['HTTP_USER_AGENT']);
        $obj->pagina = DwUtils::setUrl();
        $obj->hostname = $details->hostname;
        $obj->country = $details->country;
        $obj->city = $details->city;
        $obj->region = $details->region;
        $obj->loc = $details->loc;
        $obj->create();
    }

}
