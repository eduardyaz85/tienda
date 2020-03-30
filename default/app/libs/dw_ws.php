<?php

/**
 * Clase que se utiliza para guardar el catalogo en json
 * de la carpeta public/files/productos.json
 *
 * @category    Sistema
 * @package     Libs
 */
Load::models('sistema/usuarios');

class DwWs {

    /**
     * Método que se utiliza para escribir un .json
     * @param type $source
     */
    public static function writeWsCatalogo() {
        ini_set('max_execution_time', 620);
        $url = DwConect::peticionURL(); //pedir url
        $respuestaJSON = DwConect::getSslPage($url);

        if (!empty($respuestaJSON)) {
            $archivo = 'files/productos.json';

            $fc = fopen($archivo, 'w+');
            fwrite($fc, $respuestaJSON);
            fclose($fc);
            if (file_exists($archivo)) {
                Flash::valid("Catalogo sincronizado");
                Logger::debug('ws ok: -- ' . date('Y-m-d'));
                //archivo creado
                return TRUE;
            } else {
                Flash::error("Ops. ha ocurrido un error");
                //error el archivo no ha sido creado
                return FALSE;
            }
        }
    }

    /**
     * Método que se utiliza para escribir el catalogo en .json
     * @param type $source
     */
    public static function writeCatalogoServicio() {
        ini_set('max_execution_time', 320);

        if (empty($path)) {
            $path = APP_PATH . 'temp/ws/catalogo.txt';
        }

        @chmod($path, 0744);
        if (!is_writable($path)) {
            Flash::error('Error: No se pudo actualizar el catalogo.');
            return false;
        }

        $hoy = (date('Y-m-d'));
        $catalogo = (new CatMaster)->getCatalogoJsonWs($ws = 1);

        if (!empty($catalogo)) {
            $fc = fopen($path, 'w+');
            fwrite($fc, $catalogo);
            fclose($fc);
            if (file_exists($path)) {
                //archivo creado
                return TRUE;
            } else {
                Flash::error("Ops. ha ocurrido un error al cargar el catalogo");
                //error el archivo no ha sido creado
                return FALSE;
            }
        }
    }

}
