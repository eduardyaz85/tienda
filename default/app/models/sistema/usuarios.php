<?php

/**
 *
 * Descripcion: Modelo para el manejo de usuarios
 *
 * @category
 * @package     Models
 */
Load::models('sistema/estado_usuario', 'sistema/perfil', 'sistema/recurso', 'sistema/recurso_perfil', 'sistema/acceso', 'utils/correo', 'utils/misc');

class Usuarios extends ActiveRecord {

    //Se desabilita el logger para no llenar el archivo de "basura"
    public $logger = false;

    /**
     * Método para definir las relaciones y validaciones
     */
    protected function initialize() {
        $this->belongs_to('perfil');
        $this->has_many('estado_usuario');
    }

    /**
     * Método que devuelve el inner join con el estado_usuario
     * @return string
     */
    public static function getInnerEstado() {
        return "INNER JOIN ( SELECT estado_usuario.usuarios_id, estado_usuario.descripcion, estado_usuario.estado_usuario_at, estado_usuario.estado_usuario FROM estado_usuario GROUP BY estado_usuario.usuarios_id DESC ) eu ON eu.usuarios_id = usuarios.id ";
    }

    /**
     * Método para abrir y cerrar sesión
     * @param type $opt
     * @return boolean
     */
    public static function setSession($opt = 'open', $user = NULL, $pass = NULL, $mode = NULL) {
        if ($opt == 'close') { //Cerrar Sesión
            $usuario = Session::get('id');
            $nombre = Session::get('nombre');
            if (DwAuth::logout()) {
                Flash::valid("¡ Regresa pronto <strong>" . $nombre . "</strong> !.");
                //Registro la salida
                Acceso::setAcceso(Acceso::SALIDA, $usuario);
                return TRUE;
            }
            Flash::error(DwAuth::getError());
        } else if ($opt == 'open') { //Abrir Sesión
            if (DwAuth::isLogged()) {
                return TRUE;
            } else {
                if (DwForm::isValidToken()) { //Si el formulario es válido
                    if (DwAuth::login(array('login' => $user), array('password' => $pass), $mode)) {
                        $usuario = self::getUsuarioLogueado();
                        if ($usuario->perfil_id != Perfil::SUPER_USUARIO && ($usuario->estado_usuario != EstadoUsuario::ACTIVO)) {
                            DwAuth::logout();
                            Flash::error('Lo sentimos pero tu cuenta se encuentra inactiva. <br />Si esta información es incorrecta contacta al administrador del sistema.');
                            return false;
                        }

                        Session::set("ip", DwUtils::getIp());
                        Session::set('perfil', $usuario->perfil);
                        Session::set('nombre', $usuario->nombre);
                        Session::set('foto', $usuario->fotografia);
                        Session::set('sucursal', $usuario->sucursal);
                        Session::set('razon_social', $usuario->razon_social);
                        Session::set('ruc', $usuario->ruc);
                        Session::set('empresas_id', $usuario->empresas_id);
                        //Registro el acceso
                        Acceso::setAcceso(Acceso::ENTRADA, $usuario->id);
                        Flash::valid("¡ Bienvenido <strong>$usuario->nombre $usuario->apellido</strong> !.");
                        return TRUE;
                    } else {
                        Flash::error(DwAuth::getError());
                    }
                } else {
                    Flash::info('La llave de acceso ha caducado. <br />Por favor ' . Html::link('dashboard/login/entrar/', 'recarga la página <b>aquí</b>'));
//                    Flash::info('La llave de acceso ha caducado. <br />Por favor ');
                }
            }
        } else {
            Flash::error('No se ha podido establecer la sesión actual.');
        }
        return FALSE;
    }

