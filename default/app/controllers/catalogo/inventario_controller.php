<?php

Load::lib('http_conection');

class InventarioController extends BackendController {

    public $page_title = 'Articulos';

    /**
     * Método que se ejecuta antes de cualquier acción
     */
    protected function before_filter() {
        //Se cambia el nombre del módulo actual
        $this->modulo = Router::get('controller_path');
        $this->page_module = 'Gestión Catalogo WEB';
    }

    /**
     * Método para ver el catalogo
     */
    public function index() {
        return Redirect::toAction('producto/listar');
    }

    /**
     * Método principal catalogo backend
     */
    public function producto($pagina = 'listar', $value = 'none', $order = 'order.descripcion.asc', $page = 1) {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;
        $pagina = (Input::hasPost('field')) ? Input::post('field') : $pagina;
        $value = (Input::hasPost('value')) ? Input::post('value') : $value;

        if ($pagina == 'buscar') {
            if (empty($value)) {
                Flash::info('Ingrese un valor a buscar');
            }
        }

        $catalogo = new CatMaster();
        $this->catalogo = $catalogo->getListadoCatalogo($pagina, $value, $order, $page);

        $this->url = "$pagina/$value/$order";
        $this->page_title = 'Listado de Productos ' . $pagina;
    }

    /**
     * Método para agregar
     */
    public function agregar() {
        if (Input::hasPost('articulo')) {
            if (CatMaster::setCatalogoMaster('create', Input::post('articulo'), array('tipo_articulo' => 'm', 'usuario_registro' => Session::get('id'), 'precio_venta' => Input::post('precio_venta'), 'iva_id' => Input::post('iva_id'), 'ice_id' => Input::post('ice_id'), 'irbpnr_id' => Input::post('irbpnr_id'), 'estado' => CatMaster::ACTIVO))) {
                Flash::valid('El articulo se ha registrado correctamente!');
                return Redirect::toAction('producto/listar');
            }
        }
        $this->page_title = 'Agregar articulo';
    }

    /**
     * Método para editar
     */
    public function editar($key) {
        if (!$id = Security::getKey($key, 'upd_articulo', 'int')) {
            return Redirect::toAction('producto/listar');
        }

        $articulo = new CatMaster();
        if (!$articulo->getArticuloBackEnd($id)) {
            Flash::error('Lo sentimos, no se pudo establecer la información del articulo');
            return Redirect::toAction('producto/listar');
        }
//        DwOnline::pr($articulo);
//        die();
        $key_show = Security::setKey($articulo->id, 'show_articulo');

        if (Input::hasPost('articulo')) {
            if (CatMaster::setCatalogoMaster('update', Input::post('articulo'), array('id' => $id, 'codigo' => $articulo->codigo, 'cat_conexion_id' => $articulo->cat_conexion_id, 'type' => $articulo->type, 'tipo_articulo' => $articulo->tipo_articulo, 'usuario_registro' => Session::get('id'), 'precio_venta' => Input::post('precio_venta'), 'iva_id' => Input::post('iva_id'), 'ice_id' => Input::post('ice_id'), 'irbpnr_id' => Input::post('irbpnr_id'), 'estado' => $articulo->estado))) {
                Flash::valid('El articulo se ha actualizado correctamente!');
                return Redirect::toAction("ver/$key_show/");
            }
        }

        $this->articulo = $articulo;
        $this->page_title = 'Actualizar articulo';
    }

    /**
     * Método para ver
     */
    public function ver($key, $order = 'order.articulo.asc', $page = 'page.1') {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;
        if (!$id = Security::getKey($key, 'show_articulo', 'int')) {
            return Redirect::toAction('producto/listar');
        }

        $articulo = new CatMaster();
        if (!$articulo->getArticuloBackEnd($id)) {
            Flash::error('Lo sentimos, no se pudo establecer la información del articulo');
            return Redirect::toAction('producto/listar');
        }

        $this->precios = (new CatSnc)->find("cat_master_id = $articulo->id", "order: id desc");
        $this->articulo = $articulo;
        $this->order = $order;
        $this->page_title = 'Información del articulo';
        $this->key = $key;
    }

    /**
     * Método para listar las conexiones
     */
    public function conexion($order = 'order.descripcion.asc', $page = 'pag.1') {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;

        $this->conexiones = (new CatConexion)->getListaConexiones('todos', $order, $page);

        $this->order = $order;
        $this->page_title = 'Resumen Conexion';
    }

    /*
     * ========================================================================
     *  MANEJO SERVICIOS
     * ======================================================================== 
     */

