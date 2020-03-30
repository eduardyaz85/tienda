<?php

class Kardex extends ActiveRecord {

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
     * Método que se ejecuta antes de inicializar cualquier acción
     */
    public function initialize() {
        
    }

    /**
     * Método para listar los articulos en el Kardex
     * @return array
     */
    public function getListadoArticulosKardex($estado = 'todos', $order = '', $page = 0, $tipo = '') {
        $colm = "kardex.id, kardex.numero, kardex.minimo,  kardex.maximo, kardex.usuario_registra, kardex.estado, kardex.articulos_id, ";
        $colm .= "categorias.category, marca.marca, umedida.simbolo, articulos.mpn, articulos.sku, articulos.nombre, articulos.precio_venta  ";
        $join = 'INNER JOIN articulos ON articulos.id = kardex.articulos_id ';
        $join .= 'LEFT JOIN categorias ON categorias.id = articulos.categorias_id ';
        $join .= 'LEFT JOIN marca ON marca.id = articulos.marca_id ';
        $join .= 'LEFT JOIN umedida ON umedida.id = articulos.umedida_id ';
//        $join .= 'LEFT JOIN kardex_detalle ON kardex_detalle.kardex_id = kardex.id ';
//        $join .= 'LEFT JOIN kardex_existencias AS ke ON ke.kardex_detalle_id = kardex_detalle.id ';
        $cond = "articulos.tipo_articulo = '$tipo' ";

        $order = $this->get_order($order, 'nombre', array(
            'codigo' => array(
                'ASC' => 'articulos.sku ASC, articulos.nombre ASC, categorias.category DESC',
                'DESC' => 'articulos.sku DESC, articulos.nombre DESC, categorias.category DESC'
            ),
            'nombre' => array(
                'ASC' => 'articulos.nombre ASC, categorias.category DESC',
                'DESC' => 'articulos.nombre DESC, categorias.category DESC'
            ),
            'category' => array(
                'ASC' => 'categorias.category ASC, articulos.nombre ASC',
                'DESC' => 'categorias.category DESC, articulos.nombre DESC'
            ),
            'email' => array(
                'ASC' => 'usuario.email ASC, categorias.category ASC, articulos.nombre ASC',
                'DESC' => 'usuario.email DESC, categorias.category DESC, articulos.nombre DESC'
            ),
            'estado' => array(
                'ASC' => 'kardex.estado ASC, categorias.category ASC, articulos.nombre ASC',
                'DESC' => 'kardex.estado DESC, categorias.category DESC, articulos.nombre DESC'
            )
        ));

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
     * Método para verificar el articulo en el Kardex
     * @return array
     */
    public function getInformacionArticulosKardex($kardex = '') {
        $colm = "kardex.*, categorias.category, marca.marca, umedida.simbolo, ";
        $colm .= "articulos.mpn, articulos.sku, articulos.codigo, articulos.nombre, articulos.precio_venta ";
        $join = 'INNER JOIN articulos ON articulos.id = kardex.articulos_id ';
        $join .= 'LEFT JOIN categorias ON categorias.id = articulos.categorias_id ';
        $join .= 'LEFT JOIN marca ON marca.id = articulos.marca_id ';
        $join .= 'LEFT JOIN umedida ON umedida.id = articulos.umedida_id ';
        $cond = "kardex.id = '$kardex' ";

        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond");
    }

    /**
     * Método para buscar articulos
     */
    public function getAjaxArticulosKardex($field, $value, $order = '', $page = 0, $tipo = '') {
        $value = Filter::get($value, 'string');
        if (strlen($value) <= 4 OR ( $value == 'none')) {
            return NULL;
        }
        $colm = "kardex.*, categorias.category, marca.marca, umedida.simbolo, ";
        $colm .= "articulos.mpn, articulos.sku, articulos.nombre, articulos.precio_venta ";
        $join = 'INNER JOIN articulos ON articulos.id = kardex.articulos_id ';
        $join .= 'LEFT JOIN categorias ON categorias.id = articulos.categorias_id ';
        $join .= 'LEFT JOIN marca ON marca.id = articulos.marca_id ';
        $join .= 'LEFT JOIN umedida ON umedida.id = articulos.umedida_id ';
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
     * Método para setear
     * 
     * @param array $data
     * @return
     */
    public static function setKardex($method, $data, $optData = NULL) {
        $obj = new Kardex($data);
        if ($optData) {
            $obj->dump_result_self($optData);
        }
        if ($method != 'delete') {
            if (empty($obj->minimo)) {
                Flash::error('Indica el stock minimo');
                return FALSE;
            }
            if (empty($obj->maximo)) {
                Flash::error('Indica el stock maximo');
                return FALSE;
            }

            if ($obj->maximo <= $obj->minimo) {
                Flash::error('El stock mínimo no puede ser igual o mayor al máximo');
                return FALSE;
            }

            //Si es un registro nuevo, creo un numero secuencial al Kardex
            if ($method == 'create') {
                $numeroKardex = $obj->getNumeroKardex();
                $numero = empty($numeroKardex->numero) ? '00000000' : $numeroKardex->numero;
                $nuevo = DwOnline::setNumeroSecuencial($numero);
                $obj->numero = $nuevo;
            }

            $old = (isset($obj->id)) ? $obj->count("articulos_id='$obj->articulos_id' AND id!= $obj->id") : $obj->count("articulos_id='$obj->articulos_id'");
            if ($old) {
                Flash::info('Lo sentimos, pero ya se encuentra este articulo registrado en el Kardex');
                return FALSE;
            }
        } else {
            //Valido el ID antes de eliminar
            $obj->id = Filter::get($obj->id, 'int');
            if (empty($obj->id)) {
                Flash::error('El id del kardex no existe');
                return FALSE;
            }
            $obj->find_first("columns: kardex.*", "conditions: kardex.id = $obj->id");
            if ((Session::get('perfil_id') >= Perfil::ADMIN)) {
                Flash::error('Tu no tienes los permisos para anular los registros de este articulo');
                return FAlSE;
            }
        }

        $rs = $obj->$method();
        if ($rs) {
            ($method == 'create') ? DwAudit::debug("Se ha registrado un item en el kardex $obj->numero, id $obj->id") : DwAudit::debug("Se ha modificado la información del kardex $obj->numero, id $obj->id");
        }
        return ($rs) ? $obj : FALSE;
    }

    /**
     * Metodo para buscar el ultimo numero secuencial
     */
    public function getNumeroKardex() {
        $colm = "kardex.id, kardex.numero";
        $cond = "kardex.id IS NOT NULL ";
        return $this->find_first("columns: $colm", "conditions: $cond", "order: id desc");
    }

    /**
     * Metodo para obtener kardex 
     */
    public function obtenerKardex($id) {
        $colm = "kardex.id as kardex_id, kardex_detalle.valor_unitario, kardex_existencias.* ";
        $join = "LEFT JOIN kardex_detalle ON kardex_detalle.kardex_id = kardex.id ";
        $join .= "LEFT JOIN kardex_existencias ON kardex_existencias.kardex_detalle_id = kardex_detalle.id ";
        $cond = "kardex.id != 0 AND kardex.articulos_id = $id ";
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond", "order: id desc");
    }

    /**
     * Metodo para obtener historial kardex 
     */
    public function obtenerRegistrosKardex($id) {
        $colm = "kardex_detalle.valor_total, tablas_tipos.* ";
        $join = "INNER JOIN kardex_detalle ON kardex_detalle.kardex_id = kardex.id ";
        $join .= "INNER JOIN kardex_existencias ON kardex_existencias.kardex_detalle_id = kardex_detalle.id ";
        $join .= "INNER JOIN tablas_tipos ON kardex_detalle.concepto_id = tablas_tipos.id ";
        $cond = "kardex.id != 0 AND kardex.articulos_id = $id AND tablas_tipos.codigo != 'iin'";
        return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: id desc");
    }

    /**
     * Metodo para obtener carga inicial 
     */
    public function obtenerCargaInicialKardex($id) {
        $dataConcepto = DwOnline::setCodigoDocumento('', KardexDetalle::SALDO_INICIAL);
        $colm = "kardex.id, kardex_detalle.valor_total";
        $join = "INNER JOIN kardex_detalle ON kardex_detalle.kardex_id = kardex.id ";
        $cond = "kardex.id != 0 AND kardex.articulos_id = $id and kardex_detalle.concepto_id = $dataConcepto->id ";
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond", "order: id desc");
    }

    /**
     * Metodo para obtener inventario final 
     */
    public function obtenerInventarioFinalKardex($id) {
        $colm = "kardex.id as kardex_id, kardex_detalle.id, kardex_existencias.costo_total, (kardex_existencias.cantidad_existencia) AS stock_actual, (kardex_existencias.costo_unitario) AS ultimo_precio";
        $join = "INNER JOIN kardex_detalle ON kardex_detalle.kardex_id = kardex.id ";
        $join .= "INNER JOIN kardex_existencias ON kardex_existencias.kardex_detalle_id = kardex_detalle.id ";
        $cond = "kardex.id != 0 AND kardex.articulos_id = $id";
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond", "order: id desc");
    }

    /**
     * Método para obtener los ultimos 3 precios
     */
    public function getListadoPreciosKardex($producto, $limit = 1) {
        $colm = 'kardex.id, kardex.numero, kardex.minimo,  kardex.maximo, kardex.estado, ke.cantidad_existencia, ke.costo_unitario, kardex_detalle.creado_at, ';
        $colm .= 'categorias.category, marca.marca, umedida.simbolo, articulos.mpn, articulos.sku, articulos.nombre, articulos.precio_venta, kardex.articulos_id  ';
        $join = 'INNER JOIN articulos ON articulos.id = kardex.articulos_id ';
        $join .= 'LEFT JOIN categorias ON categorias.id = articulos.categorias_id ';
        $join .= 'LEFT JOIN marca ON marca.id = articulos.marca_id ';
        $join .= 'LEFT JOIN umedida ON umedida.id = articulos.umedida_id ';
        $join .= 'LEFT JOIN kardex_detalle ON kardex_detalle.kardex_id = kardex.id ';
        $join .= 'LEFT JOIN kardex_existencias AS ke ON ke.kardex_detalle_id = kardex_detalle.id ';
        /*        if (!is_numeric($producto)) {
          $producto = $this->getProductoID($producto)->id;
          } */
        $cond = "articulos.tipo_articulo = 'A' AND kardex.articulos_id = $producto ";
        $limit = Filter::get($limit, 'int');
        if ($limit <= 1) {
            $order = 'kardex_detalle.creado_at DESC';
            return $this->find_first("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
        } else {
            $order = 'kardex_detalle.creado_at DESC';
            return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order", "limit: $limit");
        }
    }

}