    /**
     * Método para abrir y cerrar sesión
     * @param type $opt
     * @return boolean
     */
    public static function setSessionCliente($opt = 'open', $user = NULL, $pass = NULL, $mode = NULL) {
        if ($opt == 'close') { //Cerrar Sesión
            $usuario = Session::get('id');
            if (DwAuth::logout()) {
                //Registro la salida
                Acceso::setAcceso(Acceso::SALIDA, $usuario);
                return TRUE;
            }
            Flash::error(DwAuth::getError());
        } else if ($opt == 'open') { //Abrir Sesión
            if (DwAuth::isLogged()) {
                return TRUE;
            } else {
                if (DwForm::isValidToken()) { //Si el formulario es válido
                    if (DwAuth::login(array('login' => $user), array('password' => $pass), $mode)) {
                        $usuario = self::getUsuarioLogueado();
                        if ($usuario->perfil_id != Perfil::SUPER_USUARIO && ($usuario->estado_usuario != EstadoUsuario::ACTIVO)) {
                            DwAuth::logout();
                            Flash::error('Lo sentimos pero tu cuenta se encuentra inactiva. <br />Activar la cuenta desde en link enviado a su email.');
                            return FALSE;
                        }

                        Session::set("ip", DwUtils::getIp());
                        Session::set('perfil', $usuario->perfil);
                        Session::set('nombre', $usuario->nombre);
                        Session::set('foto', $usuario->fotografia);
                        //Registro el acceso
                        Acceso::setAcceso(Acceso::ENTRADA, $usuario->id);
                        Flash::valid("¡ Bienvenido <strong>$usuario->nombre $usuario->apellido</strong> !.");
                        return TRUE;
                    } else {
                        Flash::error(DwAuth::getError());
                    }
                } else {
                    Flash::info('La llave de acceso ha caducado. <br />Por favor ' . Html::link('dashboard/login/', 'recarga la página <b>aquí</b>'));
//                    Flash::info('La llave de acceso ha caducado. <br />Por favor ');
                }
            }
        } else {
            Flash::error('No se ha podido establecer la sesión actual.');
        }
        return FALSE;
    }

    /**
     * Método para obtener la información de un usuario logueado
     * @return object Usuario
     */
    public static function getUsuarioLogueado() {
        $columnas = 'usuarios.*, perfil.perfil, eu.estado_usuario, empresas.razon_social, empresas.ruc, establecimientos.sucursal ';
        $join = "INNER JOIN perfil ON perfil.id = usuarios.perfil_id ";
        $join .= 'LEFT JOIN establecimientos ON establecimientos.id = usuarios.establecimientos_id ';
        $join .= 'LEFT JOIN empresas ON empresas.id = establecimientos.empresas_id ';
        $join .= self::getInnerEstado();
        $conditions = "usuarios.id = '" . Session::get('id') . "'";
        $obj = new Usuarios();
        return $obj->find_first("columns: $columnas", "join: $join", "conditions: $conditions");
    }

    /**
     * Método para obtener la información de un usuario logueado
     * @return object Usuario
     */
    public static function getUsuarioAutoriza($username) {
        $columnas = 'usuarios.*, perfil.perfil, eu.estado_usuario';
        $join = "INNER JOIN perfil ON perfil.id = usuarios.perfil_id ";
        $join .= self::getInnerEstado();
        $conditions = "usuarios.login = '$username'";
        $obj = new Usuarios();
        return $obj->find_first("columns: $columnas", "join: $join", "conditions: $conditions");
    }

