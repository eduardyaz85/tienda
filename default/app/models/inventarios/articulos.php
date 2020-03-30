<?php

class Articulos extends ActiveRecord {

//Se desabilita el logger para no llenar el archivo de "basura"
    public $logger = FALSE;

    /**
     * Constante para definir un recurso como activo
     */
    const ACTIVO = 1;

    /**
     * Constante para definir un recurso como inactivo
     */
    const INACTIVO = 0;

    /**
     * Método que se ejecuta antes de cualquier acción
     */
    protected function initialize() {
        $this->validates_presence_of('nombre', 'message: Ingresa el nombre de la articulo');
    }

    /**
     * Método para listar los articulos
     * @return array
     */
    public function getListadoArticulos($estado = 'todos', $order = '', $page = 0, $tipo = '') {
        $colm = "articulos.*, categorias.category, marca.marca, umedida.simbolo ";
        $join = 'INNER JOIN categorias ON categorias.id = articulos.categorias_id ';
        $join .= 'INNER JOIN marca ON marca.id = articulos.marca_id ';
        $join .= 'INNER JOIN umedida ON umedida.id = articulos.umedida_id ';
        $cond = "articulos.tipo_articulo = '$tipo' ";
        $order = $this->get_order($order, 'nombre', array('id' => 'articulos.id',
            'nombre' => array(
                'ASC' => 'articulos.nombre ASC, categorias.category DESC',
                'DESC' => 'articulos.nombre DESC, categorias.category DESC')));
        if ($estado != 'todos') {
            $cond .= ($estado == self::ACTIVO) ? " AND activo=" . self::ACTIVO : " AND activo=" . self::INACTIVO;
        }
        if ($page) {
            return $this->paginated("columns: $colm", "join: $join", "conditions: $cond", "order: $order", "page: $page");
        } else {
            return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
        }
    }

    /**
     * Método para ver la informacion del articulo
     * @return array
     */
    public function getInformacionArticulo($articulo_id) {
        $articulo_id = Filter::get($articulo_id, 'int');
        if (!$articulo_id) {
            return NULL;
        }
        $colm = "articulos.*, categorias.category, marca.marca, umedida.simbolo, kardex.minimo, kardex.maximo, (kardex.id) AS id_kardex ";
        $join = 'INNER JOIN categorias ON categorias.id = articulos.categorias_id ';
        $join .= 'INNER JOIN marca ON marca.id = articulos.marca_id ';
        $join .= 'INNER JOIN umedida ON umedida.id = articulos.umedida_id ';
        $join .= 'INNER JOIN kardex ON kardex.articulos_id = articulos.id ';
        $cond = "articulos.id = '$articulo_id' ";
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond");
    }

    /**
     * Método para ver la informacion del servicio
     * @return array
     */
    public function getInformacionServicio($articulo_id) {
        $articulo_id = Filter::get($articulo_id, 'int');
        if (!$articulo_id) {
            return NULL;
        }
        $colm = "articulos.*, categorias.category, marca.marca, umedida.simbolo ";
        $join = 'INNER JOIN categorias ON categorias.id = articulos.categorias_id ';
        $join .= 'INNER JOIN marca ON marca.id = articulos.marca_id ';
        $join .= 'INNER JOIN umedida ON umedida.id = articulos.umedida_id ';
        $cond = "articulos.id = '$articulo_id' ";
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond");
    }

