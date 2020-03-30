<?php

/**
 * Asdin - Web | App
 *
 * Descripcion: Modelo encargado de registrar las empresas
 *
 * @category    
 * @package     Models 
 * @author      Eduardo Yánez (asdin_shop@yahoo.com)
 * @copyright   Copyright (c) 2019 Asdin
 * @revision    1.0
 */
class Empresas extends ActiveRecord {

    //Se desabilita el logger para no llenar el archivo de "basura"
    public $logger = FALSE;

    /**
     * Constante para definir un recurso como activo
     */
    const EMPRESA = 'e';

    /**
     * Constante para definir un recurso como activo
     */
    const PROVEEDOR = 'p';

    /**
     * Constante para definir un recurso como activo
     */
    const CLIENTE = 'c';

    /**
     * Método para definir las relaciones y validaciones
     */
    protected function initialize() {
//        $this->has_many('sucursal');
//        $this->validates_presence_of('razon_social', 'message: Ingresa el nombre de la empresa');
//        $this->validates_presence_of('legal', 'message: Ingresa el nombre del propietario o representante legal.');
//        $this->validates_presence_of('ruc', 'message: Ingresa el RUC de la empresa.');
//        $this->validates_email_in('email', 'message: El correo electrónico es incorrecto.');
    }

    /**
     * Método para obtener la configuracion de la empresa
     * @return obj
     */
    public function getInformacionEmpresa($type = '') {
        $columnas = 'empresas.*, establecimientos.*, ciudad.ciudad, (tablas_tipos.codigo) AS codigo_ct, tablas_tipos.titulo ';
        $join = "INNER JOIN establecimientos ON establecimientos.empresas_id = empresas.id ";
        $join .= "LEFT JOIN tablas_tipos ON tablas_tipos.id = empresas.tipo_documento ";
        $join .= "LEFT JOIN ciudad ON ciudad.id = establecimientos.ciudad_id ";
        return $this->find_first("columns: $columnas", "join: $join", "conditions: empresas.type = '$type'", 'order: empresas.nombres DESC');
    }

    /**
     * Método para obtener la información de la empresa
     * @return array
     */
    public function getInformacionEmpresas($empresa_id) {
        $colm = 'empresas.*, establecimientos.ciudad_id, (establecimientos.id) id_sucursal, establecimientos.sucursal, establecimientos.direccion, establecimientos.telefono, establecimientos.telefono2, establecimientos.celular, establecimientos.email, establecimientos.email2, establecimientos.ext, establecimientos.ext2, ciudad.ciudad ';
        $join = 'LEFT JOIN establecimientos ON establecimientos.empresas_id = empresas.id ';
        $join .= 'LEFT JOIN ciudad ON ciudad.id = establecimientos.ciudad_id ';
        $cond = "empresas.id = $empresa_id ";
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond");
    }

