<?php

class Talonario extends ActiveRecord {

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
//        $this->has_many('sucursal');        
        $this->validates_presence_of('fecha_emision', 'message: Ingresa la fecha de emision');        
    }

    /**
     * Método para setear
     * 
     * @param array $data
     * @return
     */
    public static function setComprobantes($method, $data, $optData=null) {        
        $obj = new Comprobantes($data); //Se carga los datos con los de las tablas        
        if($optData) { //Se carga información adicional al objeto
            $obj->dump_result_self($optData);
        }
        if ($obj->fecha_emision){
            $obj->fecha_emision = DwOnline::checkFechaDocumentoElectronico($obj->fecha_emision);
        }
        //Verifico que no exista otra Comprobantes, y si se encuentra inactivo lo active
        $conditions = empty($obj->id) ? "serie='$obj->serie' AND numer_secuencial='$obj->numer_secuencial'" : "serie='$obj->serie' AND numer_secuencial='$obj->numer_secuencial' AND id != '$obj->id'";
        $old = new Comprobantes();
        if($old->find_first($conditions)) {            
            if($method=='create') {
//                $obj->id = $old->id;
//                $method = 'update';
            } else {
                Flash::info('Ya existe un comprobante registrado bajo esos parámetros.');
                return FALSE;
            }
        }
        return ($obj->$method()) ? $obj : FALSE;
    }
    
    /**
     * Método que devuelve las ciudades paginadas o para un select
     * @param int $pag Número de página a mostrar.
     * @return ActiveRecord
     */
    public function getListadoComprobantes($estado='todos', $order='', $page=0) {                   
        $conditions = 'comprobantes.fecha_emision IS NOT NULL';                
        $order = $this->get_order($order, 'fecha_emision');         
        if($page) {            
            return $this->paginated("conditions: $conditions", "order: $order", "page: $page");
        }
        return $this->find("conditions: $conditions", "order: $order");
    }
 
    /**
     * Método para buscar ciudades
     */
    public function getAjaxComprobantes($field, $value, $order='', $page=0) {
        $value = Filter::get($value, 'string');
        if( strlen($value) <= 1 OR ($value=='none') ) {
            return NULL;
        }
        $columns = "comprobantes.*";
        $conditions = "comprobantes.activo != 'NULL' ";
        
        $order = $this->get_order($order, 'id'); 
        //Defino los campos habilitados para la búsqueda
        $fields = array('fecha_emision', 'ruc');
        if(!in_array($field, $fields)) {
            $field = 'fecha_emision';
        }        
        if(! ($value=='todas') ) {
            $conditions.= " AND $field LIKE '%$value%'";
        }        
        if($page) {
            return $this->paginated("columns: $columns", "conditions: $conditions", "order: $order", "page: $page");
        } else {
            return $this->find("columns: $columns", "conditions: $conditions", "order: $order");
        }  
    }

    /**
     * Método para obtener las ciudades como json
     * @return type
     */
    public function getCiudadesToJson() {
        $rs =  $this->find("columns: comprobantes", 'group: comprobantes', 'order: comprobantes ASC');
        $ciudades = array();
        foreach($rs as $comprobantes) {            
            $ciudades[] = $comprobantes->comprobantes; 
        }
        return json_encode($ciudades);
    }
    
}