<?php

class MarcasController extends BackendController {

    public $page_title = 'Marcas';

    /**
     * Método que se ejecuta antes de cualquier acción
     */
    protected function before_filter() {
        //Setear nombre del módulo actual
        $this->modulo = Router::get('controller_path');
        //Se cambia el nombre del módulo actual
        $this->page_module = 'Gestión de Marcas';
    }

    /**
     * Método principal
     */
    public function index() {
        Redirect::toAction('listar');
    }

    /**
     * Método para listar
     */
    public function listar($order = 'order.marca.asc', $page = 'pag.1') {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;

        $this->marcas = (new Marca)->getListadoMarcas('todos', $order, $page);

        $this->order = $order;
        $this->page_title = 'Listado';
    }

    /**
     * Método para buscar
     */
    public function buscar($field = 'marca', $value = 'none', $order = 'order.id.asc', $page = 1) {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;
        $field = (Input::hasPost('field')) ? Input::post('field') : $field;
        $value = (Input::hasPost('field')) ? Input::post('value') : $value;

        $marca = new Marca();
        $marcas = $marca->getAjaxMarcas($field, $value, $order, $page);
        if (empty($marcas->items)) {
            Flash::info('No se han encontrado registros de las marcas');
        }
        $this->marcas = $marcas;
        $this->order = $order;
        $this->field = $field;
        $this->value = $value;
        $this->page_title = 'Búsqueda';
    }

    /**
     * Método para agregar
     */
    public function agregar() {
        ActiveRecord::beginTrans();
        if (Input::hasPost('marca')) {
            if (Marca::setMarca('create', Input::post('marca'), array('activo' => Marca::ACTIVO))) {
                ActiveRecord::commitTrans();
                Flash::valid('La Marca se ha creado correctamente.');
                return Redirect::toAction('listar');
            }
        }
        $this->page_title = 'Agregar';
    }

    /**
     * Método para editar
     */
    public function editar($key) {
        if (!$id = Security::getKey($key, 'upd_marca', 'int')) {
            return Redirect::toAction('listar');
        }
        ActiveRecord::beginTrans();

        $marca = $this->marca = (new Marca)->find_first($id);
        if (!$marca) {
            Flash::error('No se ha encontrado registros');
            return Redirect::toAction('listar');
        }

        if (Input::hasPost('marca')) {
            if (Marca::setMarca('update', Input::post('marca'), array('id' => $id))) {
                ActiveRecord::commitTrans();
                Flash::valid('La Marca se ha actualizado correctamente!');
                return Redirect::toAction('listar');
            }
        }

        $this->page_title = 'Actualizar';
    }

    /**
     * Método para subir imágenes
     */
    public function upload() {
        if (PRODUCTION) {
            $upload = new DwUpload('imagen', '../../img/upload/marcas/');
        } else {
            $upload = new DwUpload('imagen', 'img/upload/marcas/');
        }

        $upload->setAllowedTypes('png|jpg|gif|jpeg');
        $upload->setEncryptName(TRUE);
        $upload->setSize('3MB', 300, 300, TRUE);
        if (!$data = $upload->save()) { //retorna un array('path'=>'ruta', 'name'=>'nombre.ext');
            $data = array('error' => TRUE, 'message' => $upload->getError());
        }
        sleep(1); //Por la velocidad del script no permite que se actualize el archivo
        $this->data = $data;
        View::json($data);
    }

}