    /**
     * Método para registrar y modificar los datos de la empresa
     * 
     * @param string $method Método para guardar en la base de datos (create, update)
     * @param array $data Array de datos para la autocarga de objetos
     * @param arraty $other Se utiliza para autocargar datos adicionales al objeto
     * @return Empresas
     */
    public static function setEmpresas($method, $data, $optData = null) {
        $obj = new Empresas($data);
        if ($optData) {
            $obj->dump_result_self($optData);
        }
//        DwOnline::pr($obj);
//        die();
        $rs = $obj->$method();
        return ($rs) ? $obj->getInformacionEmpresas($obj->id) : FALSE;
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
    protected function before_save() {
        if ($this->type != 'c') {
            //Verifico si la razon social está disponible
            if ($this->_getRegisteredField('razon_social', $this->razon_social, $this->id)) {
                Flash::error('La Razon Social no se encuentra disponible.');
                return 'cancel';
            }
        }
        //Verifico si se encuentra el ruc registrado
        if ($this->_getRegisteredField('ruc', $this->ruc, $this->id)) {
            Flash::error('El ruc ya se encuentra registrado o no se encuentra disponible.');
            return 'cancel';
        }
    }

    /**
     * Callback que se ejecuta después de guardar/crear
     */
    public function after_save() {
        if ($this->id && Input::post('sucursal')) {
//            DwOnline::pr('after_save');
//            DwOnline::pr($this);
//            die();
            $empresa = (new Empresas())->find_first($this->id);
            Establecimientos::setEstablecimientos('save', Input::post('sucursal'), array('empresas_id' => $this->id, 'type' => $empresa->type, 'estado' => Establecimientos::ACTIVO));
        }
    }

    /**
     * Método para filtrar la información de la empresa
     */
    public function getFiltradoEmpresa() {
        $this->razon_social = Filter::get(strtoupper($this->razon_social), 'string');
        $this->nombres = Filter::get(strtoupper($this->nombres), 'string');
        $this->representante = Filter::get(strtoupper($this->representante), 'string');
        $this->contabilidad = Filter::get(strtolower($this->contabilidad), 'string');
        $this->especial = Filter::get(strtolower($this->especial), 'string');
        $this->type = Filter::get(strtolower($this->type), 'string');
        $this->web = Filter::get(strtolower($this->web), 'string');
        $this->ruc = Filter::get($this->ruc, 'numeric');
        $this->credito = Filter::get($this->credito, 'numeric');
        $this->tiempo = Filter::get($this->tiempo, 'numeric');
    }

    /**
     * Método para listar los tipos de identificación
     * @return array
     */
    public function getListaEmpresas($tipo = '', $estado = 'todos', $order = '', $page = 0) {
        $columns = 'empresas.*, establecimientos.sucursal, establecimientos.direccion, establecimientos.telefono, establecimientos.telefono2, establecimientos.celular, establecimientos.email, establecimientos.ext, establecimientos.ext2, ciudad.ciudad ';
        $join = 'INNER JOIN establecimientos ON establecimientos.empresas_id = empresas.id ';
        $join .= 'LEFT JOIN ciudad ON ciudad.id = establecimientos.ciudad_id ';
        $conditions = "empresas.type = '$tipo'";
        if ($estado != 'todos') {
//            $conditions.= ($estado==self::ACTIVO) ? " AND activo=".self::ACTIVO : " AND activo=".self::INACTIVO;
        }
        $order = $this->get_order($order, 'razon_social');
        if ($page) {
            return $this->paginated("columns: $columns", "join: $join", "conditions: $conditions", "order: $order", "page: $page");
        }
        return $this->find("columns: $columns", "join: $join", "conditions: $conditions", "order: $order");
    }

    /**
     * Método para buscar empresas
     */
    public function getBuscaEmpresas($field, $value, $order = '', $page = 0, $tipo = 0) {
        $value = Filter::get($value, 'string');
        if (strlen($value) <= 2 OR ( $value == 'none')) {
            return NULL;
        }
        $colm = 'empresas.*, establecimientos.sucursal, establecimientos.direccion, establecimientos.telefono, establecimientos.telefono2, establecimientos.celular, establecimientos.email, establecimientos.ext, establecimientos.ext2, ciudad.ciudad ';
        $join = 'INNER JOIN establecimientos ON establecimientos.empresas_id = empresas.id ';
        $join .= 'LEFT JOIN ciudad ON ciudad.id = establecimientos.ciudad_id ';
        $cond = "empresas.type = '$tipo' ";

        $order = $this->get_order($order, 'razon_social');
        //Defino los campos habilitados para la búsqueda
        $cond .= " AND UPPER($field) LIKE '%$value%' OR UPPER(ruc) LIKE '%$value%' OR UPPER(razon_social) LIKE '%$value%' OR UPPER(nombres) LIKE '%$value%' ";

        if ($page) {
            return $this->paginated("columns: $colm", "join: $join", "conditions: $cond", "order: $order", "page: $page");
        } else {
            return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
        }
    }

    /**
     * getByRuc
     * busca id de la empresa con el ruc
     */
    public function getByRuc($ruc = '') {
        return $this->find_by_ruc($ruc);
    }

    /**
     * getByRuc
     * busca id de la empresa con el ruc
     */
    public function getByNombres($nombres) {
        $colm = 'empresas.id, empresas.nombres';
        $cond = "empresas.nombres = '$nombres' ";
        return $this->find_first("columns: $colm", "conditions: $cond");
    }

    /**
     * get empresa
     * Creamos la empresa / cliente
     */
    public function getGuardaDatosClientes($datos) {
        $empresa = new Empresas($datos); //Se carga los datos con los de las tablas
        //verificamos si existe el cliente
        $busca_cliente = (new Empresas)->find_by_ruc($empresa->ruc);

        if (empty($busca_cliente->id)) {
            $empresa->begin();
            //verificamos si el usuario indica que no se encuentra su ciudad
            $ciudad = 0;
            if (!empty($datos['new_ciudad'])) {
                $ciudad = (new Ciudad)->getVerificaCiudad($datos);
                $datos['ciudad_id'] = $ciudad;
            }
            $rs = Empresas::setEmpresas('create', $datos, ['contabilidad' => 'NO', 'type' => Empresas::CLIENTE, 'credito' => '0', 'tiempo' => '0']);
            if (!empty($rs->id)) {
                $datos['empresas_id'] = $rs->id;
                //Creamos datos de contacto Cliente
                $es = Establecimientos::setEstablecimientos('save', $datos, ['type' => $rs->type, 'estado' => Establecimientos::ACTIVO]);

                //Creamos la cuenta de usuario del Cliente
                if (!empty($rs->id) && !empty($datos['new_usuario'])) {
                    $datos['establecimientos_id'] = $es->id;
                    $datos['nombre'] = $rs->nombres;
                    $datos['perfil_id'] = Perfil::CLIENTE;

                    $us = Usuarios::setUsuario('create', $datos, ['email' => $datos['email3'], 'cliente' => 's']);
                    if (empty($us->id)) {
                        $empresa->rollback();
                        Flash::error('Se ha producido un error al registrar el usuario.');
                        return FALSE;
                    }
                }
            } else {
                $empresa->rollback();
                Flash::info('Se ha producido un error al registrar el Cliente ' . $datos['razon_social']);
                return FALSE;
            }
            $empresa->commit();
            $id_cliente = $rs;
            Logger::debug("************NEW CLIENTE*************+");
            Logger::debug('Nuevo: ' . $id_cliente->id . ' : ' . $id_cliente->ruc);
        } else {
            $busca_cliente->razon_social = $datos['razon_social'];
            $busca_cliente->nombres = $datos['nombres'];
            if (!$busca_cliente->update()) {
                Flash::error('Error al Verificar su informacion');
                return FALSE;
            }
            $establecimiento = (new Establecimientos)->find_by_empresas_id($busca_cliente->id);
            $establecimiento->direccion = $datos['direccion'];
            $establecimiento->email = $datos['email'];
            $establecimiento->telefono = $datos['telefono'];
            if (!$establecimiento->update()) {
                Flash::error('Error al Verificar la direccion');
                return FALSE;
            }

            $id_cliente = $busca_cliente;
            Logger::debug("************UPDATE CLIENTE*************+");
            Logger::debug('Existe: ' . $id_cliente->id . ' : ' . $id_cliente->ruc);
        }
        return $empresa->getInformacionEmpresas($id_cliente->id);
    }

}
