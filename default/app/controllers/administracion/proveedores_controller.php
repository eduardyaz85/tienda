<?php

class ProveedoresController extends BackendController {

    public $page_title = 'Proveedores';

    /**
     * Método que se ejecuta antes de cualquier acción
     */
    protected function before_filter() {
        //Setear nombre del módulo actual
        $this->modulo = Router::get('controller_path');
        //Se cambia el nombre del módulo actual
        $this->page_module = 'Gestión de Proveedores';
    }

    /**
     * Método principal
     */
    public function index() {
        Redirect::toAction('listar');
    }

    /**
     * Método para listar
     */
    public function listar($order = 'order.razon_social.asc', $page = 'pag.1') {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;

        $this->proveedores = (new Empresas)->getListaEmpresas($tipo = Empresas::PROVEEDOR, 'todos', $order, $page);

        $this->order = $order;
        $this->page_title = 'Listado';
    }

    /**
     * Método para buscar
     */
    public function buscar($field = 'razon_social', $value = 'none', $order = 'order.id.asc', $page = 1) {
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;
        $field = (Input::hasPost('field')) ? Input::post('field') : $field;
        $value = (Input::hasPost('value')) ? Input::post('value') : $value;

        if (empty($value)) {
            Flash::info('Ingrese un proveedor');
            return Redirect::toAction('listar');
        }

        $proveedor = new Empresas();
        $empresa = $proveedor->getBuscaEmpresas($field, $value, $order, $page, $tipo = Empresas::PROVEEDOR);
        if (empty($empresa->items)) {
            Flash::info('No se han encontrado registros');
        }
        $this->proveedores = $empresa;
        $this->order = $order;
        $this->field = $field;
        $this->value = $value;
        $this->page_title = 'Búsqueda';
    }

    /**
     * Método para agregar
     */
    public function agregar() {
        if (Input::hasPost('empresa') && Input::hasPost('sucursal')) {
            if (Empresas::setEmpresas('create', Input::post('empresa'), array('type' => Empresas::PROVEEDOR))) {
                Flash::valid('El proveedor se ha creado correctamente.');
                return Redirect::toAction('listar');
            }
        }
        $this->ciudades = (new Ciudad)->getCiudadesToJson();
        $this->page_title = 'Agregar';
    }

    /**
     * Método para agregar contactos del proveedor
     */
    public function contacto($key) {
        if (!$id = Security::getKey($key, 'add_contacto', 'int')) {
            return Redirect::toAction('listar');
        }

        $proveedor = new Empresas();
        if (!$proveedor->getInformacionEmpresas($id)) {
            Flash::error('No se pudo verificar la informacion del proveedor');
            return Redirect::toAction('listar');
        }
        if (Input::hasPost('contacto')) {
            if (Contactos::setContacto('create', Input::post('contacto'), array('empresas_id' => $proveedor->id, 'estado' => Contactos::ACTIVO))) {
                Flash::valid('El Contacto se ha creado correctamente.');
                $key = Security::setKey($proveedor->id, 'shw_proveedor');
                return Redirect::toAction("ver/$key/");
            }
        }
        $this->empresa = $proveedor;
        $this->page_title = 'Agregar Contacto';
    }

    /**
     * Método para editar
     */
    public function editar($key) {
        if (!$id = Security::getKey($key, 'upd_proveedor', 'int')) {
            return Redirect::toAction('listar');
        }

        $proveedor = new Empresas();
        if (!$proveedor->getInformacionEmpresas($id)) {
            Flash::error('No se pudo verificar la informacion del proveedor');
            return Redirect::toAction('listar');
        }

        if (Input::hasPost('empresa') && Input::hasPost('sucursal')) {
            if (Empresas::setEmpresas('update', Input::post('empresa'), array('id' => $proveedor->id))) {
                Flash::valid('El proveedor se ha actualizado correctamente!');
                return Redirect::toAction('listar');
            }
        }

        $this->sucursal = (new Establecimientos())->getInformacionEstablecimiento($proveedor->id);
        $this->empresa = $proveedor;
        View::select("agregar");
        $this->page_title = 'Actualizar';
    }

    /**
     * Método para editar
     */
    public function modifica($key) {
        if (!$id = Security::getKey($key, 'upd_contacto', 'int')) {
            return Redirect::toAction('listar');
        }

        $contactos = new Contactos();
        if (!$contactos->find_first($id)) {
            Flash::error('No se pudo verificar la informacion del proveedor');
            return Redirect::toAction('listar');
        }

        if (Input::hasPost('contacto')) {
            if (Contactos::setContacto('update', Input::post('contacto'), array('id' => $id))) {
                $key = Security::setKey($contactos->empresas_id, 'shw_proveedor');
                Flash::valid('El contacto se ha actualizado correctamente!');
                return Redirect::toAction("ver/$key/");
            }
        }

        $this->empresa = (new Empresas)->getInformacionEmpresas($contactos->empresas_id);
        $this->contacto = $contactos;
        $this->page_title = 'Actualizar contacto';
    }

