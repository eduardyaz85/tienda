<?php

/**
 * Asdin - Web | App
 *
 * Descripcion: Modelo encargado de registrar la galeria del catalogo
 *
 * @category    
 * @package     Models 
 * @author      Eduardo Yánez (asdin_shop@yahoo.com)
 * @copyright   Copyright (c) 2019 Asdin
 * @revision    1.0
 */
class Galeria extends ActiveRecord {

    //Se desabilita el logger para no llenar el archivo de "basura"
    public $logger = FALSE;

    /**
     * Método que se ejecuta antes de inicializar cualquier acción
     */
    public function initialize() {
        $this->has_many('empresa');
    }

    /**
     * Método para setear
     * 
     * @param array $data
     * @return
     */
    public static function setGaleria($method, $data, $optData = null) {
        $obj = new Galeria($data); //Se carga los datos con los de las tablas        
        if ($optData) { //Se carga información adicional al objeto
            $obj->dump_result_self($optData);
        }
        //Verifico que no exista otra galeria, y si se encuentra inactivo lo active
        $conditions = empty($obj->id) ? "mpn='$obj->mpn' AND empresas_id='$obj->empresas_id'" : "mpn='$obj->mpn' AND empresas_id='$obj->empresas_id' AND id != '$obj->id'";
        $old = new Galeria();
        if ($old->find_first($conditions)) {
            if ($method == 'create') {
                $obj->id = $old->id;
                $method = 'update';
            } else {
                Flash::info('Ya existe una foto registrada bajo esos parámetros.<br>' . $obj->mpn);
                return FALSE;
            }
        }
        $rs = $obj->$method();
        if ($rs) {
            ($method == 'create') ? DwAudit::debug("Se ha registrado una imagen codigo: $obj->mpn, id $obj->id") : DwAudit::debug("Se ha modificado la información de la imagen $obj->mpn, id $obj->id");
        }
        return ($rs) ? $obj : FALSE;
    }

    /**
     * Método que se ejecuta antes de guardar y/o modificar     
     */
    public function before_save() {
        $this->mpn = filter_var(strtoupper($this->mpn), FILTER_SANITIZE_STRING);
    }

    /**
     * Callback que se ejecuta despues de sincronizar el catalogo
     */
    protected function after_update() {
        
    }

    /**
     * Método que devuelve las fotos paginadas o para un select
     * @param int $pag Número de página a mostrar.
     * @return ActiveRecord
     */
    public function getListadoGaleria($estado = 'todos', $order = '', $page = 0) {
        $colm = "galeria.*, empresa.siglas ";
        $join = 'INNER JOIN empresa ON empresa.id = galeria.empresas_id ';
        $cond = 'galeria.id IS NOT NULL ';
        $order = $this->get_order($order, 'galeria');
        if ($page) {
            return $this->paginated("columns: $colm", "join: $join", "conditions: $cond", "order: $order", "page: $page");
        }
        return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
    }

    /**
     * Método que devuelve las fotos para verificar en el catalogo
     * @param int $pag Número de página a mostrar.
     * @return ActiveRecord
     */
    public function getGaleriaCatalogo($empresas_id) {
        $colm = "galeria.id, empresas_id, mpn, imagen";
        $cond = "galeria.empresas_id = $empresas_id";
        return $this->find("columns: $colm", "conditions: $cond", "order: id");
    }

    /**
     * Método que devuelve las fotos paginadas o para un select
     * @param int $pag Número de página a mostrar.
     * @return ActiveRecord
     */
    public function getListadoGaleriaSinImagen($estado = 'todos', $order = '', $page = 0) {
        $colm = "galeria.*, empresa.siglas ";
        $join = 'INNER JOIN empresa ON empresa.id = galeria.empresas_id ';
        $join .= 'LEFT JOIN cat_master ON cat_master.mpn = galeria.mpn ';
        $cond = "cat_master.instock > 0 AND galeria.imagen IS NULL AND galeria.url_foto IS NULL ";
        $order = $this->get_order($order, 'galeria');
        if ($page) {
            return $this->paginated("columns: $colm", "join: $join", "conditions: $cond", "order: $order", "page: $page");
        }
        return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
    }

    /**
     * Método para obtener la información de un usuario
     * @return type
     */
    public function getInformacionGaleria($foto_id) {
        $foto_id = Filter::get($foto_id, 'int');
        if (!$foto_id) {
            return NULL;
        }
        $colm = "galeria.*, empresa.siglas ";
        $join = 'INNER JOIN empresa ON empresa.id = galeria.empresas_id ';
        $cond = "galeria.id = $foto_id";
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond");
    }

    /**
     * Método para buscar ciudades
     */
    public function getAjaxGaleria($field, $value, $order = '', $page = 0) {
        $value = Filter::get($value, 'string');
        if (strlen($value) <= 1 OR ( $value == 'none')) {
            return NULL;
        }
        $colm = "galeria.*, empresa.siglas ";
        $join = 'INNER JOIN empresa ON empresa.id = galeria.empresas_id ';
        $cond = 'galeria.id IS NOT NULL';
        $order = $this->get_order($order, 'id');
        if ($field == 'modelo') {
            $field = 'mpn';
        }
        //Defino los campos habilitados para la búsqueda
        $fields = array('mpn');
        if (!in_array($field, $fields)) {
            $field = 'mpn';
        }
        if (!($value == 'todas')) {
            $cond .= " AND $field LIKE '%$value%'";
        }
        if ($page) {
            return $this->paginated("columns: $colm", "join: $join", "conditions: $cond", "order: $order", "page: $page");
        } else {
            return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
        }
    }

    /**
     * Método para buscar la foto del producto
     */
    public function getFotoProducto($mpn) {
        return $this->find_by_mpn($mpn);
    }

    /**
     * Método para crear la foto del producto
     */
    public function getRegistraFoto($mpn, $empresa, $foto_url) {
        //Verifico si existe la galeria
        $galerias = (new Galeria())->find_by_mpn($mpn);

        //crea la galería
        $data = array();
        if (empty($galerias->id)) {
            $data['mpn'] = $mpn;
            $data['empresas_id'] = $empresa;
            $data['cuerpo'] = empty($foto_url['cuerpo']) ? NULL : $foto_url['cuerpo'];
            $data['url_foto'] = empty($foto_url['url_foto']) ? NULL : $foto_url['url_foto'];
            $data['imagen'] = empty($foto_url['imagen']) ? NULL : $foto_url['imagen'];
            $metodo = 'create';
        } else {
            $data['id'] = $galerias->id;
            $data['mpn'] = $galerias->mpn;
            $data['empresas_id'] = $empresa;
            $data['cuerpo'] = empty($foto_url['cuerpo']) ? $galerias->cuerpo : $foto_url['cuerpo'];
            $data['url_foto'] = empty($foto_url['url_foto']) ? $galerias->url_foto : $foto_url['url_foto'];
            $data['imagen'] = empty($foto_url['imagen']) ? $galerias->imagen : $foto_url['imagen'];
            $metodo = 'update';
        }
        if (!Galeria::setGaleria($metodo, NULL, $data)) {
            Flash::info('Se ha producido un error al crear la galeria...');
            return 'cancel';
        }
    }

}
