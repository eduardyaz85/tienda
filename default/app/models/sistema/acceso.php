<?php

/**
 *
 * Descripcion: Clase que gestiona los accesos al sistema
 *
 * @category
 * @package     Models
 * @subpackage 
 */
class Acceso extends ActiveRecord {

    //Se desabilita el logger para no llenar el archivo de "basura"
    public $logger = FALSE;

    /**
     * Constante para definir el acceso como entrada
     * @var int
     */
    const ENTRADA = 1;

    /**
     * Constante para definir el acceso como salida
     * @var int
     */
    const SALIDA = 2;

    /**
     * Método para definir las relaciones y validaciones
     */
    protected function initialize() {
        $this->belongs_to('usuarios');
    }

    /**
     * Método para registrar un acceso
     * @param string $tipo Tipo de acceso acceso/salida
     * @param int $usuario Usuario que accede
     * @param string $ip  Dirección ip
     */
    public static function setAcceso($tipo, $usuario) {
        $usuario = Filter::get($usuario, 'numeric');
        $obj = new Acceso();
        $obj->usuarios_id = $usuario;
        $obj->ip = DwUtils::getIp();
        $obj->host = gethostbyaddr(DwUtils::getIp());
        $obj->tipo_acceso = ($tipo == Acceso::ENTRADA) ? 1 : 2;
        $obj->create();
    }

    /**
     * Método para obtener la información del ultimo acceso registrado
     * @return type
     */
    public function getInformacionAcceso($obj) {
        $obj = Filter::get($obj, 'int');
        if (!$obj) {
            return NULL;
        }
//        $hoy = date('H:i:s');
        //'20' BETWEEN HOUR(hora_inicio) AND HOUR(hora_fin)
        $colm = "acceso.*, usuarios.login ";
//        $join = self::getInnerInicioSession();
        $join = "INNER JOIN usuarios ON usuarios.id = acceso.usuarios_id ";
        //$base = date("H:i:s", strtotime('acceso.acceso_at'));
//        $cond = "acceso.usuarios_id = $obj AND acceso.tipo_acceso = 1 AND HOUR(02:00:00) BETWEEN HOUR($base) AND HOUR($hoy)";
        $cond = "usuarios_id = $obj ";
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond", "order: acceso_at desc", "limit: 1");
    }

    /**
     * 
     * Método para listar los accesos de los usuario     
     *       
     * @param int $usuario Identificador del usuario
     * @param string $tipo Tipo de acceso
     * @param string $order Método de ordenamiento
     * @param int $page Número de página
     * @return array ActiveRecord    
     */
    public function getListadoAcceso($usuario = NULL, $tipo = 'todos', $order = '', $page = 0) {
        $columns = 'acceso.*, usuarios.login, usuarios.nombre, usuarios.apellido';
        $join = 'INNER JOIN usuarios ON usuarios.id = acceso.usuarios_id ';
        $conditions = (empty($usuario)) ? "usuarios.id > '1'" : "usuarios.id=$usuario";

        $order = $this->get_order($order, 'acceso.acceso_at', array('fecha' => array(
                'ASC' => 'acceso.acceso_at ASC, usuarios.nombre ASC, usuarios.apellido ASC',
                'DESC' => 'acceso.acceso_at DESC, usuarios.nombre ASC, usuarios.apellido ASC'),
            'nombre' => array(
                'ASC' => 'usuarios.nombre ASC, usuarios.apellido ASC, acceso.acceso_at DESC',
                'DESC' => 'usuarios.nombre DESC, usuarios.apellido DESC, acceso.acceso_at DESC'),
            'apellido' => array(
                'ASC' => 'usuarios.nombre ASC, usuarios.apellido ASC, acceso.acceso_at DESC',
                'DESC' => 'usuarios.nombre DESC, usuarios.apellido DESC, acceso.acceso_at DESC'),
            'ip',
            'tipo_acceso' => array(
                'ASC' => 'acceso.tipo_acceso ASC, acceso.acceso_at DESC, usuarios.nombre ASC, usuarios.apellido ASC',
                'DESC' => 'acceso.tipo_acceso DESC, acceso.acceso_at DESC, usuarios.nombre DESC, usuarios.apellido DESC')));

        if ($tipo != 'todos') {
            $conditions .= ($tipo != self::ENTRADA) ? " AND acceso.tipo_acceso = " . self::ENTRADA : " AND acceso.tipo_acceso = " . self::SALIDA;
        }

        if ($page) {
            return $this->paginated("columns: $columns", "join: $join", "conditions: $conditions", "order: $order", "page: $page");
        } else {
            return $this->find("columns: $columns", "join: $join", "conditions: $conditions", "order: $order");
        }
    }

    /**
     * Método para buscar accesos
     * 
     * @param string $field Nombre del campo
     * @param string $value Valor del campo
     * @param string $order Orden
     * @param int $page Número de página
     * @return array ActiveRecord
     */
    public function getAjaxAcceso($field, $value, $order = '', $page = 0) {
        $value = Filter::get($value, 'string');
        if (strlen($value) <= 2 OR ( $value == 'none')) {
            return NULL;
        }

        $columns = 'acceso.*, IF(acceso.tipo_acceso=' . self::ENTRADA . ', "Entrada", "Salida") AS new_tipo, usuarios.login, usuarios.nombre, usuarios.apellido';
        $join = 'INNER JOIN usuarios ON usuarios.id = acceso.usuarios_id ';
        $conditions = "usuarios.id > '1'";

        $order = $this->get_order($order, 'acceso.acceso_at', array('fecha' => array(
                'ASC' => 'acceso.acceso_at ASC, usuarios.nombre ASC, usuarios.apellido ASC',
                'DESC' => 'acceso.acceso_at DESC, usuarios.nombre ASC, usuarios.apellido ASC'),
            'nombre' => array(
                'ASC' => 'usuarios.nombre ASC, usuarios.apellido ASC, acceso.acceso_at DESC',
                'DESC' => 'usuarios.nombre DESC, usuarios.apellido DESC, acceso.acceso_at DESC'),
            'apellido' => array(
                'ASC' => 'usuarios.nombre ASC, usuarios.apellido ASC, acceso.acceso_at DESC',
                'DESC' => 'usuarios.nombre DESC, usuarios.apellido DESC, acceso.acceso_at DESC'),
            'ip',
            'tipo_acceso' => array(
                'ASC' => 'acceso.tipo_acceso ASC, acceso.acceso_at DESC, usuarios.nombre ASC, usuarios.apellido ASC',
                'DESC' => 'acceso.tipo_acceso DESC, acceso.acceso_at DESC, usuarios.nombre DESC, usuarios.apellido DESC')));

        //Defino los campos habilitados para la búsqueda por seguridad
        $fields = array('fecha', 'nombre', 'apellido', 'tipo_acceso', 'ip');
        if (!in_array($field, $fields)) {
            $field = 'nombre';
        }

        if ($field == 'fecha') {
            $conditions .= " AND DATE(acceso.acceso_at) LIKE '%$value%'";
        } else if ($field == 'tipo_acceso') {
            $conditions .= " HAVING new_tipo LIKE '%$value%'";
        } else {
            $conditions .= " AND $field LIKE '%$value%'";
        }

        if ($page) {
            return $this->paginated_by_sql("SELECT $columns FROM $this->source $join WHERE $conditions ORDER BY $order", "page: $page");
        } else {
            return $this->find_all_by_sql("SELECT $columns FROM $this->source $join WHERE $conditions ORDER BY $order", "order: $order");
        }
    }

}

?>
