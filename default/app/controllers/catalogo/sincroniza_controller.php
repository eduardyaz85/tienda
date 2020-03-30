<?php

Load::lib('http_conection');

class SincronizaController extends BackendController {

    /**
     * Método que se ejecuta antes de cualquier acción
     */
    protected function before_filter() {
        Rest::init();
        Rest::accept('html');
        $this->input = Rest::param();
        $this->set_title = FALSE;
    }

    /**
     * Método para pedir la url de sincronizacion
     */
    public function keyurl($key = '') {
        if (!$id = Security::getKey($key, 'url_catalogo', 'int')) {
            return Redirect::to("catalogo/inventario/producto/listar/");
        }

        //conexion a sincronizar
        $url = DwConect::peticionURL(); //pedir url
        Flash::valid("$url");

        return Redirect::to("catalogo/inventario/producto/listar/");
    }

    /**
     * Método para sincronizar el catalogo del proveedor
     */
    public function iniciar($key = '') {
        if (!$id = Security::getKey($key, 'snc_catalogo', 'int')) {
            return Redirect::to("catalogo/inventario/producto/listar/");
        }

        if (!empty(Input::post('proveedor'))) {
            //Guardamos el catalogo
            $proveedor = (new Empresas())->find_first(Input::post('proveedor'));
            if ($proveedor->nombres == 'INTCOMEX') {
                $rs = (new CatWs())->pideCatalogoProveedor($proveedor->id);
                if ($rs->codigo_error == 'ok') {
                    Flash::valid('El catalogo se ha sincronizado correctamente!.');
                    return Redirect::to("catalogo/inventario/producto/listar/");
                } else {
                    Flash::error('Ops ha ocurrido un Error! no se ha procesado el catalogo');
                    return Redirect::to("catalogo/inventario/producto/listar/");
                }
            }
        }
        return Redirect::to("catalogo/inventario/producto/listar/");
    }

}