    /**
     * Método para registro de articulos
     */
    public static function setRegistroBaseArticulos($articulos) {
        $obj = (new Articulos);
        $aplica_iva = (new TablasTipos);
        $obj->begin();
        try {
            if (!empty($articulos)) {
                $errores = TemporalArticulos::setValidacionExcel($articulos);
                if (!empty($errores)) {
                    Flash::error("Se ha detectado errores en el excel.. <br>Por Favor modifique y vuelva a intentarlo..");
                    return FALSE;
                }

                //Creo unidad de medida que no exista en la base
                $unidad = (new Umedida);
                foreach ($articulos as $key => $value) {
                    if (!empty($value->unidad)) {
//                        $simboloNew = DwOnline::ganeraSlugExcel(trim($value->unidad));
                        $unidadNew = strtoupper(trim($value->unidad));
                        $unidad_id = $unidad->getInformacionUnidad("$unidadNew");
                        if (empty($unidad_id->unidad) && !empty($unidadNew)) {
                            $data = array();
//                            $data['simbolo'] = $simboloNew;
                            $data['unidad'] = $unidadNew;
                            $data['activo'] = '1';
                            if (!Umedida::setUnidad('create', NULL, $data)) {
                                $obj->rollback();
                                Flash::info('Se ha producido un error al crear la unidad');
                                return FALSE;
                            }
                        }
                    }
                }

                //Creo marcas que no exista en la base
                $marcas = ( new Marca );
                foreach ($articulos as $key => $value) {
                    if (!empty($value->marca)) {
                        $marcaNew = strtoupper(trim($value->marca));
                        $marca = $marcas->getInformacionMarcas("$marcaNew");
                        if (empty($marca->marca) && !empty($marcaNew)) {
                            $brand = DwOnline::ganeraSlugExcel(trim($value->marca));
                            $data = array();
                            $data['brand'] = $brand;
                            $data['marca'] = $marcaNew;
                            $data['activo'] = '1';
                            if (!Marca::setMarca('create', NULL, $data)) {
                                $obj->rollback();
                                Flash::info('Se ha producido un error al crear la marca');
                                return FALSE;
                            }
                        }
                    }
                }

                //Creo categorias principales
                $categoria = ( new Categorias );
                foreach ($articulos as $key => $value) {
                    if (!empty($value->categoria)) {
                        $categoNew = strtoupper(trim($value->categoria));
                        $categor = $categoria->getInformacionCategoria("$categoNew");
                        if (empty($categor->category) && !empty($categoNew)) {
                            $categoSlug = DwOnline::ganeraSlugExcel(trim($value->categoria));
                            $data = array();
                            $data['parent_id'] = '0';
                            $data['slug'] = $categoSlug;
                            $data['category'] = $categoNew;
                            $data['have_childrens'] = '0';
                            $data['url'] = '#';
                            $data['activo'] = '0';
                            if (!empty($categoNew)) {
                                if (!Categorias::setCategorias('create', NULL, $data)) {
                                    $obj->rollback();
                                    Flash::info('Se ha producido un error al crear la categoria');
                                    return FALSE;
                                }
                            }
                        }
                    }
                }

                //Creo sub categorias
                foreach ($articulos as $key => $value) {
                    if (!empty($value->sub_categoria)) {
                        //buscar si existe la categoria
                        $subcate_id = strtoupper(trim($value->sub_categoria));
                        $sub_categoria = $categoria->getInformacionCategoria("$subcate_id");
                        if (empty($sub_categoria->category) && !empty($subcate_id)) {
                            $subcate_slug = DwOnline::ganeraSlugExcel(trim($value->sub_categoria));
                            $categoria_id = $categoria->getInformacionCategoria("$value->categoria");
                            $data = array();
                            $data['parent_id'] = $categoria_id->id;
                            $data['slug'] = $categoria_id->slug . '.' . $subcate_slug;
                            $data['category'] = $subcate_id;
                            $data['have_childrens'] = '1';
                            $data['url'] = $categoria_id->slug . $subcate_slug;
                            $data['activo'] = '1';
                            if (!empty($subcate_id)) {
                                if (!Categorias::setCategorias('create', NULL, $data)) {
                                    $obj->rollback();
                                    Flash::info('Se ha producido un error al crear la sub categoria ' . $value->sub_categoria);
                                    return FALSE;
                                }
                            }
                        }
                    }
                }

                //Registro el articulo
                foreach ($articulos as $key => $value) {
                    if (!empty($value->mpn)) {
                        //buscar si existe el articulo
                        $cod_articulo = strtoupper(trim($value->mpn));
                        $articulo = $obj->informacionArticulo($cod_articulo);
                        if (empty($articulo->mpn) && !empty($cod_articulo)) {
                            $unidad = $unidad->getInformacionUnidad($value->unidad);
                            $marca = $marcas->getInformacionMarcas($value->marca);
                            $categoria = $categoria->getInformacionCategoria($value->sub_categoria);
                            $iva = $aplica_iva->getIdTabla($value->aplica_iva);
                            $data = array();
                            $data['categorias_id'] = $categoria->id;
                            $data['marca_id'] = $marca->id;
                            $data['umedida_id'] = $unidad->id;
                            $data['sku'] = $value->sku;
                            $data['mpn'] = $value->mpn;
                            $data['nombre'] = $value->nombre;
                            $data['detalle'] = $value->detalle;
                            $data['precio_venta'] = $value->precio_venta;
                            $data['aplica_iva'] = $iva->id;
                            $data['aplica_ice'] = $value->aplica_ice;
                            $data['tipo_articulo'] = $value->tipo_articulo;
                            $data['utilidad'] = '';
                            $data['activo'] = '1';
                            //kardex data
                            $kardex = array();
                            $kardex['minimo'] = $value->minimo;
                            $kardex['maximo'] = $value->maximo;
                            $kardex['estado'] = Kardex::ACTIVO;
                            $kardex['usuario_registra'] = Session::get('id');
                            //Guardo el articulo
                            $articulo = Articulos::setArticulos('create', NULL, $data);
                            if ($articulo->tipo_articulo == 'A') {
                                $kardex['articulos_id'] = $articulo->id;
                                //registro el kardex
                                $kardexr = Kardex::setKardex('create', NULL, $kardex);
                                //registro el detalle_kardex
                                if ($kardexr->id) {
                                    DwKardex::kardex($articulo->id, KardexDetalle::SALDO_INICIAL, $value->cantidad, $value->precio_compra);
                                }
                            }
                        } else {
                            $obj->rollback();
                            Flash::info('Se ha producido un error sincronizar el kardex');
                            return FALSE;
                        }
                    }
                }
            }

            $obj->commit();
            return TRUE;
        } catch (KumbiaException $e) {
            Flash::error($e->getMessage());
            $obj->rollback();
            return false;
        }
    }

