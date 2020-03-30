<?php

class Misc {

    //Se desabilita el logger para no llenar el archivo de "basura"
    public $logger = FALSE;

    /**
     * Genera una clave aleatoria, dado un tamaño.
     *
     * @param int $length
     */
    public static function generarClave($length) {
        $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
        $cad = '';
        for ($i = 0; $i < $length; $i++) {
            $cad .= substr($str, rand(0, 62), 1);
        }
        return $cad;
    }

    /**
     * Genera un hash md5 para intentar identificar el usuario. Capura el navegador,
     * Ip del proxy + la IP, si tiene habilitado cookies...
     */
    public static function fingerPrint() {
        $str = $_SERVER['HTTP_USER_AGENT'];

        if (getenv('HTTP_X_FORWARDED_FOR')) {
            $pipaddress = getenv('HTTP_X_FORWARDED_FOR');
            $ipaddress = getenv('REMOTE_ADDR');
            $str .= $pipaddress . $ipaddress;
        } else {
            $ipaddress = getenv('REMOTE_ADDR');
            $str .= $ipaddress;
        }

        setcookie('test', 1, time() + 3600);

        if (count($_COOKIE) > 0) {
            $str .= "cookie=yes";
        } else {
            $str .= "cookie=no";
        }

        $str .= $_SERVER['HTTP_ACCEPT'] . $_SERVER['HTTP_ACCEPT_LANGUAGE'];

        return md5($str);
    }

    /**
     * Retorna la Ip del proxy si la hay y la IP
     * IpProxy/IP o solo la IP
     * @return type 
     */
    public static function getIp() {
        if (getenv('HTTP_X_FORWARDED_FOR')) {
            $pipaddress = getenv('HTTP_X_FORWARDED_FOR');
            $ipaddress = getenv('REMOTE_ADDR');
            return $pipaddress . '/' . $ipaddress;
        } else {
            $ipaddress = getenv('REMOTE_ADDR');
            return $ipaddress;
        }
    }

    /**
     * Verifica que el email no este en una lista negra.
     */
    public static function isEmailToBan($email) {
        $domain = explode('@', $email);
        $key = 'ca9a190d1e53a1f4b199c21cadc14946';
        $request = 'http://check.block-disposable-email.com/api/json/' . $key . '/' . $domain[1];

        $response = file_get_contents($request);
        $dea = json_decode($response);
        if ($dea->request_status == 'success') {
            if ($dea->domain_status != 'ok') {
                return false;
            }
            return TRUE;
        } else {
            return FALSE;
        }
    }

}
