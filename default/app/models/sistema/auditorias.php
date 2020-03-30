<?php

class Auditorias extends ActiveRecord {

    //Se desabilita el logger para no llenar el archivo de "basura"
    public $logger = FALSE;
    public $debug = FALSE;

    protected function initialize() {
        //relaciones
        $this->belongs_to('usuario');
    }

    /**
     * Método para listar
     * @return array
     */
    public function getListadoAuditorias($id_usuario, $tipo = 'todos', $order = '', $page = 0) {
        $colm = 'auditorias.*';
        $join = 'INNER JOIN usuarios ON usuarios.id = auditorias.usuarios_id ';
        $cond = "auditorias.usuarios_id = $id_usuario";
        $order = $this->get_order($order, 'tabla_afectada');
        if ($page) {
            return $this->paginated("columns: $colm", "join: $join", "conditions: $cond", "order: $order", "page: $page");
        } else {
            return $this->find("columns: $colm", "join: $join", "conditions: $cond");
        }
    }

    protected function log() {
        //proteccion, para que no se ejecute el log recursivamente :-s
        return NULL;
    }

}
