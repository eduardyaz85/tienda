<?php

/**
 * Todas las controladores heredan de esta clase en un nivel superior
 * por lo tanto los metodos aqui definidos estan disponibles para
 * cualquier controlador.
 *
 * @category Kumbia
 * @package Controller
 * */
// @see Controller nuevo controller
require_once CORE_PATH . 'kumbia/controller.php';

class ServicesController extends Controller {

    public function after_filter() {
        View::select(null, null);
        header("Content-type: application/json");
    }

    final protected function initialize() {
        
    }

    final protected function finalize() {
        
    }

}