    /**
     * Método para agregar servicio
     */
    public function crear() {
        $obj = new CatMaster();
        $codigoSistema = $obj->codigoArticuloSistema();
        $codigo = empty($codigoSistema->codigo) ? 'SER-00000' : $codigoSistema->codigo;
        $this->codigo = DwOnline::setNumeroSecuencial($codigo);

        if (Input::hasPost('articulo')) {
            if (CatMaster::setCatalogoMaster('create', Input::post('articulo'), array('tipo_articulo' => 's', 'instock' => 1, 'empresas_id' => '1', 'usuario_registro' => Session::get('id'), 'precio_venta' => Input::post('precio_venta'), 'iva_id' => Input::post('iva_id'), 'estado' => CatMaster::ACTIVO))) {
                Flash::valid('El Servicios se ha registrado correctamente!');
                return Redirect::toAction('producto/listar');
            }
        }
        $this->page_title = 'Agregar';
    }

    /**
     * Método para subir una foto
     */
    public function foto($key) {
        if (!$id = Security::getKey($key, 'upd_foto', 'int')) {
            return Redirect::toAction('producto/listar');
        }
        ActiveRecord::beginTrans();
        $articulo = new CatMaster();
        if (!$articulo->getArticuloBackEnd($id)) {
            Flash::error('Ops ha ocurrido un Error! id no encontrado');
            return Redirect::toAction('producto/listar');
        }

        $this->articulo = $articulo;
        $this->page_title = 'Subir Imagen';
    }

    /**
     * Método para subir imágenes
     */
    public function uploadimg($key) {
        if (!$id = Security::getKey($key, 'upl_foto', 'int')) {
            return Redirect::toAction('producto/listar');
        }

        $articulo = new CatMaster();
        if (!$articulo->getArticuloBackEnd($id)) {
            Flash::error('Ops ha ocurrido un Error! id no encontrado');
            return Redirect::toAction('producto/listar');
        }
        if (PRODUCTION) {
//            $upload = new DwUpload('fotografia', 'img/upload/productos/');
            $upload = new DwUpload('fotografia', '../../img/upload/productos/');
        } else {
            $upload = new DwUpload('fotografia', 'img/upload/productos/');
        }

        $upload->setAllowedTypes('png|jpg|gif|jpeg');
        $upload->setEncryptName(TRUE);
        $upload->setSize('20MB', 500, 490, TRUE);
        if (!$data = $upload->save()) { //retorna un array('path'=>'ruta', 'name'=>'nombre.ext');
            $data = array('error' => $upload->getError());
        }
        $galeria = (new Galeria)->find_first($articulo->id_imagen);

        if (!empty($data) && !empty($galeria->id)) {
            $galeria->imagen = $data['name'];
//            DwOnline::pr($galeria);
//            die();
            if (!$galeria->update()) {
                Flash::error('Ops! no se pudo actualizar la fotografia<br>' . $articulo->mpn);
                return FALSE;
            }
        }

        sleep(1); //Por la velocidad del script no permite que se actualize el archivo
        View::json($data);
    }

    /**
     * Método para cargar fotos
     */
    public function imagen() {
        $fotos = (new Fotos)->find("imagen IS NOT NULL");
        $ols_path = 'img/upload/fotos/';
        $path = 'img/upload/productos/';
        if ($fotos) {
            foreach ($fotos as $row) {
                $this->old = dirname(APP_PATH) . '/public/' . $ols_path . $row->imagen;
                $this->new = dirname(APP_PATH) . '/public/' . $path . $row->imagen;
                $exists = is_file($this->old);
                if ($exists) {
//                DwOnline::pr($row->imagen);
//                die();
                    @chmod("$this->new", 0777); //Permisos
                    if (copy("$this->old", "$this->new")) {
                        unlink("$this->old");
                    }
                    $galeria = (new Galeria);
                    $galeria->empresas_id = $row->empresas_id;
                    $galeria->mpn = $row->mpn;
                    $galeria->cuerpo = $row->cuerpo;
                    $galeria->imagen = $row->imagen;
                    $galeria->url_foto = NULL;
                    if (!$galeria->create()) {
                        Logger::debug('no se crea imagen: ' . $row->imagen . ' ID ' . $row->id);
                    }
                }
            }
            DwOnline::pr('creado correctamente');
            die();
        }
        return DwRedirect::toAction('producto/listar');
    }

    /**
     * Método para publicar en el index
     */
    public function publica() {
        $data = explode('-', Input::post('data'));
        $id = $data[0];
        $valor = ($data[1] == 'true') ? 1 : 0;

        $articulo = (new CatMaster)->find_first($id);
        if (!empty($articulo->id)) {
            $articulo->frontend = $valor;
            if (!$articulo->update()) {
                Flash::error('No se puede publicar el articulo');
            }
        }
        View::json();
    }

}
