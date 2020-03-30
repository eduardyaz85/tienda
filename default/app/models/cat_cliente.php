<?php

/**
 * Asdin - Web | App
 *
 * Descripcion: Modelo encargado de registrar compras de clientes
 *
 * @category    
 * @package     Models 
 * @author      Eduardo Yánez (asdin_shop@yahoo.com)
 * @copyright   Copyright (c) 2019 Asdin
 * @revision    1.0
 */
class CatCliente {

    //Se desabilita el logger para no llenar el archivo de "basura"
    public $logger = FALSE;

    /**
     * Método para procesar la orden del Cliente
     */
    public function getOrdenCliente($data) {
        $obj = new CatCliente();
        try {
            $stream = json_encode($data);
            $orden = json_decode($stream);

            $existe = $obj->getVerificaDatosOrden($orden);
            $pedido = 0;
            if (!empty($existe)) {
                $cliente = (new Empresas)->getGuardaDatosClientes($data);

                if (!empty($cliente)) {
                    $pedido = (new OrdCabecera)->getProcesaCarroCompras($cliente, $data);
                    return $pedido;
                } else {
                    return FALSE;
                }
            } else {
                return FALSE;
            }
        } catch (KumbiaException $e) {
            View::exception($e);
        }
    }

    /**
     * Método para verificar que toda la informacion este llena
     */
    public function getVerificaDatosOrden($data) {
        $obj = new CatCliente();
        try {
            if (empty($data->terms)) {
                Flash::error("Debe Aceptar los Terminos");
                return false;
            }
            if ($data->tipo_orden == '0') {
                if (empty($data->entregas)) {
                    Flash::error("Debe Seleccionar una forma de Envio");
                    return false;
                }
            }
            if (empty($data->pagos)) {
                Flash::error("Debe Seleccionar una forma de pago");
                return false;
            }
            if (!empty($data->new_usuario)) {
                $rs_usr = $obj->getVerificaDatosUsuario($data);
                if (empty($rs_usr)) {
                    return FALSE;
                }
            }
            if (!empty($data->ruc)) {
                $rs_emp = $obj->getVerificaDatosEmpresa($data);
                if (empty($rs_emp)) {
                    return FALSE;
                }
            }
            return TRUE;
        } catch (KumbiaException $e) {
            View::exception($e);
        }
    }

    /**
     * Verifico los datos del cliente antes de registrar
     */
    public function getVerificaDatosEmpresa($data) {
        if (empty($data->ciudad_id) && empty($data->ciudad)) {
            Flash::error("Indica tu Ciudad");
            return false;
        }
        if (!empty($data->new_ciudad)) {
            if (empty($data->region)) {
                Flash::error("Indica la Region");
                return false;
            }
            if (empty($data->ciudad)) {
                Flash::error("Indica tu Ciudad");
                return false;
            }
        }
        if (empty($data->direccion)) {
            Flash::error("Indica la Direccion para la factura");
            return false;
        }
        if (empty($data->email)) {
            Flash::error("Indica un correo para la factura");
            return false;
        }
        if (empty($data->tipo_documento)) {
            Flash::error("Indica tu documento de identificacion");
            return false;
        }
        if (empty($data->ruc)) {
            Flash::error("Indica el numero de cedula/ruc");
            return false;
        }
        if (empty($data->razon_social)) {
            Flash::error("Indica el apellido/razon social");
            return false;
        }
        return TRUE;
    }

    /**
     * Verifico los datos del usuario antes de crear la cuenta
     */
    public function getVerificaDatosUsuario($data) {
        $usuario = new Usuarios();

        if (empty($data->email3)) {
            Flash::error("Indica el Correo para la cuenta");
            return false;
        }
        if (empty($data->login)) {
            Flash::error("Indica el nombre de usuario");
            return false;
        }
        if (empty($data->password)) {
            Flash::error("Indica la contraseña");
            return false;
        }
        //Verifico si se encuentra el mail registrado
        $existe = $usuario->findByEmail($data->email3);
        if (!empty($existe->email)) {
            Flash::error('El correo electrónico ya se encuentra registrado, ingrese otro correo.');
            return false;
        }
        //Verifico si el login está disponible
        $existeu = $usuario->findByNick($data->login);
        if (!empty($existeu->login)) {
            Flash::error('El nombre de usuario no se encuentra disponible.');
            return false;
        }
        return TRUE;
    }

