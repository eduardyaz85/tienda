<?php

/**
 * Asdin - Web | App
 *
 * Descripcion: Modelo encargado de registrar las categorias
 *
 * @category    
 * @package     Models 
 * @author      Eduardo Yánez (asdin_shop@yahoo.com)
 * @copyright   Copyright (c) 2019 Asdin
 * @revision    1.0
 */
class Categorias extends ActiveRecord {

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
//        $this->validates_presence_of('category', 'message: Ingresa la categoria');
    }

    /**
     * Método para listar las categorias
     * @return array
     */
    public function getListadoCategorias($estado = 'todos', $order = '', $page = 0) {
        $colm = 'categorias.*';
        $cond = "categorias.activo != 'NULL' ";
        if ($estado != 'todos') {
            $cond .= ($estado == self::ACTIVO) ? " AND activo=" . self::ACTIVO : " AND activo=" . self::INACTIVO;
        }
        $order = $this->get_order($order, 'category');
        if ($page) {
            return $this->paginated("columns: $colm", "conditions: $cond", "order: $order", "page: $page");
        }
        return $this->find("columns: $colm", "conditions: $cond", "order: $order");
    }

    /**
     * Método para cargar las categorias en articulos
     * @return array
     */
    public function getListadoCategoriasArticulos($estado = 'todos', $order = '', $page = 0) {
        $colm = 'categorias.id, categorias.category, categorias.slug';
        $cond = "categorias.parent_id != 0 ";
        if ($estado != 'todos') {
            $cond .= ($estado == self::ACTIVO) ? " AND activo=" . self::ACTIVO : " AND activo=" . self::INACTIVO;
        }
        $order = $this->get_order($order, 'category');
        if ($page) {
            return $this->paginated("columns: $colm", "conditions: $cond", "order: $order", "page: $page");
        }
        return $this->find("columns: $colm", "conditions: $cond", "order: $order");
    }

    /**
     * Método que devuelve las categorias paginadas o para un select
     */
    public function getListadoCategoriaPadre($estado = 'todos', $order = '', $page = 0) {
        $conditions = "categorias.parent_id = 0 AND categorias.tipo != 'i' AND categorias.activo = " . self::ACTIVO;
        $order = $this->get_order($order, 'category');
        if ($page) {
            return $this->paginated("conditions: $conditions", "order: $order", "page: $page");
        }
        return $this->find("conditions: $conditions", "order: $order");
    }

    /**
     * Método para obtener las subcategorias de cada categoria
     */
    public function getListadoSubcategoria($categoria) {
        $colm = 'categorias.* ';
        $cond = "categorias.parent_id = $categoria AND categorias.tipo != 'i' AND categorias.activo = " . self::ACTIVO;
        $order = 'categorias.category ASC';
        return $this->find("columns: $colm", "conditions: $cond", "order: $order");
    }

    /**
     * Método para buscar items
     */
    public function getAjaxCategorias($field, $value, $order = '', $page = 0) {
        $value = Filter::get($value, 'string');
        if (strlen($value) <= 4 OR ( $value == 'none')) {
            return NULL;
        }
        $colm = "categorias.* ";
        $cond = "categorias.activo != 'NULL' ";

        $order = $this->get_order($order, 'category');
        if ($field == 'categoria') {
            $field = 'category';
        }
        //Defino los campos habilitados para la búsqueda
        $fields = array('category', 'slug');
        if (!in_array($field, $fields)) {
            $field = 'categoria';
        }
        if (!($field == 'category' && $value == 'todas')) {
            $cond .= " AND $field LIKE '%$value%'";
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
    public static function setCategorias($method, $data, $optData = null) {
        $obj = new Categorias($data); //Se carga los datos con los de las tablas        
        if ($optData) { //Se carga información adicional al objeto
            $obj->dump_result_self($optData);
        }

        //Verifico que no exista otro categoria, y si se encuentra inactivo lo active
        $conditions = empty($obj->id) ? "category='$obj->category' AND slug='$obj->slug'" : "category='$obj->category' AND slug='$obj->slug' AND id != '$obj->id'";
        $old = new Categorias();
        if ($old->find_first($conditions)) {
            if ($method == 'create') {
                $obj->id = $old->id;
                $method = 'update';
            } else {
                Flash::info("Ya existe una categoria ({$obj->category}) registrado bajo esos parámetros.");
                return FALSE;
            }
        }
        return ($obj->$method()) ? $obj : FALSE;
    }

    /**
     * Callback que se ejecuta antes de guardar/modificar
     */
    public function before_save() {
        $this->category = filter_var($this->category, FILTER_SANITIZE_STRING);
        Logger::debug('** categoria **' . $this->category);
        if (empty($this->parent_id)) {
            $this->clase = 'dropdown mega-dropdown';
            $this->url = '#';
            $this->have_childrens = 0;
        } else {
            $this->url = strtolower($this->url);
            $this->have_childrens = 1;
        }
    }

    /**
     * Método consultar las categorias
     */
    public function getInformacionCategoria($categoria) {
        $colum = "categorias.id, categorias.slug, categorias.category";
        $condi = "categorias.category = '$categoria'";
        return $this->find_first("columns: $colum", "conditions: $condi");
    }

    /**
     * Método consultar las categorias
     */
    public function getInformacionCategoriaSlug($id_categoria) {
        $colum = "categorias.id, categorias.slug, categorias.category";
        $condi = "categorias.slug = '$id_categoria'";
        return $this->find_first("columns: $colum", "conditions: $condi");
    }

    /**
     * Método para obtener las categorias como json
     * @return type
     */
    public function getCategoriasToJson() {
        $rs = $this->find("columns: category", 'condition: parent_id != 0', 'order: category ASC');
        $categorias = array();
        foreach ($rs as $categoria) {
            $categorias[] = $categoria->category;
        }
        return json_encode($categorias);
    }

    /**
     * Método para buscar la categoria
     */
    public function getNombreCategoria($categoria) {
        return $this->find_first("id = '$categoria' ");
    }

    /**
     * Método para buscar el ID de la categoria
     */
    public function getIdByTitulo($titulo) {
        $this->find_first("url = '$titulo'");
        return $this->id;
    }

    /**
     * Método para buscar el NOMBRE de la categoria
     */
    public function getNombreIdCategoria($cod) {
        $this->find_first("id = '$cod'");
        return $this->category;
    }

    /**
     * Método que devuelve las categorias de productos con stock
     */
    public function getCategoriaWeb() {
        $colm = "categorias.id, cp.id AS id_padre, cp.parent_id, cp.slug, cp.category, cp.have_childrens, cp.clase, cp.url, cp.tipo, cat_master.instock";
        $join = "LEFT JOIN cat_master ON cat_master.categorias_id = categorias.id ";
        $join .= "LEFT JOIN categorias AS cp ON cp.id = categorias.parent_id ";
        $cond = "categorias.tipo != 'i' AND cat_master.instock != 0 AND cp.parent_id = 0 ";
        return $this->find("columns: $colm", "join: $join", "conditions: $cond", "group: cp.slug", "order: cp.category");
    }

    /**
     * Método para obtener las subcategorias de productos con stock
     */
    public function getSubcategoriasWeb($categoria) {
        $colm = 'categorias.* ';
        $join = "LEFT JOIN cat_master ON cat_master.categorias_id = categorias.id ";
        $cond = "categorias.parent_id = $categoria AND categorias.tipo != 'i' AND cat_master.instock != 0 ";
        return $this->find("columns: $colm", "join: $join", "conditions: $cond", "group: slug");
    }

}