    /**
     * Método para listar los usuario por perfil
     */
    public function getUsuarioPorPerfil($perfil, $order = 'order.nombre.asc', $page = 0) {
        $perfil = Filter::get($perfil, 'int');
        if (empty($perfil)) {
            return NULL;
        }
        $columns = 'usuarios.*, perfil.perfil';
        $join = 'INNER JOIN perfil ON perfil.id = usuarios.perfil_id ';
        $conditions = "perfil.id = $perfil";

        $order = $this->get_order($order, 'nombre', array(
            'login' => array(
                'ASC' => 'usuarios.login ASC, usuarios.nombre ASC, usuarios.apellido DESC',
                'DESC' => 'usuarios.login DESC, usuarios.nombre DESC, usuarios.apellido DESC'
            ),
            'nombre' => array(
                'ASC' => 'usuarios.nombre ASC, usuarios.apellido DESC',
                'DESC' => 'usuarios.nombre DESC, usuarios.apellido DESC'
            ),
            'apellido' => array(
                'ASC' => 'usuarios.apellido ASC, usuarios.nombre ASC',
                'DESC' => 'usuarios.apellido DESC, usuarios.nombre DESC'
            ),
            'email' => array(
                'ASC' => 'usuarios.email ASC, usuarios.apellido ASC, usuarios.nombre ASC',
                'DESC' => 'usuarios.email DESC, usuarios.apellido DESC, usuarios.nombre DESC'
            ),
            'estado_usuario' => array(
                'ASC' => 'estado_usuario.estado_usuario ASC, usuarios.apellido ASC, usuarios.nombre ASC',
                'DESC' => 'estado_usuario.estado_usuario DESC, usuarios.apellido DESC, usuarios.nombre DESC'
            )
        ));

        if ($page) {
            return $this->paginated("columns: $columns", "join: $join", "conditions: $conditions", "order: $order", "page: $page");
        }
        return $this->find("columns: $columns", "join: $join", "conditions: $conditions", "order: $order");
    }

    /**
     * Método para buscar usuario
     */
    public function getAjaxUsuario($field, $value, $order = '', $page = 0) {
        $value = Filter::get($value, 'string');
        if (strlen($value) <= 2 OR ( $value == 'none')) {
            return NULL;
        }
        $columns = 'usuarios.*, perfil.perfil, eu.estado_usuario, eu.descripcion';
        $join = self::getInnerEstado();
        $join .= 'INNER JOIN perfil ON perfil.id = usuarios.perfil_id ';
        $conditions = "usuarios.perfil_id != " . Perfil::SUPER_USUARIO; //Por el super usuarios

        $order = $this->get_order($order, 'nombre', array(
            'login' => array(
                'ASC' => 'usuarios.login ASC, usuarios.nombre ASC, usuarios.apellido DESC',
                'DESC' => 'usuarios.login DESC, usuarios.nombre DESC, usuarios.apellido DESC'
            ),
            'nombre' => array(
                'ASC' => 'usuarios.nombre ASC, usuarios.apellido DESC',
                'DESC' => 'usuarios.nombre DESC, usuarios.apellido DESC'
            ),
            'apellido' => array(
                'ASC' => 'usuarios.apellido ASC, usuarios.nombre ASC',
                'DESC' => 'usuarios.apellido DESC, usuarios.nombre DESC'
            ),
            'email' => array(
                'ASC' => 'usuarios.email ASC, usuarios.apellido ASC, usuarios.nombre ASC',
                'DESC' => 'usuarios.email DESC, usuarios.apellido DESC, usuarios.nombre DESC'
            ),
            'estado_usuario' => array(
                'ASC' => 'estado_usuario.estado_usuario ASC, usuarios.apellido ASC, usuarios.nombre ASC',
                'DESC' => 'estado_usuario.estado_usuario DESC, usuarios.apellido DESC, usuarios.nombre DESC'
            )
        ));

        //Defino los campos habilitados para la búsqueda
        $fields = array('login', 'nombre', 'apellido', 'email', 'perfil', 'estado_usuario');
        if (!in_array($field, $fields)) {
            $field = 'nombre';
        }

        $conditions .= " AND $field LIKE '%$value%'";

        if ($page) {
            return $this->paginated("columns: $columns", "join: $join", "conditions: $conditions", "order: $order", "page: $page");
        } else {
            return $this->find("columns: $columns", "join: $join", "conditions: $conditions", "order: $order");
        }
    }