    /**
     * Método para guardar Ciudades
     */
    public function guardaCiudades($newMarcas) {
        $marcas = (new Marca)->find("id != '0'");

        foreach ($newMarcas as $key => $value) {
            $jMarca = filter_var($value['marca'], FILTER_SANITIZE_STRING);

            //busco id marca
            $iMarca = (new CatWs())->findMarca($jMarca, $marcas);
            //creo la marca
            if (empty($iMarca)) {
                if (!Marca::setMarca('create', NULL, ['marca' => $jMarca, 'brand' => $value['brand'], 'activo' => 1])) {
                    Flash::info('Se ha producido un error al crear la marca ' . $value['marca']);
                    return FALSE;
                }
            }
        }
    }

    /*
     * ========================================================================
     *  MANEJO ARTICULOS CREAR/MODIFICAR
     * ======================================================================== 
     */

    /**
     * Método crear el Cliente
     */
    public function creaCliente($new, $objCon, $marcas, $categorias, $galeria, $utilidad) {
        $catalogows = (new CatWs());
        //busco id marca
        $iMarca = $catalogows->findMarca($new->Brand->Description, $marcas);

        //busco id categoria
        $iCategoria = $catalogows->findCategoria($new->Category->Subcategories['0']->CategoryId, $categorias);
        if (!empty($iCategoria->id)) {
            //creamos registro nuevo
            $data = array();
            $data['cat_conexion_id'] = $objCon->id;
            $data['marca_id'] = $iMarca->id;
            $data['empresas_id'] = $objCon->empresas_id;
            $data['categorias_id'] = $iCategoria->id;
            $data['umedida_id'] = 1;
            $data['onsales'] = ($new->OnSale == TRUE) ? 1 : 0;
            $data['instock'] = $new->InStock;
            $data['sku'] = $new->Sku;
            $data['mpn'] = $new->Mpn;
            $data['descripcion'] = filter_var($new->Description, FILTER_SANITIZE_STRING);
            $data['detalle'] = '';
            $data['type'] = $new->Type;
            $data['nuevo'] = ($new->New == TRUE) ? 1 : 0;
            $data['precio_compra'] = $new->Price->UnitPrice;
            $data['web'] = CatMaster::ACTIVO;
            $data['pre_man'] = CatMaster::INACTIVO;
            $data['tipo_articulo'] = 'c';
            $data['porcentaje'] = NULL;
            $data['usuario_registro'] = empty(Session::get('id')) ? 1 : Session::get('id');
            $data['estado'] = CatMaster::ACTIVO;

            if (!empty($data)) {
                $catalogo = (new CatMaster());

                if (!$catalogo->create($data)) {
                    Flash::error('Ops! no se pudo crear el articulo<br>' . $new->Description);
                    return FALSE;
                }
                if (!empty($catalogo->id)) {
                    //registramos los precios
                    $catalogows->guardaPrecios($new, $catalogo, $utilidad);

                    //busco galeria con numero de parte
                    $iGaleria = $catalogows->findGaleria($catalogo->mpn, $galeria);

                    if (empty($iGaleria)) {
                        //registramos la foto
                        (new Galeria())->create(['mpn' => $catalogo->mpn,
                            'empresas_id' => $objCon->empresas_id]);
                    }
                    //registramos el impuesto 
                    (new CatImpuestos())->create([
                        'cat_master_id' => $catalogo->id,
                        'impuestos_id' => 168,
                        'usuario_registra' => Session::get('id'),
                        'estado' => CatImpuestos::ACTIVO
                    ]);
                }

                return ['result' => $catalogo->id];
            } else {
                return ['result' => 0];
            }
        }
    }

    /*
     * ========================================================================
     *  MANEJO COSTO Y BUSCA CIUDADES, USUARIOS, CLIENTES
     * ======================================================================== 
     */

    /**
     * Método para buscar id del Clienta
     */
    public function findMarca($jMarca, $marcas) {
        $iMarca = null;
        foreach ($marcas as $data) {
            if (strtoupper(trim($data->marca)) === strtoupper(trim($jMarca))) {
                $iMarca = $data;
                break;
            }
        }
        return $iMarca;
    }

}
