<?php

class Compras extends ActiveRecord {

    //Se desabilita el logger para no llenar el archivo de "basura"
    public $logger = FALSE;

    /**
     * Constante para definir un estado Creado
     */
    const Creado = 1;

    /**
     * Constante para definir un estado Generado
     */
    const Generado = 2;

    /**
     * Constante para definir un estado Aprobado
     */
    const Aprobado = 3;

    /**
     * Constante para definir un estado Aprobado
     */
    const Cargado = 4;

    /**
     * Constante para definir un estado Aprobado
     */
    const Retencion = 5;

    /**
     * Constante para definir el estado de un documento Electronico
     */
    const Finalizado = 6;

    /**
     * Constante para definir el estado de un documento Electronico
     */
    const Enviado = 7;

    /**
     * Constante para definir un estado Anulado
     */
    const Anulado = 0;

    /**
     * Método que se ejecuta antes de cualquier acción
     */
    protected function initialize() {
        $this->validates_presence_of('fecha_compra', 'message: Ingresa la fecha de la compra');
    }

    /**
     * Método para listar los compras
     * @return array
     */
    public function getListadoCompras($estado = 'todos', $order = '', $page = 0, $tipo = '') {
        $colm = "compras.*, empresa.razon_social, ur.login as usuario_registro, ua.login as usuario_autoriza ";
        $join = 'INNER JOIN empresa ON empresa.id = compras.empresas_id ';
        $join .= 'INNER JOIN usuario ur ON ur.id = compras.usuario_registro ';
        $join .= 'LEFT JOIN usuario ua ON ua.id = compras.usuario_autoriza';
        //$cond = "compras.fecha_compra = '$tipo' ";
        $cond = "compras.estado IS NOT NULL OR compras.estado != 0";
        $order = $this->get_order($order, 'fecha_compra', array('id' => 'compras.id',
            'fecha_compra' => array(
                'ASC' => 'compras.fecha_compra ASC, empresa.razon_social DESC',
                'DESC' => 'compras.fecha_compra DESC, empresa.razon_social DESC')));
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
     * Método para listar los compras
     * @return array
     */
    public function getListadoComprasExistencias($estado = 'todos', $order = '', $page = 0, $tipo = '') {
        $colm = "compras.*, empresa.razon_social, ur.login as usuario_registro, ua.login as usuario_autoriza ";
        $join = 'INNER JOIN empresa ON empresa.id = compras.empresas_id ';
        $join .= 'INNER JOIN usuario ur ON ur.id = compras.usuario_registro ';
        $join .= 'LEFT JOIN usuario ua ON ua.id = compras.usuario_autoriza';
        //$cond = "compras.fecha_compra = '$tipo' ";
        $cond = "compras.estado = '$tipo' ";
        $order = $this->get_order($order, 'fecha_compra', array('id' => 'compras.id',
            'fecha_compra' => array(
                'ASC' => 'compras.fecha_compra ASC, empresa.razon_social DESC',
                'DESC' => 'compras.fecha_compra DESC, empresa.razon_social DESC')));
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
     * Método para registro de compras
     */
    public static function setRegistroBaseCompras($articulos) {
        $obj = new Compras();
        $obj->begin();
        if (!empty($articulos)) {
            foreach ($articulos as $value) {
                $data = explode('-sisco-', $value); //el formato es 1-4 = recurso_id-perfil_id
                $obj->codigo = empty($data[0]) ? 'NULL' : $data[0];
                $obj->fecha_compra = empty($data[1]) ? 'NULL' : $data[1];
                $obj->factura = empty($data[2]) ? 'NULL' : $data[2];
                $obj->detalle = empty($data[3]) ? 'NULL' : $data[3];
                $obj->nombre = empty($data[4]) ? 'NULL' : $data[4];
                $obj->stock = empty($data[5]) ? 'NULL' : $data[5];
                $obj->precio_unitario = empty($data[6]) ? 'NULL' : $data[6];
                $obj->usuarios_id = Session::get('id');
                $obj->activo = '0';
                $obj->p_venta = DwOnline::setPrecioUtilidad($obj->precio_unitario);
                if ($obj->stock > 2) {
                    if (!$obj->create()) {
                        $obj->rollback();
                        return FALSE;
                    }
                }
            }
        }
        $obj->commit();
        return TRUE;
    }

    /**
     * Método para buscar compras
     */
    public function getAjaxCompras($field, $value, $order = '', $page = 0) {
        $value = Filter::get($value, 'string');
        if (strlen($value) <= 4 OR ( $value == 'none')) {
            return NULL;
        }
        $colm = "compras.id, compras.codigo, compras.numero, compras.fecha_compra, compras.autorizacion, compras.registro_at, compras.modificado_in, compras.estado, empresa.razon_social, ur.login as usuario_registro, ua.login as usuario_autoriza ";
        $join = 'INNER JOIN empresa ON empresa.id = compras.empresas_id ';
        $join .= 'INNER JOIN usuario ur ON ur.id = compras.usuario_registro ';
        $join .= 'LEFT JOIN usuario ua ON ua.id = compras.usuario_autoriza';
        $cond = "compras.estado IS NOT NULL ";

        $order = $this->get_order($order, 'fecha_compra');
        if ($field == 'numero_orden') {
            $field = 'compras.codigo';
        }
        if ($field == 'usuario_registro') {
            $field = 'ur.login';
        }
        if ($field == 'usuario_autoriza') {
            $field = 'ua.login';
        }
        //Defino los campos habilitados para la búsqueda
        $fields = array('compras.codigo', 'fecha_compra', 'razon_social', 'ur.login', 'ua.login');
        if (!in_array($field, $fields)) {
            $field = 'fecha_compra';
        }

        if (!($field == 'fecha_compra' && $value == 'todas')) {
            $cond .= " AND $field LIKE '%$value%'";
        }
        if ($page) {
            return $this->paginated("columns: $colm", "join: $join", "conditions: $cond", "order: $order", "page: $page");
        } else {
            return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
        }
    }

    /**
     * Método para buscar ultimo numero de factura
     */
    public function numeroCompraSistema() {
        $colm = "compras.id, compras.codigo ";
        $cond = "compras.id IS NOT NULL";
        return $this->find_first("columns: $colm", "conditions: $cond", 'order: compras.id DESC');
    }

    /**
     * Método para ver la informacion de la orden de compra
     * @return array
     */
    public function getInformacionCompra($compra_id) {
        $compra_id = Filter::get($compra_id, 'int');
        if (!$compra_id) {
            return NULL;
        }
        $colm = "compras.*, empresa.razon_social, empresa.ruc, empresa.contabilidad, sucursal.direccion, sucursal.telefono, sucursal.email, empresa.tipo_documento ";
        $join = 'INNER JOIN empresa ON empresa.id = compras.empresas_id ';
        $join.= 'LEFT JOIN sucursal ON sucursal.empresas_id = empresa.id ';
        $cond = "compras.id = '$compra_id' ";
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond");
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
    public static function setCompras($method, $data, $optData = null) {
        $obj = new Compras($data); //Se carga los datos con los de las tablas        
        if ($optData) { //Se carga información adicional al objeto
            $obj->dump_result_self($optData);
        }
//        $obj->numero = trim(strtoupper($obj->numero));
        $obj->empresas_id = $obj->empresas_id;
        $obj->fecha_compra = $obj->fecha_compra;

        if (isset($data['id'])) {
            $obj->id = $data['id'];
            $obj->modificado_in = date('Y-m-d');
            $method = 'update';
        }
        return ($obj->$method()) ? $obj : FALSE;
    }

    /**
     * Callback que se utiliza después de insertar
     */
    protected function after_create() {
        $this->mpn = strtoupper($this->mpn);
        $this->nombre = strtoupper($this->nombre);
    }

    /**
     * Método para obtener los precios de los articulos
     */
    public function getPrecioArticuloVenta($producto, $limit = 1) {
        $colum = 'articulos.*, marca.marca ';
        $join = 'INNER JOIN marca ON marca.id = articulos.marca_id ';
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
    public function getListadoToJson() {
        $colm = 'articulos.id, articulos.mpn, articulos.nombre, marca.marca ';
        $join = 'INNER JOIN marca ON marca.id = articulos.marca_id ';
        $cond = "articulos.activo != 'NULL' ";

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
    function codigoCompraSistema() {
        $columnas = "compras.id, compras.codigo";
        $condicion = "compras.id IS NOT NULL ";
        return $this->find_first("columns: $columnas", "conditions: $condicion", "order: id desc");
    }

}