    /**
     * Método para inactivar/reactivar
     */
    public function estadocontacto($tipo, $key) {
        if (!$id = Security::getKey($key, $tipo . '_contacto', 'int')) {
            return Redirect::toAction('listar');
        }

        $contacto = new Contactos();
        if (!$contacto->find_first($id)) {
            Flash::error('No se pudo verificar la informacion del contacto');
        } else {
            $key = Security::setKey($contacto->empresas_id, 'shw_proveedor');
            if ($contacto->id <= 0) {
                Flash::warning('Lo sentimos, pero este contacto no se puede editar.');
                return Redirect::toAction("ver/$key/");
            }
            if ($tipo == 'inactivar' && $contacto->estado == Contactos::INACTIVO) {
                Flash::info('El contacto ya se encuentra inactivo');
            } else if ($tipo == 'reactivar' && $contacto->estado == Contactos::ACTIVO) {
                Flash::info('El contacto ya se encuentra estado');
            } else {
                $estado = ($tipo == 'inactivar') ? Contactos::INACTIVO : Contactos::ACTIVO;
                if (Contactos::setContacto('update', $contacto->to_array(), array('id' => $id, 'estado' => $estado))) {
                    ($estado == Contactos::ACTIVO) ? Flash::valid('El contacto se ha reactivado correctamente!') : Flash::valid('El contacto se ha inactivado correctamente!');
                }
            }
        }

        return Redirect::toAction("ver/$key/");
    }

    /**
     * Método para ver
     */
    public function ver($key) {
        if (!$id = Security::getKey($key, 'shw_proveedor', 'int')) {
            return Redirect::toAction('listar');
        }

        $proveedor = new Empresas();
        if (!$proveedor->getInformacionEmpresas($id)) {
            Flash::error('No se pudo verificar la informacion del proveedor');
            return Redirect::toAction('listar');
        }

        $this->contactos = (new Contactos)->getInformacionContactosPorTipo('todos', 'order.nombre.desc', $proveedor->id, $proveedor->type);

        $this->proveedor = $proveedor;
        $this->page_title = 'Información';
    }

    /**
     * Método para migrar clientes
     */
    public function migrar() {
        $obj = (new Empresas());
        $clientes = (new Clientes())->find();

        try {
            $data = array();
            $contacto = array();
            foreach ($clientes as $row) {
                $old_cliente = (new Empresas())->getByRuc($row->ruc);
                if (empty($old_cliente)) {
                    $data['ruc'] = $row->ruc;
                    $data['tipo_documento'] = $row->tipo_documento;
                    $data['razon_social'] = $row->apellidos;
                    $data['nombres'] = empty($row->nombres) ? $row->siglas : $row->nombres;
                    $data['web'] = NULL;
                    $data['detalle'] = NULL;
                    $data['contabilidad'] = NULL;
                    $data['especial'] = NULL;
                    $data['numero_especial'] = NULL;
                    $data['lugar'] = NULL;
                    $data['tipo'] = NULL;
                    $data['type'] = 'c';
                    $data['credito'] = (strtolower($row->plazo) == 's') ? 1 : 0;
                    $data['tiempo'] = $row->tiempo;

                    //registro la empresa
                    if ($rs = !Empresas::setEmpresas('create', NULL, $data)) {
                        Flash::info('Se ha producido un error al crear el cliente<br>' . $row->apellidos . ' ' . $row->nombres);
                        return FALSE;
                    }

                    if ($row->ruc) {
                        $existe = (new Empresas())->getByRuc($row->ruc);

                        $contacto['empresas_id'] = $existe->id;
                        $contacto['ciudad_id'] = $row->ciudad_id;
                        $contacto['codigo_establecimiento'] = NULL;
                        $contacto['sucursal'] = NULL;
                        $contacto['sucursal_slug'] = NULL;
                        $contacto['direccion'] = $row->direccion;
                        $contacto['telefono'] = $row->telefono;
                        $contacto['telefono2'] = $row->telefono2;
                        $contacto['celular'] = $row->celular;
                        $contacto['ext'] = $row->ext;
                        $contacto['ext2'] = NULL;
                        $contacto['email'] = $row->email;
                        $contacto['email2'] = NULL;
                        $contacto['estado'] = $row->estado;
                        $contacto['type'] = 'c';
                        $contacto['migra'] = '1';

                        //registro el contacto
                        if (!Establecimientos::setEstablecimientos('create', NULL, $contacto)) {
                            Flash::info('Se ha producido un error al crear los datos del cliente');
                            return FALSE;
                        }
                    }
                }
//                return TRUE;
            }
        } catch (KumbiaException $e) {
            Flash::error($e->getMessage());
            return false;
        }
    }

}
