<?php

Load::models('factura/emision');

class EmpresaController extends BackendController {

    /**
     * Método que se ejecuta antes de cualquier acción
     */
    protected function before_filter() {
        //Setear nombre del módulo actual
        $this->modulo = Router::get('controller_path');
        //Se cambia el nombre del módulo actual
        $this->page_module = 'Configuraciones';
    }

    /**
     * Método principal
     */
    public function listar() {
        Redirect::toAction('index');
    }

    /**
     * Método principal
     */
    public function index() {
        if (Input::hasPost('empresa')) {
            if (Empresas::setEmpresas('save', Input::post('empresa'), array('type' => Empresas::EMPRESA))) {
                Flash::valid('Los datos se han actualizado correctamente');
            } else {
                Flash::error('No se ha podido guardar los cambios de la empresa');
            }
        }

        $empresa = new Empresas();
        if (!$empresa->getInformacionEmpresa(Empresas::EMPRESA)) {
            Flash::error('No se ha podido verificar la informacion de la empresa');
            return Redirect::to('dashboard');
        }

        $sucursales = new Establecimientos();
        $this->establecimientos = $sucursales->getListadoEstablecimientos($empresa->id, 'todos', 'order.sucursal.desc');

        $this->empresa = $empresa;
        $this->page_title = 'Información de la empresa';
    }

    /**
     * Método para añadir establecimientos
     */
    public function crear($key = '') {
        if (!$id = Security::getKey($key, 'add_sucursal', 'int')) {
            return Redirect::toAction('listar');
        }

        $empresa = $this->empresa = (new Empresas)->find_first($id);
        if (!$empresa) {
            Flash::error('No se ha encontrado registros de la tabla');
            return Redirect::toAction('listar');
        }

        if (Input::hasPost('sucursal')) {
            ActiveRecord::beginTrans();
            //Guardo el establecimiento
            if (Establecimientos::setEstablecimientos('create', Input::post('sucursal'), array('estado' => Establecimientos::ACTIVO))) {
                ActiveRecord::commitTrans();
                Flash::valid('El Establecimineto se ha creado correctamente!');
                return Redirect::toAction("index");
            }
            ActiveRecord::rollbackTrans();
        }

        $this->page_title = 'Registra Establecimiento';
    }

    /**
     * Método para editar
     */
    public function modificar($key) {
        if (!$id = Security::getKey($key, 'upd_sucursal', 'int')) {
            return Redirect::toAction('listar');
        }
        ActiveRecord::beginTrans();

        $establecimiento = $this->sucursal = (new Establecimientos)->getInformacionEstablecimiento($id);
        if (!$establecimiento) {
            Flash::error('No se ha encontrado registros del contenido');
            return Redirect::toAction('listar');
        }
        $this->empresa = (new Empresas)->find_first($establecimiento->empresas_id);

        if (Input::hasPost('sucursal')) {
            if (Establecimientos::setEstablecimientos('update', Input::post('sucursal'), array('id' => $establecimiento->id, 'estado' => $establecimiento->estado))) {
                ActiveRecord::commitTrans();
                Flash::valid('El establecimiento se ha actualizado correctamente!');
                return Redirect::toAction("index");
            }
        }

        View::select("crear");
        $this->page_title = 'Actualizar Establecimiento';
    }

    /**
     * Método para ver las cuentas
     */
    public function ver($key) {
        if (!$id = Security::getKey($key, 'shw_sucursal', 'int')) {
            return Redirect::toAction('listar');
        }

        $sucursales = $this->sucursales = (new Establecimientos)->getInformacionEstablecimiento($id);

        if (!$obj = (new Emision())->getListadoEmision($sucursales->id, 'todos', 'order.sucursal.desc')) {
            Flash::error('Ho se encontraron registros');
            return 'cancel';
        }

        $this->tbltipo = (new TablasTipos);
        $this->emision = $obj;
        $this->page_title = 'Ver Puntos de Emision';
    }

    /**
     * Método para añadir un punto de emision
     */
    public function agregar($key = '') {
        if (!$id = Security::getKey($key, 'add_emision', 'int')) {
            return Redirect::toAction('listar');
        }

        $sucursales = $this->sucursales = (new Establecimientos)->find_first($id);
        if (!$sucursales) {
            Flash::error('No se ha encontrado registros del punto de emision');
            return Redirect::toAction('listar');
        }

        if (Input::hasPost('emision')) {
            ActiveRecord::beginTrans();
            //Guardo el punto de emision
            if (Emision::setEmision('create', Input::post('emision'), array('estado' => Emision::ACTIVO))) {
                ActiveRecord::commitTrans();
                Flash::valid('El Punto de Emision se ha creado correctamente!');
                return Redirect::toAction("index");
            }
            ActiveRecord::rollbackTrans();
        }

        $this->page_title = 'Registra Punto Emision';
    }

    /**
     * Método para editar
     */
    public function editar($key) {
        if (!$id = Security::getKey($key, 'upd_emision', 'int')) {
            return Redirect::toAction('listar');
        }

        $emision = $this->emision = (new Emision())->getInformacionEmision($id);
        if (!$emision) {
            Flash::error('No se ha encontrado registros del contenido');
            return Redirect::toAction('listar');
        }
        $this->sucursales = (new Establecimientos)->find_first($emision->establecimientos_id);

        ActiveRecord::beginTrans();
        if (Input::hasPost('emision')) {
            if (Emision::setEmision('update', Input::post('emision'), array('id' => $emision->id, 'estado' => $emision->estado))) {
                ActiveRecord::commitTrans();
                Flash::valid('El punto de emision se ha actualizado correctamente!');
                return Redirect::toAction("index");
            }
        }

        View::select("agregar");
        $this->page_title = 'Actualizar Punto de Emision';
    }

    /**
     * Método para subir imágenes
     */
    public function upload() {
        $upload = new DwUpload('logo', 'img/upload/empresa');
        $upload->setAllowedTypes('png|jpg|gif|jpeg');
        $upload->setEncryptName(TRUE);
        $upload->setSize(200, 50, TRUE);
        if (!$data = $upload->save()) { //retorna un array('path'=>'ruta', 'name'=>'nombre.ext');
            $data = array('error' => $upload->getError());
        }
        sleep(1); //Por la velocidad del script no permite que se actualize el archivo
        View::json($data);
    }

}
