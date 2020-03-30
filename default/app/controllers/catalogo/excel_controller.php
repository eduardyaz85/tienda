<?php

Load::models('sistema/sistema');

class ExcelController extends BackendController {

    public $page_title = 'Base Temporal Inventario';

    /**
     * Método que se ejecuta antes de cualquier acción
     */
    protected function before_filter() {
        Rest::init();
        Rest::accept('html');
        $this->input = Rest::param();
        $this->modulo = Router::get('controller_path');
        $this->set_title = FALSE;
    }

    /**
     * Método para listar
     */
    public function index() {
        $this->page_title = 'Subir Inventario Excel';
    }

    /**
     * Método para ver el inventario en la tabla catalogo
     */
    public function listar($order = 'order.id.asc', $page = 'pag.1') {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;

        $this->articulos = (new CatTemporal)->getListadoTemporalArticulos('todos', $order, $page);

        $this->order = $order;
        $this->page_title = 'Listado';
    }

    /**
     * Método para buscar
     */
    public function buscar($field = 'fila', $value = 'none', $order = 'order.id.asc', $page = 1) {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;
        $field = (Input::hasPost('field')) ? Input::post('field') : $field;
        $value = (Input::hasPost('field')) ? Input::post('value') : $value;

        $articulos = new CatTemporal();
        $articulo = $articulos->getAjaxTemporalArticulos($field, $value, $order, $page);
        if (empty($articulo->items)) {
            Flash::info('No se han encontrado el registro solicitado');
        }

        $this->articulos = $articulo;
        $this->order = $order;
        $this->field = $field;
        $this->value = $value;
        $this->page_title = 'Búsqueda';
    }

    /**
     * Método para cargar articulos desde un excel de forma masiva
     */
    public function articulos() {
        //obtengo el json enviado desde la vista
        $input = file_get_contents('php://input');
        $result = json_decode($input, true);

        $data = array();
        if (empty($result)) {
            $data['message'] = 'Oops!. No hemos recibido el archivo en excel.';
        } else {
            if ($rs = CatTemporal::setGuardaExcelUpload('create', NULL, $result['inventario'])) {
                ActiveRecord::commitTrans();
                $data['success'] = TRUE;
                $data['message'] = 'Se ha almacenado correctamente el Excel!.';
            } else {
                $data['message'] = Flash::toString();
            }
        }
        View::json($data);
    }

    /**
     * Método para sincronizar el inventario  al kardex
     */
    public function iniciar($key = '') {
        if (!Input::isAjax()) {
            Flash::error('Método incorrecto para sincronizar el Inventario.');
            return Redirect::to('catalogo/excel/listar/');
        }
        if (!$id = Security::getKey($key, 'snc_inventario', 'int')) {
            return View::ajax();
        }
        $pass = Input::post('password');
        $usuario = Usuarios::getUsuarioLogueado();
        if ($usuario->password != sha1($pass) && $usuario->perfil_id == Perfil::ADMIN) {
            Flash::error('Acceso incorrecto al sistema. Tu no tienes los permisos necesarios para realizar esta acción.');
            return View::ajax();
        }

        $articulos = (new CatTemporal)->getListadoTemporalArticulos();
        if ($articulos) {
            $rs = Articulos::setRegistroBaseArticulos($articulos);
            if ($rs) {
                ActiveRecord::commitTrans();
                (new Sistema)->getTruncateTabla('temporal_articulos');
                View::redirect('inventarios/articulos/listar/');
                Flash::valid("Se ha sincronizado correctamente el inventario al Kardex!");
            } else {
                ActiveRecord::rollbackTrans();
                Flash::error("Ha ocurrido un error al guardar el inventario en el Kardex");
            }
        }
        return View::ajax();
    }

