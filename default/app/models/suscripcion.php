<?php

/**
 * Asdin - Web | App
 *
 * Descripcion: Modelo encargado de registrar los correos para boletines
 *
 * @category    
 * @package     Models 
 * @author      Eduardo Yánez (asdin_shop@yahoo.com)
 * @copyright   Copyright (c) 2019 Asdin
 * @revision    1.0
 */
class Suscripcion extends ActiveRecord {

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
        
    }

    /**
     * Método para listar los correos
     * @return array
     */
    public function getListadoCorreos($estado = 'todos', $order = '', $page = 0) {
        $conditions = 'suscripcion.estado IS NOT NULL';
        if ($estado != 'todos') {
            $conditions .= ($estado == self::ACTIVO) ? " AND estado=" . self::ACTIVO : " AND estado=" . self::INACTIVO;
        }
        $order = $this->get_order($order, 'nombres');
        if ($page) {
            return $this->paginated("conditions: $conditions", "order: $order", "page: $page");
        }
        return $this->find("conditions: $conditions", "order: $order");
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
    public static function setSuscripcion($method, $data, $optData = NULL) {
        $obj = new Suscripcion($data);
        if ($optData) {
            $obj->dump_result_self($optData);
        }
//        DwOnline::pr($obj);
//        die();
        $obj->begin();
        if ($method != 'delete') {
            if (empty($obj->nombres)) {
                Flash::info('Ingresa Tu Nombre');
                return FALSE;
            }
            if (empty($obj->correo)) {
                Flash::info('Ingresa un correo');
                return FALSE;
            }

            $obj->existe = $obj->getVerificaCorreo($obj->correo);

            if (empty($obj->existe)) {
                Flash::info('El correo ingresado no es valido');
                return FALSE;
            }

            $old = (isset($obj->id)) ? $obj->count("correo='$obj->correo' AND id!= $obj->id") : $obj->count("correo='$obj->correo'");
            if ($old) {
                $obj->rollback();
                Flash::error('Ya existe un correo suscrito a nuestros boletines.<br>' . $obj->correo);
                return FALSE;
            }
        } else {
            //Valido el ID
            $obj->id = Filter::get($obj->id, 'int');
            if (empty($obj->id)) {
                $obj->rollback();
                Flash::error('No se ha podido establecer la informacion correo');
                return FALSE;
            }

            $obj->find_first("columns: suscripcion.*", "conditions: suscripcion.id = $obj->id");
        }
        $obj->commit();
//        DwOnline::pr($obj);
//        die();
        return ($obj->$method()) ? $obj : FALSE;
    }

    /**
     * Callback que se ejecuta antes de guardar/modificar
     */
    protected function before_save() {
        $this->correo = strtolower($this->correo);
    }

    /**
     * Callback que se ejecuta después de guardar/crear
     */
    public function after_save() {
        if ($this->estatus == 'a') {
            //Enviamos correo de bienvenida
            $correo = new Suscripcion();
            if ($correo->enviaEmailBienvenida($this->nombres, $this->correo)) {
                Flash::valid('<b>Revise su Correo!, gracias por su confianza.</b>');
            }
        }
    }

    /**
     * Método consultar el dominio
     */
    public function getInformacionDominio($correo) {
        $colum = "suscripcion.id, suscripcion.correo, suscripcion.dominio";
        $condi = "correo = '$correo' AND estado = " . Suscripcion::ACTIVO;
        return $this->find_first("columns: $colum", "conditions: $condi");
    }

    /**
     * Verifica el correo
     */
    public function getVerificaCorreo($correo) {
        $domain = (new Suscripcion)->getInformacionDominio($correo);
        $valida = (new Misc)->isEmailToBan($correo);

        if (!empty($domain->correo)) {
            Flash::info('Lo sentimos, su correo ya se encuentra registrado<br>' . $domain->correo);
            return FALSE;
        }

        if (empty($valida)) {
            Flash::error("<b>Ops! Lo siento mucho, no se pudo verificar tu correo.</b>");
            return FALSE;
        }
        return TRUE;
    }

    /*
     * ========================================================================
     *  MANEJO EXCEL
     * ======================================================================== 
     */

    /**
     * Método para registro de suscriptores en array masivo
     */
    public static function setGuardaExcelUpload($method, $data, $optData = null) {
        $obj = new Suscripcion($data);
        $correos = (new Suscripcion)->find();

        try {
            if (!empty($optData)) {
                //Registro los cat_temporal en la tabla temporal
                foreach ($optData as $key) {
                    $verifica = $obj->findCorreo($key['email'], $correos);

                    if (empty($verifica->id)) {
                        $obj->id = NULL;
                        $obj->nombres = $key['name'];
                        $obj->correo = $key['email'];
                        $obj->groups = $key['groups'];
                        $obj->estatus = 'i';
                        $obj->promo = '0';
                        $obj->nuevo = '0';
                        $obj->estado = '0';
                        if (!$obj->create()) {
                            Flash::error('Se ha producido un error interno al registrar la suscripcion');
                        }
                    }
                }
            }

            return TRUE;
        } catch (KumbiaException $e) {
            Flash::error($e->getMessage());
            return false;
        }
    }

    /**
     * Método para buscar el correo
     */
    public function findCorreo($jEmail, $correos) {
        $iGaleria = null;
        foreach ($correos as $data) {
            if (strtoupper($data->correo) === strtoupper($jEmail)) {
                $iGaleria = $data;
                break;
            }
        }
        return $iGaleria;
    }

    /*
     * ========================================================================
     *  MANEJO CORREO
     * ======================================================================== 
     */

    /**
     * Envia correo de bienvenida
     */
    public function enviaEmailBienvenida($nombres, $correo) {
        $nombres = ucwords(strtolower($nombres));

        if ($correo) {
            //TODO que este contenido del correo lo tome de una plantilla.
            $asunto = "Bienvenido $nombres, a nuestra Tienda en Linea";

            // Activa el almacenamiento en búfer de la salida
            ob_start();
            // Carga el contenido del partial
            View::partial('correo/suscripcion', '', ['actas' => $nombres, 'data' => $correo]);
            // Obtiene en $html el contenido del búfer actual y elimina el búfer de salida actual
            $body = ob_get_clean();

            if (Correo::send($correo, $nombres, $asunto, $body, $adjunto = NULL)) {
                return true;
            } else {
                return false;
            }
        } else {
            Flash::error('No existe registro del email ingresado.');
        }
    }

}
