<?php

/**
 * Controller por defecto si no se usa el routes
 * 
 */
ini_set('max_execution_time', 620);
ini_set('max_allowed_packet', 240);
Load::lib('http_conection');

class SadController extends AppController {

    public $page_title = 'Centro de Ayuda!';

    public function faq() {
//        View::template('frontend/productos');
        $this->titulo = 'Centro de Ayuda!';
    }

    public function index() {
        DwWs::writeCatalogoServicio();
        return DwRedirect::to('index');
    }

    public function ws() {
        $ws = DwWs::writeWsCatalogo();
        sleep(1);
        if (!empty($ws)) {
            //Guardamos el catalogo
            (new CatWs())->pideCatalogoProveedor(2);
            /*            $proveedor = (new Empresas)->find_by_id($id = 2);
              if ($proveedor->nombres == 'INTCOMEX') {
              (new CatWs())->pideCatalogoProveedor($proveedor->id);
              } */
        }
        return DwRedirect::to('index');
    }

}
