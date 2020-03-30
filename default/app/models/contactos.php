<?php

/**
 * Asdin - Web | App
 *
 * Descripcion: Modelo encargado de registrar los contactos de empresas
 *
 * @category    
 * @package     Models 
 * @author      Eduardo Yánez (asdin_shop@yahoo.com)
 * @copyright   Copyright (c) 2019 Asdin
 * @revision    1.0
 */
class Contactos extends ActiveRecord {

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
        $this->has_one('personas');
    }

    /**
     * Método para obtener la información de un contacto
     * @return type
     */
    public function getInformacionContactoCliente($cliente) {
        $cliente = Filter::get($cliente, 'int');
        if (!$cliente) {
            return NULL;
        }
        $colm = 'contactos.*';
        $join = 'INNER JOIN personas ON personas.id = contactos.tipo_contacto ';
        $cond = "contactos.tipo_contacto = $cliente";
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond");
    }

    /**
     * Método para obtener la información de un contacto
     * @return type
     */
    public function getInformacionContactos($cliente) {
        $cliente = Filter::get($cliente, 'int');
        if (!$cliente) {
            return NULL;
        }
        $colm = 'contactos.*';
        $join = 'INNER JOIN personas ON personas.id = contactos.tipo_contacto ';
        $cond = "contactos.id = $cliente";
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond");
    }

    /**
     * Método para obtener la información de los contactos
     * @return type
     */
    public function getInformacionContactosPorTipo($estado = 'todos', $order = '', $obj = '', $tipo = '', $page = 0) {
        $obj = Filter::get($obj, 'int');
        $colum = 'contactos.* ';
        $join = 'INNER JOIN empresas ON empresas.id = contactos.empresas_id ';
        $cond = "contactos.empresas_id = $obj ";
        $order = $this->get_order($order, 'apellido');
        if ($page) {
            return $this->paginated("columns: $colum", "join: $join", "conditions: $cond", "order: $order", "page: $page");
        }
        return $this->find("columns: $colum", "join: $join", "conditions: $cond", "order: $order");
    }

    /**
     * Método para crear/modificar un objeto de base de datos
     * 
     * @param string $medthod: create, update
     * @param array $data: Data para autocargar el modelo
     * @param array $otherData: Data adicional para autocargar
     * 
     * @return object ActiveRecord
     */
    public static function setContacto($method, $data, $optData = null) {
        $obj = new Contactos($data); //Se carga los datos con los de las tablas        
        if ($optData) { //Se carga información adicional al objeto
            $obj->dump_result_self($optData);
        }
        //Verifico que no exista otro contacto, y si se encuentra inactivo lo active
        $conditions = empty($obj->id) ? "empresas_id='$obj->empresas_id' AND nombre='$obj->nombre' AND apellido='$obj->apellido'" : "empresas_id='$obj->empresas_id' AND nombre='$obj->nombre' AND apellido='$obj->apellido' AND id != '$obj->id'";
        $old = new Contactos();
        if ($old->find_first($conditions)) {
            if ($method == 'create') {
                $obj->id = $old->id;
                $method = 'update';
            } else {
                Flash::info('Ya existe un contacto registrado bajo esos parámetros. id ' . $obj->id);
                return FALSE;
            }
        }

        $rs = $obj->$method();
        if ($rs) {
            ($method == 'create') ? DwAudit::debug("Se ha registrado el contacto: $obj->nombre $obj->apellido") : DwAudit::debug("Se ha modificado la información del contacto: $obj->nombre $obj->apellido");
        }
//        DwOnline::pr($obj);
//        die();
        return ($rs) ? $obj : FALSE;
    }

    /**
     * Método para verificar si existe un campo registrado
     */
    protected function _getRegisteredField($field, $value, $id = NULL) {
        $conditions = "$field = '$value'";
        $conditions .= (!empty($id)) ? " AND id != $id" : '';
        return $this->count("conditions: $conditions");
    }

    /**
     * Callback que se ejecuta antes de guardar/modificar
     */
    public function before_save() {
        //Verifico si se encuentra el mail registrado
        if ($this->_getRegisteredField('email', $this->email, $this->id)) {
            Flash::error('El correo electrónico ya se encuentra registrado.');
            return 'cancel';
        }
        $this->nombre = Filter::get(strtoupper($this->nombre), 'string');
        $this->apellido = Filter::get(strtoupper($this->apellido), 'string');
        $this->abreviatura = Filter::get(strtoupper($this->abreviatura), 'string');
        $this->celular = Filter::get($this->celular, 'numeric');
        $this->email = strtolower($this->email);
        $this->email2 = strtolower($this->email2);
    }

    /**
     * Callback que se ejecuta despues de crear un contacto
     */
    /*    public function after_create() {        
      //Callback que crea automáticamente el cliente
      if (class_exists('Clientes', FALSE)) { //Verifico si existe cargado el modelo "Cliente"
      $cliente = new Clientes();
      if(!$cliente->count("conditions: persona_id = $this->id")) {
      Clientes::setCliente('create', array('persona_id'=>$this->id));
      }
      }
      } */
}
