<?php

class CatTemporal extends ActiveRecord {

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
        $this->validates_presence_of('descripcion', 'message: Ingresa el nombre del articulo');
    }

    /**
     * Método para listar los cat_temporal
     * @return array
     */
    public function getListadoTemporalArticulos($estado = 'todos', $order = '', $page = 0) {
        $colm = "cat_temporal.* ";
        $cond = "cat_temporal.activo = " . CatTemporal::ACTIVO . " ";
        $order = $this->get_order($order, 'descripcion');
        if ($estado != 'todos') {
            $cond .= ($estado == self::ACTIVO) ? " AND activo=" . self::ACTIVO : " AND activo=" . self::INACTIVO;
        }
        if ($page) {
            return $this->paginated("columns: $colm", "conditions: $cond", "order: $order", "page: $page");
        } else {
            return $this->find("columns: $colm", "conditions: $cond", "order: $order");
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
        $obj = new CatTemporal($data); //Se carga los datos con los de las tablas        
        if ($optData) { //Se carga información adicional al objeto
            $obj->dump_result_self($optData);
        }

        if (empty($obj->mpn)) {
            $obj->mpn = $obj->sku;
        }

        if (empty($obj->sku)) {
            $obj->sku = $obj->mpn;
        }

        if (empty($obj->cantidad)) {
            Flash::error("Indica el stock");
            return FALSE;
        }

        //Verifico que no exista otra articulo, y si se encuentra inactivo lo active
        $conditions = empty($obj->id) ? "mpn='$obj->mpn'" : "mpn='$obj->mpn' AND id != '$obj->id'";
        $old = new CatTemporal();
        if ($old->find_first($conditions)) {
            if ($method == 'create' && $old->activo != CatTemporal::ACTIVO) {
                $obj->id = $old->id;
                $obj->activo = CatTemporal::ACTIVO;
                $method = 'update';
            } else {
                Flash::error('Ya existe un articulo ' . $obj->descripcion . '<br> registrado bajo esos parámetros.');
                return FALSE;
            }
        }
        return ($obj->$method()) ? $obj : FALSE;
    }

    /**
     * Método que se ejecuta antes de guardar y/o modificar     
     */
    public function before_save() {
        $this->sku = filter_var(strtoupper($this->sku), FILTER_SANITIZE_STRING);
        $this->mpn = filter_var(strtoupper($this->mpn), FILTER_SANITIZE_STRING);
        $this->descripcion = filter_var(strtoupper($this->descripcion), FILTER_SANITIZE_STRING);
        $this->detalle = filter_var(strtoupper($this->detalle), FILTER_SANITIZE_STRING);
        $this->tipo_articulo = filter_var(strtolower($this->tipo_articulo), FILTER_SANITIZE_STRING);
    }

    /**
     * Método para registro del catalogo en array masivo
     */
    public static function setGuardaExcelUpload($method, $data, $optData = null) {
        $obj = new CatTemporal($data);
        (new Sistema)->getTruncateTabla('cat_temporal');

        $obj->begin();
        try {
            if (!empty($optData)) {
                //Registro los cat_temporal en la tabla temporal
                foreach ($optData as $key) {
                    $data = array();
                    $data['sku'] = empty($key['codigo_principal']) ? '' : $key['codigo_principal'];
                    $data['mpn'] = empty($key['codigo_auxiliar']) ? '' : $key['codigo_auxiliar'];
                    $data['categoria'] = empty($key['categoria']) ? '' : $key['categoria'];
                    $data['sub_categoria'] = empty($key['sub_categoria']) ? '' : $key['sub_categoria'];
                    $data['marca'] = empty($key['marca']) ? '' : $key['marca'];
                    $data['descripcion'] = empty($key['descripcion']) ? '' : $key['descripcion'];
                    $data['detalle'] = empty($key['detalle']) ? '' : $key['detalle'];
                    $data['cantidad'] = empty($key['cantidad']) ? '' : $key['cantidad'];
                    $data['precio_compra'] = empty($key['precio_compra']) ? '' : $key['precio_compra'];
                    $data['precio_venta'] = empty($key['precio_venta']) ? '' : $key['precio_venta'];
                    $data['aplica_iva'] = empty($key['aplica_iva']) ? '' : $key['aplica_iva'];
                    $data['aplica_ice'] = empty($key['aplica_ice']) ? '' : $key['aplica_ice'];
                    $data['unidad'] = empty($key['unidad']) ? '' : $key['unidad'];
                    $data['tipo_articulo'] = empty($key['tipo_articulo']) ? '' : $key['tipo_articulo'];
                    $data['minimo'] = empty($key['stock_minimo']) ? '' : $key['stock_minimo'];
                    $data['maximo'] = empty($key['stock_maximo']) ? '' : $key['stock_maximo'];
                    $data['aplica_iva'] = empty($key['aplica_iva']) ? '' : $key['aplica_iva'];
                    $data['activo'] = '1';

                    if (!CatTemporal::setArticulos('create', NULL, $data)) {
                        Flash::info('Se ha producido un error al crear el articulo...');
                        $obj->rollback();
                        return FALSE;
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
     * Método para validar el archivo de excel
     */
    public static function setValidacionExcel($articulos) {
        $errores = array();

        foreach ($articulos as $key => $value) {
            if (empty($value->sku)) {
                Flash::info("Error en la fila ({$value->id}), El campo codigo principal no puede estar vacio.. <br>Por Favor modifique y vuelva a intentarlo..");
                $errores[] = $value->id;
            } else if (empty($value->mpn)) {
                Flash::info("Error en la fila ({$value->id}), El campo codigo auxiliar no puede estar vacio.. <br>Por Favor modifique y vuelva a intentarlo..");
                $errores[] = $value->id;
            } else if (empty($value->descripcion)) {
                Flash::info("Error en la fila ({$value->id}), El campo nombre no puede estar vacio.. <br>Por Favor modifique y vuelva a intentarlo..");
                $errores[] = $value->id;
            } else if ((empty($value->cantidad) || $value->cantidad <= 0) && ($value->tipo_articulo != 's')) {
                Flash::info("Error en la fila ({$value->id}), El campo cantidad no puede estar vacio ni ser menor a 1.. <br>Por Favor modifique y vuelva a intentarlo..");
                $errores[] = $value->id;
            } else if ((empty($value->precio_compra) || $value->precio_compra <= 0) && ($value->tipo_articulo != 's')) {
                Flash::info("Error en la fila ({$value->id}), El campo precio compra no puede estar vacio, ni ser mayor al precio de venta.. <br>Por Favor modifique y vuelva a intentarlo..");
                $errores[] = $value->id;
            } else if (empty($value->precio_venta) || $value->precio_venta <= 0 || $value->precio_venta < $value->precio_compra) {
                Flash::info("Error en la fila ({$value->id}), El campo precio venta no puede estar vacio, debe ser mayor a 1 y mayor al precio de compra..<br> Por Favor modifique y vuelva a intentarlo..");
                $errores[] = $value->id;
            } else if (empty($value->categoria)) {
                Flash::info("Error en la fila ({$value->id}), El campo categoria no puede estar vacio.. <br>Por Favor modifique y vuelva a intentarlo..");
                $errores[] = $value->id;
            } else if (empty($value->sub_categoria)) {
                Flash::info("Error en la fila ({$value->id}), El campo sub_categoria no puede estar vacio.. <br>Por Favor modifique y vuelva a intentarlo..");
                $errores[] = $value->id;
            } else if (empty($value->marca)) {
                Flash::info("Error en la fila ({$value->id}), El campo marca no puede estar vacio.. <br>Por Favor modifique y vuelva a intentarlo..");
                $errores[] = $value->id;
            } else if (empty($value->unidad)) {
                Flash::info("Error en la fila ({$value->id}), El campo unidad no puede estar vacio.. <br>Por Favor modifique y vuelva a intentarlo..");
                $errores[] = $value->id;
            } else if (empty($value->tipo_articulo)) {
                Flash::info("Error en la fila ({$value->id}), El campo tipo articulo no puede estar vacio.. <br>Por Favor modifique y vuelva a intentarlo..");
                $errores[] = $value->id;
            } else if ((empty($value->minimo) || $value->minimo <= 0 || $value->minimo > $value->maximo) && ($value->tipo_articulo != 's')) {
                Flash::info("Error en la fila ({$value->id}), El campo stock minimo no puede estar vacio, debe ser mayor a 0 y menor al stock maximo..<br> Por Favor modifique y vuelva a intentarlo..");
                $errores[] = $value->id;
            } else if ((empty($value->maximo) || $value->maximo <= 0 || $value->maximo < $value->minimo) && ($value->tipo_articulo != 's')) {
                Flash::info("Error en la fila ({$value->id}), El campo stock maximo no puede estar vacio, debe ser mayor a 0 y mayor al stock minimo..<br> Por Favor modifique y vuelva a intentarlo..");
                $errores[] = $value->id;
            }
        }
        if (!empty($errores)) {
            return $errores;
        }
    }

    /**
     * Método para buscar cat_temporal
     */
    public function getAjaxTemporalArticulos($field, $value, $order = '', $page = 0) {
        $value = Filter::get($value, 'string');
        if (strlen($value) <= 0 OR ( $value == 'none')) {
            return NULL;
        }
        $colm = "cat_temporal.* ";
        $cond = "cat_temporal.activo IS NOT NULL ";

        $order = $this->get_order($order, 'descripcion');
        if ($field == 'fila') {
            $field = 'cat_temporal.id';
        }
        //Defino los campos habilitados para la búsqueda
        $fields = array('cat_temporal.id');
        if (!in_array($field, $fields)) {
            $field = 'cat_temporal.id';
        }
        if (!($field == 'fila' && $value == 'todas')) {
            $cond .= " AND $field = '$value'";
        }
        if ($page) {
            return $this->paginated("columns: $colm", "conditions: $cond", "order: $order", "page: $page");
        } else {
            return $this->find("columns: $colm", "conditions: $cond", "order: $order");
        }
    }

}