    /**
     * Método para buscar articulos
     */
    public function getAjaxarticulos($field, $value, $order = '', $page = 0, $tipo = '') {
        $value = Filter::get($value, 'string');
        if (strlen($value) <= 4 OR ( $value == 'none')) {
            return NULL;
        }
        $colm = "articulos.*, categorias.category, marca.marca, umedida.simbolo ";
        $join = 'INNER JOIN categorias ON categorias.id = articulos.categorias_id ';
        $join .= 'INNER JOIN marca ON marca.id = articulos.marca_id ';
        $join .= 'INNER JOIN umedida ON umedida.id = articulos.umedida_id ';
//        $join .= 'LEFT JOIN compras_articulos ON compras_articulos.articulos_id = articulos.id ';
        $cond = "articulos.tipo_articulo = '$tipo' ";

        $order = $this->get_order($order, 'nombre');
        if ($field == 'categoria') {
            $field = 'category';
        }
        if ($field == 'codigo') {
            $field = 'mpn';
        }
//Defino los campos habilitados para la búsqueda
        $fields = array('mpn', 'category', 'marca', 'nombre');
        if (!in_array($field, $fields)) {
            $field = 'nombre';
        }
        if (!($field == 'nombre' && $value == 'todas')) {
            $cond .= " AND $field LIKE '%$value%'";
        }
        if ($page) {
            return $this->paginated("columns: $colm", "join: $join", "conditions: $cond", "order: $order", "page: $page");
        } else {
            return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
        }
    }