    /**
     * Método para validar el inventario antes de sincronizar el inventario al kardex
     */
    public function valida($key = '') {
        if (!$id = Security::getKey($key, 'snc_inventario', 'int')) {
            return Redirect::to('catalogo/excel/listar/');
        }
        $pass = Input::post('password');
        $usuario = Usuarios::getUsuarioLogueado();
        if ($usuario->password != sha1($pass) && $usuario->perfil_id == Perfil::ADMIN) {
            Flash::error('Acceso incorrecto al sistema. Tu no tienes los permisos necesarios para realizar esta acción.');
            return Redirect::to('catalogo/excel/listar/');
        }

        $articulos = (new CatTemporal)->getListadoTemporalArticulos();

        $errores = CatTemporal::setValidacionExcel($articulos);
        if (!empty($errores)) {
            Flash::error("Se ha detectado errores en el excel.. <br>Por Favor modifique y vuelva a intentarlo..");
        } else {
            Flash::valid("El inventario no tiene errores!<br> <b>Proceda con la sincronización al Kardex</b>");
        }
        return Redirect::to('catalogo/excel/listar/');
    }

    /**
     * Método para editar
     */
    public function editar($key) {
        if (!$id = Security::getKey($key, 'upd_articulo', 'int')) {
            return Redirect::to('catalogo/excel/listar/');
        }

        $articulo = new CatTemporal();
        if (!$articulo->find_first($id)) {
            Flash::error('Lo sentimos, pero no se ha podido establecer la información del articulo');
            return Redirect::to('catalogo/excel/listar/');
        }

        ActiveRecord::beginTrans();
        if (Input::hasPost('articulo')) {
            if (CatTemporal::setArticulos('update', Input::post('articulo'), array('id' => $id))) {
                ActiveRecord::commitTrans();
                Flash::valid('El articulo se ha actualizado correctamente!');
                View::redirect('catalogo/excel/listar/');
            }
        }
        $tiposImpuestos = (new TablasTipos)->getListadoTablasTipos(TablasTipos::IMPUESTOS);
        $arrayImpuestos = array();
        $arrayImpuestos[''] = 'SELECCIONE EL IVA';
        foreach ($tiposImpuestos as $key => $value) {
            $arrayImpuestos[$value->titulo] = $value->titulo;
        }
        $this->tiposImpuestos = $arrayImpuestos;
        $this->articulo = $articulo;
        $this->page_title = 'Actualizar';
    }

    /**
     * Método para eliminar
     */
    public function eliminar($key) {
        if (!$id = Security::getKey($key, 'eliminar_articulo', 'int')) {
            return Redirect::to('catalogo/excel/listar/');
        }

        $articulo = new CatTemporal();
        if (!$articulo->find_first($id)) {
            Flash::error('Lo sentimos, pero no se ha podido establecer la información del articulo');
            return Redirect::to('catalogo/excel/listar/');
        }
        try {
            if ($articulo->delete()) {
                Flash::valid('El articulo se ha eliminado correctamente!');
            } else {
                Flash::warning('Lo sentimos, pero este articulo no se puede eliminar.');
            }
        } catch (KumbiaException $e) {
            Flash::error('Este articulo no se puede eliminar porque se encuentra relacionado con otro registro.');
        }

        return Redirect::to('catalogo/excel/listar/');
    }

    /**
     * Método para listar
     */
    public function mails() {
        $this->page_title = 'Subir Correos';
    }

    /**
     * Método para cargar articulos desde un excel de forma masiva
     */
    public function suscriptores() {
        //obtengo el json enviado desde la vista
        $input = file_get_contents('php://input');
        $result = json_decode($input, true);

        $data = array();
        if (empty($result)) {
            $data['message'] = 'Oops!. No hemos recibido el archivo en excel.';
        } else {
            if ($rs = Suscripcion::setGuardaExcelUpload('create', NULL, $result['contactos'])) {
                ActiveRecord::commitTrans();
                $data['success'] = TRUE;
                $data['message'] = 'Se ha almacenado correctamente el Excel!.';
            } else {
                $data['message'] = Flash::toString();
            }
        }
        View::json($data);
    }

}