    public function getListadoUsuario($estado, $order = '', $page = 0) {
        $columns = 'usuarios.*, perfil.perfil, eu.estado_usuario, eu.descripcion, sucursal, ciudad ';
        $join = self::getInnerEstado();
        $join .= 'INNER JOIN perfil ON perfil.id = usuarios.perfil_id ';
        $join .= 'LEFT JOIN establecimientos ON establecimientos.id = usuarios.establecimientos_id ';
        $join .= 'LEFT JOIN ciudad ON ciudad.id = establecimientos.ciudad_id ';
        $conditions = "usuarios.perfil_id != " . Perfil::SUPER_USUARIO; //Por el super usuarios

        $order = $this->get_order($order, 'nombre', array(
            'login' => array(
                'ASC' => 'usuarios.login ASC, usuarios.nombre ASC, usuarios.apellido DESC',
                'DESC' => 'usuarios.login DESC, usuarios.nombre DESC, usuarios.apellido DESC'
            ),
            'nombre' => array(
                'ASC' => 'usuarios.nombre ASC, usuarios.apellido DESC',
                'DESC' => 'usuarios.nombre DESC, usuarios.apellido DESC'
            ),
            'apellido' => array(
                'ASC' => 'usuarios.apellido ASC, usuarios.nombre ASC',
                'DESC' => 'usuarios.apellido DESC, usuarios.nombre DESC'
            ),
            'email' => array(
                'ASC' => 'usuarios.email ASC, usuarios.apellido ASC, usuarios.nombre ASC',
                'DESC' => 'usuarios.email DESC, usuarios.apellido DESC, usuarios.nombre DESC'
            ),
            'estado_usuario' => array(
                'ASC' => 'estado_usuario.estado_usuario ASC, usuarios.apellido ASC, usuarios.nombre ASC',
                'DESC' => 'estado_usuario.estado_usuario DESC, usuarios.apellido DESC, usuarios.nombre DESC'
            )
        ));

        if ($estado == 'activos') {
            $conditions .= " AND estado_usuario.estado_usuario = '" . EstadoUsuario::ACTIVO . "'";
        } else if ($estado == 'bloqueados') {
            $conditions .= " AND estado_usuario.estado_usuario = '" . EstadoUsuario::BLOQUEADO . "'";
        }

        if ($page) {
            return $this->paginated("columns: $columns", "join: $join", "conditions: $conditions", "order: $order", "page: $page");
        } else {
            return $this->find("columns: $columns", "join: $join", "conditions: $conditions", "order: $order");
        }
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
    public static function setUsuario($method, $data, $optData = null) {
        $obj = new Usuarios($data);
        if ($optData) {
            $obj->dump_result_self($optData);
        }
//        DwOnline::pr($obj);
//        die();
        if ($obj->cliente == 's' && $method == 'create') {
            $obj->repassword = $obj->password;
            //verifico el codigo de referidos
            if (!empty($obj->pool)) {
                $existe = (new Usuarios)->find_by_pool(trim($obj->pool));
                if (empty($existe)) {
                    Flash::error("<b>El c&oacute;digo $obj->pool de referido es incorrecto o no Existe...</b>");
                    return false;
                }
                $obj->referido_id = empty($existe->id) ? '' : $existe->id;
            }

            $obj->existe = $obj->getVerificaCorreo($obj->email);

            if (empty($obj->existe)) {
                Flash::error('<b>El email ingresado no es válido</b>');
                return FALSE;
            }
        }

        if (!empty($obj->id)) { //Si va a actualizar
            $old = new Usuarios();
            $old->find_first($obj->id);
            if (!empty($obj->oldpassword)) { //Si cambia de claves
                if (empty($obj->password) OR empty($obj->repassword)) {
                    Flash::error("Indica la nueva contraseña");
                    return false;
                }
                $obj->oldpassword = sha1($obj->oldpassword);
                if ($obj->oldpassword !== $old->password) {
                    Flash::error("La contraseña anterior no coincide con la registrada. Verifica los datos e intente nuevamente");
                    return false;
                }
            }
            if (!empty($obj->nueva_clave)) { //Si cambia de clave el cliente
                if (empty($obj->password) OR empty($obj->repassword)) {
                    Flash::error("Indica la nueva contraseña");
                    return false;
                }
            }
        }
        //Verifico si las contraseñas coinciden (password y repassword)
        if ((!empty($obj->password) && !empty($obj->repassword) ) OR ( $method == 'create')) {
            if ($method == 'create' && (empty($obj->password))) {
                Flash::error("Indica la contraseña para el inicio de sesión");
                return false;
            }

            $obj->password = sha1($obj->password);
            $obj->repassword = sha1($obj->repassword);
            if ($obj->password !== $obj->repassword) {
                Flash::error('Las contraseñas no coinciden. Verifica los datos e intenta nuevamente.');
                return 'cancel';
            }
        } else {
            if (isset($obj->id)) { //Mantengo la contraseña anterior
                $obj->password = $old->password;
            }
        }
//        DwOnline::pr($obj);
//        die();
        $rs = $obj->$method();
        if ($rs) {
            ($method == 'create') ? DwAudit::debug("Se ha registrado el usuario $obj->login en el sistema") : DwAudit::debug("Se ha modificado la información del usuario $obj->login");
        }
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
    protected function before_save() {
        $this->email = strtolower($this->email);
        $this->nombre = Filter::get(strtoupper($this->nombre), 'string');
        $this->apellido = Filter::get(strtoupper($this->apellido), 'string');
        if (Session::get('perfil_id') != Perfil::SUPER_USUARIO) { //Solo el super usuario puede hacer esto
            //Verifico las exclusiones de los nombre de usuario del config.ini
            $exclusion = DwConfig::read('config', array('custom' => 'login_exclusion'));
            $exclusion = explode(',', $exclusion);
            if (!empty($exclusion)) {
                if (in_array($this->login, $exclusion)) {
                    Flash::error('El nombre de usuario indicado, no se encuentra disponible.');
                    return 'cancel';
                }
            }
        }
        //Verifico si el login está disponible
        if ($this->_getRegisteredField('login', $this->login, $this->id)) {
            Flash::error('El nombre de usuario no se encuentra disponible.');
            return 'cancel';
        }
        //Verifico si se encuentra el mail registrado
        if ($this->_getRegisteredField('email', $this->email, $this->id)) {
            Flash::error('El correo electrónico ya se encuentra registrado o no se encuentra disponible.');
            return 'cancel';
        }
        $this->datagrid = Filter::get($this->datagrid, 'int');
//        }
    }

    /**
     * Callback que se ejecuta despues de insertar un usuario
     */
    protected function after_create() {
        if (!EstadoUsuario::setEstadoUsuario('registrar', array('usuarios_id' => $this->id, 'descripcion' => 'Creado por registro inicial'))) {
            Flash::error('Se ha producido un error interno al activar el usuario. Pofavor intenta nuevamente.');
            return 'cancel';
        }

        //si es usuario cliente envio el email de activación
        /*        if ($this->cliente) {
          if ($this->activo == '0' && $this->reset == '') {
          if (!$this->activarCuentaCliente($this->id)) {
          return 'cancel';
          }
          }
          } */
    }

    /**
     * Callback que se ejecuta despues de insertar un usuario
     */
    protected function after_update() {
        if ($this->confirma_cliente == 'si') {
            if (!EstadoUsuario::setEstadoUsuario('activar', array('usuarios_id' => $this->id, 'descripcion' => 'Activado por confirmacion del cliente'))) {
                Flash::error('Se ha producido un error interno al activar el usuario. Pofavor intenta nuevamente.');
                return 'cancel';
            }
        }

        //si es usuario cliente envio el email de activación
        /* if ($this->cliente) {
          if ($this->activo == '0' && $this->reset == '') {
          if (!$this->activarCuentaCliente($this->id)) {
          return 'cancel';
          }
          }
          } */
    }

    /**
     * Método para obtener la información de un usuario
     * @return type
     */
    public function getInformacionUsuario($usuario) {
        $usuario = Filter::get($usuario, 'int');
        if (!$usuario) {
            return NULL;
        }
        $columnas = 'usuarios.*, perfil.perfil, eu.estado_usuario, eu.descripcion';
        $join = self::getInnerEstado();
        $join .= 'INNER JOIN perfil ON perfil.id = usuarios.perfil_id ';
        $condicion = "usuarios.id = $usuario";
        return $this->find_first("columns: $columnas", "join: $join", "conditions: $condicion");
    }

    /**
     * Envio activacion de cuenta por email
     */
    /*    public function activarCuentaCliente($usu_id) {
      $usuario = $this->find_first("id = $usu_id");
      if ($usuario) {
      if ($usuario->activo == '1') {
      Flash::warning("Este usuario ya activo su cuenta: $usuario->nombre $usuario->apellido");
      return FALSE;
      }
      $reset_clave = Misc::generarClave(33);
      $key_act = Security::setKey($usuario->id, 'act_cuenta');
      //Para el correo
      $host = Config::get('config.application.dominio');
      $email = Config::get('config.application.email');
      $nombre = Config::get('config.application.nombre');
      $url = '<a href="' . $host . "/cuenta/activar/$key_act/$reset_clave/" . '">Click Aqu&iacute; para Activar la Cuenta...</a>';

      //TODO que este contenido del correo lo tome de una plantilla.
      $correo = "<h4>¡Hola! $usuario->nombre $usuario->apellido.</h4>";
      $correo .= "<p>Un cordial saludo, somos <b>$nombre</b> una tienda de tecnología en línea, únicos con catálogo de productos nuevos, nuestro stock en línea disponible para entrega inmediata en la ciudad de Quito, otras ciudades de 24 a 48 horas.</p><br />";
      $correo .= "<p><b>¡Activa tu Cuenta!</b></p>";
      $correo .= "<p>¡Nos puedes confirmar que tenemos la dirección de email correcto!, para que puedas recibir notificaciones de tus compras.</p>";
      $correo .= "A) Click en el siguiente enlace:<br />";
      $correo .= "$url<br /><br />";
      $correo .= "B) Si no funciona, copia y pega la siguiente URL en tu navegador favorito.<br />";
      $correo .= "$host/cuenta/activar/$key_act/$reset_clave/<br /><br /><br />";
      $correo .= "<p>Estimad@ <b>$usuario->nombre</b>, tienes activo el siguiente beneficio.</p>";
      $correo .= "<b><span style='color:#000080;'><strong>¡Recibe Obsequios por referidos!</strong></span></b><br />";
      $correo .= "Te lo indicamos como puedes obtener el beneficio.<br /><br />";
      $correo .= "<b><span style='color:#000080;'>¿Conoces de alguien que está interesado en comprar productos de tecnología?</strong></span></b><br />";
      $correo .= "Invita a todos los que puedas a suscribirse en nuestra tienda en línea, por cada personas que invites acumulas $1.00 dólar a tu monedero, que puedes canjear por los siguientes accesorios (Audífonos, Mouse, Teclados, Parlantes y Cables). Monto máximo para el canje $50.00 dólares, válido hasta el 31 de diciembre del 2017.<br /><br />";
      $correo .= "<b><span style='color:#000080;'>¿Cómo puedo referir?</strong></span></b><br />";
      $correo .= "Puedes compartir nuestra tienda en línea en tus redes sociales, enviar vía correo electrónico o personalmente, sea a tus familiares, amigos, compañeros de trabajo, conocidos, etc.<br /><br />";
      $correo .= "<strong>¡Importante!, envía o entrega tu código a la persona antes del registro.</strong><br />";
      $correo .= "Al ingresar a tu cuenta busca el menú <strong>Mi Cuenta</strong>, para ver tu código <strong>Mi Código Referidos:</strong><br /><br />";
      $correo .= "<strong>Saludos</strong><br />&nbsp;<br />";
      $correo .= "Eduardo Y&aacute;nez<br /><br />";
      $correo .= "ASDIN, Distribuidor Directo de Tecnolog&iacute;a.<br />";
      $correo .= "<strong>Tel:</strong>&nbsp;(593)02 2-655-109&nbsp;| <strong>Cel:</strong> 0984022304&nbsp;| Equipos de Computaci&oacute;n, Impresi&oacute;n, Celulares y Electrodom&eacute;sticos.<br />";
      $correo .= "<strong>Email:</strong>&nbsp;<a href='mailto:mercadeo@asdinec.com' target='_blank'>mercadeo@asdinec.com</a>&nbsp;| <strong>Facebook:</strong><a href='http://www.facebook.com/asdinec' target='_blank'>www.facebook.com/asdinec&nbsp;</a> | <strong>Skype:</strong> asdinec | <strong>Web Store:</strong> <a href='http://www.asdinec.com'>www.asdinec.com</a><br />";
      $correo .= "<br /><br /><br />";
      $correo .= "¡Este email fue generado de forma automática!<br />";
      $correo .= "$usuario->nombre, si no realizaste la solicitud de cuenta nueva, por favor ignorarlo. Si necesitas más ayuda, por favor visítanos en $host/sad/faq/ o un email a $email.<br /><br />";
      $correo .= "<span style='font-size:10px;'><strong>MENSAJE LEGAL</strong><br />";
      $correo .= "La informaci&oacute;n contenida en este correo, y en los archivos electr&oacute;nicos adjuntos, es para uso exclusivo de sus destinatarios y puede ser considerada confidencial o privilegiada; raz&oacute;n por la cual, incurrir&aacute; en sanciones de car&aacute;cter legal quien(es) en provecho propio o ajeno o con perjuicio de otro, divulgue(n) o emplee(n) la informaci&oacute;n contenida en esta comunicaci&oacute;n. Si usted recibe este mensaje por error le solicitamos: notificar a su remitente, eliminarlo y abstenerse de copiar, imprimir, reenviar o utilizar cualquier otro mecanismo de uso o divulgaci&oacute;n (total o parcial) del mismo.</span><br />&nbsp;<br />";

      $usuario->reset = $reset_clave;
      if ($usuario->update()) {
      if (Correo::send($usuario->email, $usuario->nombre, "Bienvenido a $nombre, estas a un paso de activar tu cuenta.", $correo)) {
      return true;
      } else {
      return false;
      }
      } else {
      return false;
      }
      } else {
      Flash::error('El usuario con este email no existe.');
      }
      } */

    /**
     * Renviar activacion de cuenta por email
     */
    /*    public function reenviarActivacion($email_or_username) {
      $usuario = $this->findByEmail($email_or_username);
      if (!$usuario) {
      $usuario = $this->findByNick($email_or_username);
      }
      if ($usuario) {
      //envio correo para activar la cuenta
      return $this->activarCuentaCliente($usuario->id);
      }
      } */

    /**
     * Numero de Acciones
     */
    public function getListadoNumeroAcciones($estado = 'todos', $order = '', $page = 0) {
        $colm = "usuarios.*, COUNT(auditorias.id) as num_acciones ";
        $join = "LEFT JOIN auditorias ON usuarios.id = auditorias.usuarios_id";
        $conditions = 'usuarios.id > 2';
        $order = $this->get_order($order, 'login');
        $group = 'usuarios.' . join(',usuarios.', $this->fields);
        if ($page) {
            return $this->paginated("columns: $colm", "join: $join", "conditions: $conditions", "group: $group", "order: $order", "page: $page");
        }
        return $this->find("columns: $colm", "join: $join", "conditions: $conditions", "group: $group", "order: $order");
    }

    public function getNumeroAcciones($pagina = 1) {
        $cols = "usuarios.*, COUNT(auditorias.id) as num_acciones";
        $join = "LEFT JOIN auditorias ON usuarios.id = auditorias.usuarios_id";
        $group = 'usuarios.' . join(',usuarios.', $this->fields);
        $sql = "SELECT $cols FROM $this->source $join GROUP BY $group";
        return $this->paginate_by_sql($sql, "page: $pagina");
    }

    /**
     * Verifico el email
     */
    public function findByEmail($email) {
        return $this->find_first("email = '$email'");
    }

    /**
     * Método cargara informacion del usuario por medio del nombre
     */
    public function findByNick($username) {
        return $this->find_first("login = '$username'");
    }

    /**
     * Verifico el id del usuario
     */
    public function findById($id) {
        return $this->find_first("id = '$id'");
    }

    /**
     * Verifico que los datos del usuario sean completos
     */
    public function getVerificaDatosUsuario($data) {
        $obj = new Usuarios($data); //Se carga los datos con los de las tablas
//        DwOnline::pr($obj);
//        die();
        if (empty($obj->email)) {
            Flash::error("Indica el Correo para la cuenta");
            return false;
        }
        if (empty($obj->login)) {
            Flash::error("Indica el usuario");
            return false;
        }
        if (empty($obj->password)) {
            Flash::error("Indica la contraseña");
            return false;
        }
        //Verifico si el login está disponible
        if ($obj->_getRegisteredField('login', $obj->login, $obj->id)) {
            Flash::error('El nombre de usuario no se encuentra disponible.');
            return false;
        }
        //Verifico si se encuentra el mail registrado
        if ($obj->_getRegisteredField('email', $obj->email, $obj->id)) {
            Flash::error('El correo electrónico ya se encuentra registrado o no se encuentra disponible.');
            return false;
        }
//        DwOnline::pr($obj);
//        die();
        return TRUE;
    }

    /*
     * ========================================================================
     *  MANEJO CORREO
     * ======================================================================== 
     */

    /**
     * Verifica el correo
     */
    public function getVerificaCorreo($correo) {
        $domain = (new Usuarios)->findByEmail($correo);
        $valida = (new Misc)->isEmailToBan($correo);

        if (!empty($domain->correo)) {
            Flash::error('Lo sentimos, su correo ya se encuentra registrado<br>' . $domain->email);
            return FALSE;
        }

        if (empty($valida)) {
            Flash::error("<b>Ops! Lo siento mucho, no se pudo verificar tu correo.</b>");
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Resetear Clave Cliente
     */
    public function resetClaveByEmailOrUsername($email_or_username) {
        $usuario = $this->findByEmail($email_or_username);

        if (empty($usuario->email)) {
            Flash::error("El correo ingresado no existe o esta mal...");
            return FALSE;
        }

        $nombres = ucwords(strtolower($usuario->nombre));

        if (!empty($usuario->email)) {
            $config = Config::get('config');
            $reset_clave = Misc::generarClave(33);
            $key_cla = Security::setKey($usuario->id, 'cla_cuenta');
            $usuario->reset = $reset_clave;
            $asunto = "Cambiar Contraseña Cuenta " . $config['application']['name'];
            $dominio = $config['correo']['dominio'];
            $url = $dominio . "index/cambioclave/$key_cla/$reset_clave/";

            // Activa el almacenamiento en búfer de la salida
            ob_start();
            // Carga el contenido del partial
            View::partial('correo/recupera', '', ['url' => $url, 'dominio' => $dominio, 'data' => $usuario]);
            // Obtiene en $html el contenido del búfer actual y elimina el búfer de salida actual
            $body = ob_get_clean();

            if ($usuario->update()) {
                if (Correo::send($usuario->email, $nombres, $asunto, $body, $adjunto = NULL)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            Flash::error('No existe registro del email ingresado.');
        }
    }

    /**
     * Envia correo cliente registro
     */
    public function enviaEmailCliente($nombres, $correo) {
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
