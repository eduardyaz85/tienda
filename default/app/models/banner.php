<?php

class Banner extends ActiveRecord {

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

    public function initialize() {
//        $this->has_many('subcategoria');
    }

    /**
     * Método para setear un Objeto
     * @param string    $method     Método a ejecutar (create, update)
     * @param array     $data       Array para autocargar el objeto
     * @param array     $optData    Array con con datos adicionales para autocargar
     */
    public static function setBanner($method, $data = array(), $optData = array()) {
        $obj = new Banner($data); //Se carga los datos con los de las tablas        
        if ($optData) { //Se carga información adicional al objeto
            $obj->dump_result_self($optData);
        }
        //Verifico que no exista otra Banner, y si se encuentra inactivo lo active
        $conditions = empty($obj->id) ? "label1='$obj->label1' " : "label1='$obj->label1' AND id != '$obj->id'";
        $old = new Banner();
        if ($old->find_first($conditions)) {
            if ($method == 'create' && $old->activo != Banner::ACTIVO) {
                $obj->id = $old->id;
                $obj->activo = Banner::ACTIVO;
                $method = 'update';
            } else {
                DwMessage::info('Ya existe un Banner registrado bajo esos parámetros.');
                return FALSE;
            }
        }
        return ($obj->$method()) ? $obj : FALSE;
    }

    /**
     * Método listar las fotos del banner en el admin
     */
    public function getListaBanner($estado = 'todos', $order = '', $page = 0) {
        $colm = 'banner.*';
        $cond = "banner.id != 'NULL' ";
        if ($estado != 'todos') {
            $cond .= ($estado == self::ACTIVO) ? " AND activo=" . self::ACTIVO : " AND activo=" . self::INACTIVO;
        }
        $order = $this->get_order($order, 'orden DESC');
        if ($page) {
            return $this->paginated("columns: $colm", "conditions: $cond", "order: $order", "page: $page");
        }
        return $this->find("columns: $colm", "conditions: $cond", "order: $order");
    }

    /**
     * Método cargar las fotos del banner
     */
    public function getBannerIndex() {
        $colum = "banner.*";
        $condi = date('Ymd') . " BETWEEN banner.f_inicio AND banner.f_fin";
        return $this->find("columns: $colum", "conditions: $condi", 'order: orden DESC');
    }

    /**
     * Método eliminar las fotos del banner del directorio raiz
     */
    public function borrarBannerFoto($id) {
        $this->getFoto($id);
        $direc = "/public/img/upload/banner";
        if (file_exists(dirname(APP_PATH) . "" . $direc . "/" . $this->imagen)) {
            if (unlink(dirname(APP_PATH) . "" . $direc . "/" . $this->imagen)) {
                return $this->delete($id);
            }
        }
    }

}