    /**
     * Método para crear/modificar un objeto de base de datos
     * 
     * @param string $medthod: create, update
     * @param array $data: Data para autocargar el modelo
     * @param array $optData: Data adicional para autocargar
     * 
     * return object ActiveRecord
     */
    public static function setArticulos($method, $data, $optData = null) {
        $obj = new Articulos($data); //Se carga los datos con los de las tablas        
        if ($optData) { //Se carga información adicional al objeto
            $obj->dump_result_self($optData);
        }
        Flash::error($obj->aplica_iva);
        //genero codigo del articulo
        if ($method == 'create') {
            $codigoSistema = $obj->codigoArticuloSistema();
            $codigo = empty($codigoSistema->codigo) ? '00000000' : $codigoSistema->codigo;
            $nuevo = DwOnline::setNumeroSecuencial($codigo);
            $obj->codigo = $nuevo;
        }
        if ($method != 'delete') {
            if (empty($obj->categorias_id)) {
                $categoria = new Categorias();
                $obj->categorias_id = $categoria->find_first("category = '$obj->categoria'")->id;
            } else {
                $obj->categorias_id;
            }
        }
        $obj->sku = strtoupper(trim($obj->sku));
        $obj->mpn = strtoupper(trim($obj->mpn));
        $obj->nombre = strtoupper(trim($obj->nombre));
        $obj->detalle = strtoupper($obj->detalle);

//calculamos el precio de venta en base a una tabla de % utilidad
        if ($obj->tipo_articulo == 'S' OR ! empty($obj->precio_venta)) {
            $obj->precio_venta = $obj->precio_venta;
        } else {
            $obj->precio_venta = DwOnline::setPrecioUtilidad($obj->precio_compra);
        }

        if (empty($obj->mpn)) {
            $obj->mpn = $obj->sku;
        }

//Verifico que no exista otro articulo, y si se encuentra inactivo lo active
        $conditions = empty($obj->id) ? "marca_id='$obj->marca_id' AND umedida_id='$obj->umedida_id' AND mpn='$obj->mpn' AND nombre='$obj->nombre' AND activo='$obj->activo'" : "marca_id='$obj->marca_id' AND umedida_id='$obj->umedida_id' AND mpn='$obj->mpn' AND nombre='$obj->nombre' AND activo='$obj->activo' AND id != '$obj->id'";
        $old = new Articulos();
        if ($old->find_first($conditions)) {
            if ($method == 'create' && $old->activo != Articulos::ACTIVO) {
                $obj->id = $old->id;
                $obj->activo = Articulos::ACTIVO;
                $method = 'update';
            } else {
                Flash::error('Ya existe un articulo ' . $obj->nombre . '<br> registrado bajo esos parámetros.');
                return FALSE;
            }
        }
        return ($obj->$method()) ? $obj : FALSE;
    }

    /**
     * Callback que se utiliza después de insertar
     */
    protected function after_create() {
        if ($this->id and $this->method == 'create') {
            if ( !empty($this->aplica_iva) ) {
                $impuesto = $impuesto_productos->getInformacionArticulosImpuestos($this->id);
                    ArticulosImpuestos::setArticulosImpuestos('create', null, array('impuesto_id' => $this->aplica_iva, 'articulos_id' => $this->id, 'estado' => ArticulosImpuestos::ACTIVO));
            } 
            if ( !empty($this->aplica_ice) ) {
                ArticulosImpuestos::setArticulosImpuestos('create', null, array('impuesto_id' => $this->aplica_ice, 'articulos_id' => $this->id, 'estado' => ArticulosImpuestos::ACTIVO));
            } 
            if ( !empty($this->aplica_irbpnr) ) {
                ArticulosImpuestos::setArticulosImpuestos('create', null, array('impuesto_id' => $this->aplica_irbpnr, 'articulos_id' => $this->id, 'estado' => ArticulosImpuestos::ACTIVO));
            } 
        }
    }

