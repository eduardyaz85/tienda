<?php

/**
 * Controller por defecto si no se usa el routes
 *
 */

class ServicioController extends RestController {

    /**
     * Lista catalogo con stock
     * metodo get catalogo/
     */
    public function getAll() {
        $this->data = (new CatMaster)->getCatalogoJsonWs($ws = 0);
    }

    /**
     * Retorna informacion del articulo con $id 
     * metodo get articulo/:id
     */
    public function get($id) {
        $this->data = (new CatMaster)->find((int) $id);
    }

    /**
     * Crea un nuevo articulo
     * metodo post articulo/
     */
    public function post() {
        $catalogo = new CatMaster();
        if ($catalogo->save($this->param())) {
            $this->setCode(201);
            $this->data = $catalogo;
        } else {
            $this->data = $this->error("error inesperado", 400);
        }
    }

    /**
     * Modifica un articulo por $id
     * metodo put articulo/:id
     */
    public function put($id) {
        $catalogo = new CatMaster();
        $catalogo = $catalogo->find((int) $id);
        if ($catalogo->save($this->param())) {
            $this->setCode(202);
            $this->data = $catalogo;
        } else {
            $this->data = $this->error("error inesperado", 400);
        }
    }

    /**
     * Elimina un articulo por $id
     * metodo delete articulo/:id
     */
    public function delete($id) {
        $catalogo = new CatMaster();
        if ($catalogo->delete((int) $id)) {
            $this->setCode(200);
            $this->data = array();
        } else {
            $this->data = $this->error("error inesperado", 400);
        }
    }

}
