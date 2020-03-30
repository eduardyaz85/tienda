<?php

/**
 * Asdin - Web | App
 *
 * @category    Librería para el manejo del catalogo externo
 * @package     Libs
 * @copyright   Copyright (c) 2014
 */
class DwConect {

    /**
     * Función peticion url SSL
     */
    static public function getSslPage($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * Función hora de recepcion
     */
    static public function horaPeticionRest() {
        $horaInicial = date('H:i:s');
        $nuevaHora = date("H:i", strtotime($horaInicial) + 18300);
        return $nuevaHora;
    }

    /**
     * Función access key
     */
    static public function peticionURL() {
        $APIKey = Security::claveApiKey();
        $AccessKey = Security::accessApiKey();
        $fecha = date('Y-m-d');
        //$fecha = '2017-08-08';
        $zonaHoraria = 'T';
        $hora = DwConect::horaPeticionRest();
        $zonaLetra = 'Z';
        $timeStamp = $fecha . $zonaHoraria . $hora . $zonaLetra;
        $clave = "$APIKey,$AccessKey,$timeStamp";
        $claveSha = hash('sha256', $clave);
        //desarrollo
        $url = "https://prueba.com/v1/getcatalog?locale=es&apiKey=" . $APIKey . "&utcTimeStamp=" . $timeStamp . "&signature=" . $claveSha;
        return $url;
    }

    /**
     * Función verificar precio y stock
     */
    static public function getProducts($skuList) {
        $APIKey = Security::claveApiKey();
        $AccessKey = Security::accessApiKey();
        $fecha = date('Y-m-d');
        //$fecha = '2017-08-08';
        $zonaHoraria = 'T';
        $hora = DwConect::horaPeticionRest();
        $zonaLetra = 'Z';
        $timeStamp = $fecha . $zonaHoraria . $hora . $zonaLetra;
        $clave = "$APIKey,$AccessKey,$timeStamp";
        $claveSha = hash('sha256', $clave);
        //desarrollo
        $url = "https://prueba.com/v1/getcatalog?locale=es&apiKey=" . $APIKey . "&utcTimeStamp=" . $timeStamp . "&signature=" . $claveSha . "&skusList=" . $skuList;
        return $url;
    }

}