    /**
     * Callback que se utiliza después de actualizar
     */
    protected function after_update() {
        if ($this->id) {
            if ( !empty($this->aplica_iva) ) {
                ArticulosImpuestos::setArticulosImpuestos('create', null, array('impuesto_id' => $this->aplica_iva, 'articulos_id' => $this->id, 'estado' => ArticulosImpuestos::ACTIVO));
            } 
            if ( !empty($this->aplica_ice) ) {
                ArticulosImpuestos::setArticulosImpuestos('create', null, array('impuesto_id' => $this->aplica_ice, 'articulos_id' => $this->id, 'estado' => ArticulosImpuestos::ACTIVO));
            } 
            if ( !empty($this->aplica_irbpnr) ) {
                ArticulosImpuestos::setArticulosImpuestos('create', null, array('impuesto_id' => $this->aplica_irbpnr, 'articulos_id' => $this->id, 'estado' => ArticulosImpuestos::ACTIVO));
            } 
        }
    }

    /**
     * Método para obtener los precios de los articulos
     */
    public function getPrecioArticuloVenta($producto) {
        $colum = 'articulos.*, marca.marca, kardex_detalle.cantidad ';
        $join = 'INNER JOIN marca ON marca.id = articulos.marca_id ';
        $join .= 'LEFT JOIN kardex ON kardex.articulos_id = articulos.id ';
        $join .= 'LEFT JOIN kardex_detalle ON kardex_detalle.kardex_id = kardex.id ';
        if (!is_numeric($producto)) {
            $producto = $this->getProductoID($producto)->id;
        }
        $conditions = "articulos.id = '$producto' ";
        return $this->find_first("columns: $colum", "join: $join", "conditions: $conditions");
    }

    /**
     * Método para listar los articulos para json
     * @return array
     */
    public function getListadoToJson($tipo_articulo) {
        $colm = 'articulos.id, articulos.mpn, articulos.nombre, marca.marca ';
        $join = 'INNER JOIN marca ON marca.id = articulos.marca_id ';
        if (!empty($tipo_articulo == 'A')) {
            $cond = "articulos.tipo_articulo = 'A' ";
        } else {
            $cond = "articulos.activo != 'NULL' ";
        }

        $order = 'articulos.id DESC';
        $rs = $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order");

        $data = array();
        if ($rs) {
            foreach ($rs as $row) {
                $data[] = trim($row->mpn) . ' | ' . trim($row->nombre) . ' | ' . trim($row->marca);
            }
        }
        return json_encode($data);
    }

    /**
     * Método para verificar la cabecera de las cotizaciones enviadas en json
     */
    public function getProductoID($string) {
        $partes = explode('|', $string);
        if (count($partes) != 3) { //si no está definida las partes
            return NULL;
        }
        $n_part = Filter::get($partes[0], 'string');
        $nombre = Filter::get($partes[1], 'string');
        $marca = Filter::get($partes[2], 'string');

        if (empty($nombre) OR empty($marca)) {
            return FALSE;
        }
        $join = 'INNER JOIN marca ON marca.id = articulos.marca_id ';
        $conditions = "articulos.mpn LIKE '%$n_part%' AND articulos.nombre LIKE '%$nombre%' ";
        if (!empty($marca)) {
            $conditions .= " AND marca.marca LIKE '$marca'";
        }
        return $this->find_first("columns: articulos.*", "join: $join", "conditions: $conditions");
    }

    /**
     * Método para obtener la información del codigo del articulo
     * @return type
     */
    function codigoArticuloSistema() {
        $columnas = "articulos.id, articulos.codigo";
        $condicion = "articulos.codigo != 'NULL' ";
        return $this->find_first("columns: $columnas", "conditions: $condicion", "order: id desc");
    }

    /**
     * Método consultar los articulos
     */
    public function informacionArticulo($cod_articulo) {
        $colum = "articulos.* ";
        $condi = "articulos.mpn = '$cod_articulo'";
        return $this->find_first("columns: $colum", "conditions: $condi");
    }

}
