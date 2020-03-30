<?php

/**
 * Asdin - Web | App
 *
 * Descripcion: Modelo encargado de registrar el catalogo de productos
 *
 * @category    
 * @package     Models 
 * @author      Eduardo Yánez (asdin_shop@yahoo.com)
 * @copyright   Copyright (c) 2019 Asdin
 * @revision    1.0
 */
Load::models('sistema/sistema');

class CatMaster extends ActiveRecord {

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
        $this->validates_presence_of('descripcion', 'message: Ingresa el nombre de la articulo');
    }

    /**
     * Método para listar los cat_master
     * @return array
     */
    public function getListadoCatalogo($pagina = 'listar', $value = '', $order = '', $page = 0) {
        $colm = "cat_master.id, cat_master.sku, cat_master.mpn, cat_master.descripcion, cat_master.detalle, cat_master.type, cat_master.onsales, cat_master.nuevo, cat_master.pre_man, cat_master.frontend, cat_master.instock, cat_master.precio_compra, ";
        $colm .= "ci.impuestos_id, impuestos.valor, impuestos.impuesto, pr.precio_venta, pr.precio_distribuidor, ";
        $colm .= "marca.marca, marca.brand, categorias.slug, categorias.category, im.mpn as im_mpn, im.imagen, im.url_foto ";
        $join = 'INNER JOIN marca ON marca.id = cat_master.marca_id ';
        $join .= 'INNER JOIN categorias ON categorias.id = cat_master.categorias_id ';
        $join .= "INNER JOIN ( SELECT cat_snc.cat_master_id, cat_snc.precio_venta, cat_snc.precio_distribuidor, cat_snc.registro_at FROM cat_snc WHERE estado = 1 GROUP BY cat_snc.cat_master_id DESC ) pr ON pr.cat_master_id = cat_master.id ";
        $join .= "LEFT JOIN ( SELECT cat_master_id, impuestos_id FROM cat_impuestos GROUP BY cat_master_id ASC ) ci ON ci.cat_master_id = cat_master.id ";
        $join .= "LEFT JOIN impuestos ON impuestos.id = ci.impuestos_id ";
        $join .= "LEFT JOIN galeria im ON im.mpn = cat_master.mpn ";
        $cond = "cat_master.instock > 0";

        $order = $this->get_order($order, 'descripcion');

        if ($pagina == 'categoria') {
            $cond .= " AND cat_master.estado = 1 AND categorias.slug = '$value' ";
        } else if ($pagina == 'galeria') {
            $cond .= " AND im.imagen IS NULL ";
        } else if ($pagina == 'saldos') {
            $cond .= " AND cat_master.onsales = 1";
        } else if ($pagina == 'nuevo') {
            $cond .= " AND cat_master.nuevo = 1";
        } else if ($pagina == 'buscar') {
            $cond .= " AND cat_master.estado = 1";
            $cond .= " AND UPPER(codigo) LIKE '%$value%' OR  UPPER(descripcion) LIKE '%$value%' OR UPPER(sku) LIKE '%$value%' OR UPPER(cat_master.mpn) LIKE '%$value%' OR UPPER(marca.marca) LIKE '%$value%' OR UPPER(categorias.category) LIKE '%$value%' ";
        }
        if ($page) {
            return $this->paginated("columns: $colm", "join: $join", "conditions: $cond", "order: $order", "page: $page", "per_page: 27");
        } else {
            return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
        }
    }

    /**
     * Método para ver informacion del articulo
     */
    public function getArticuloBackEnd($id_articulo) {
        $colm = "cat_master.*, marca.marca, marca.brand, categorias.slug, categorias.category, umedida.simbolo, im.imagen, im.id as id_imagen, empresas.nombres, ";
        $colm .= "pr.precio_venta, pr.precio_distribuidor, cat_impuestos.impuestos_id, impuestos.valor, impuestos.impuesto ";
        $join = 'INNER JOIN marca ON marca.id = cat_master.marca_id ';
        $join .= 'INNER JOIN categorias ON categorias.id = cat_master.categorias_id ';
        $join .= 'INNER JOIN empresas ON empresas.id = cat_master.empresas_id ';
        $join .= 'INNER JOIN umedida ON umedida.id = cat_master.umedida_id ';
        $join .= "INNER JOIN ( SELECT cat_snc.cat_master_id, cat_snc.precio_venta, cat_snc.precio_distribuidor, cat_snc.registro_at FROM cat_snc WHERE estado = 1 GROUP BY cat_snc.cat_master_id DESC ) pr ON pr.cat_master_id = cat_master.id ";
        $join .= 'INNER JOIN cat_impuestos ON cat_impuestos.cat_master_id = cat_master.id ';
        $join .= "LEFT JOIN impuestos ON impuestos.id = cat_impuestos.impuestos_id ";
        $join .= "LEFT JOIN ( SELECT cat_master_id, impuestos_id FROM cat_impuestos GROUP BY cat_master_id ASC ) ci ON ci.cat_master_id = cat_master.id ";
        $join .= "LEFT JOIN galeria im ON im.mpn = cat_master.mpn ";
        $cond = "cat_master.id = $id_articulo";
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond");
    }

    /**
     * Método para ver informacion del articulo web
     */
    public function getArticuloFrontEnd($id_articulo, $marca) {
        $colm = "cat_master.id, cat_master.sku, cat_master.mpn, cat_master.descripcion, cat_master.detalle, cat_master.type, cat_master.onsales, cat_master.nuevo, cat_master.nuevo, cat_master.pre_man,cat_master.frontend, cat_master.instock, im.imagen, umedida.simbolo,  ";
        $colm .= "cat_master.precio_compra, pr.precio_venta, pr.precio_distribuidor, cat_impuestos.impuestos_id, impuestos.valor, impuestos.impuesto, marca.marca, marca.brand, categorias.slug, categorias.category, cat_master.estado ";
        $join = 'INNER JOIN marca ON marca.id = cat_master.marca_id ';
        $join .= 'INNER JOIN categorias ON categorias.id = cat_master.categorias_id ';
        $join .= 'INNER JOIN umedida ON umedida.id = cat_master.umedida_id ';
        $join .= 'INNER JOIN cat_impuestos ON cat_impuestos.cat_master_id = cat_master.id ';
        $join .= "LEFT JOIN impuestos ON impuestos.id = cat_impuestos.impuestos_id ";
        $join .= "LEFT JOIN ( SELECT cat_master_id, precio_distribuidor, precio_venta, registro_at FROM cat_snc WHERE cat_snc.estado = 1 ) pr ON pr.cat_master_id = cat_master.id AND brand = '$marca'";
        $join .= "LEFT JOIN galeria im ON im.mpn = cat_master.mpn ";
        $cond = "cat_master.id = $id_articulo";
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond");
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
    public static function setCatalogoMaster($method, $data, $optData = NULL) {
        $obj = new CatMaster($data);
        if ($optData) {
            $obj->dump_result_self($optData);
        }
//        DwOnline::pr($obj);
//        die();
        $obj->begin();
        if ($method != 'delete') {
            if (empty($obj->marca_id)) {
                Flash::info('Selecciona una Marca');
                return FALSE;
            }
            if (empty($obj->umedida_id)) {
                Flash::info('Selecciona una Unidad');
                return FALSE;
            }
            if (empty($obj->categorias_id)) {
                Flash::info('Selecciona una Categoria');
                return FALSE;
            }
            if ($obj->tipo_articulo != 's') {
                if (empty($obj->empresas_id)) {
                    Flash::info('Selecciona un Proveedor');
                    return FALSE;
                }
                if (empty($obj->sku)) {
                    Flash::info('Indica el codigo Principal');
                    return FALSE;
                }
                if (empty($obj->mpn)) {
                    Flash::info('Indica el codigo Auxiliar');
                    return FALSE;
                }
            }
            if (empty($obj->descripcion)) {
                Flash::info('Indica el nombre del Articulo');
                return FALSE;
            }
            if (empty($obj->precio_compra)) {
                Flash::info('Indica el precio de Compra');
                return FALSE;
            }

            //verifico precio para actualizar
            if ($method == 'update') {
                //actualizamos precios
                $utilidad = (new Utilidad)->find();
                $catalogo = (new CatMaster())->getArticuloBackEnd($obj->id);
                $precio_nuevo = round($obj->precio_compra, 4);
                $old_precio = round($catalogo->precio_compra, 4);

                $precio_venta = round($obj->precio_venta, 2);
                $old_venta = round($catalogo->precio_venta, 2);
                //registramos los precios si tiene algún cambio
                if ($old_precio != $precio_nuevo OR $old_venta != $precio_venta) {
                    (new CatMaster())->guardaPrecios($obj, $utilidad, $precio_venta);
                }
            }

            $old = (isset($obj->id)) ? $obj->count("marca_id='$obj->marca_id' AND empresas_id='$obj->empresas_id' AND sku='$obj->sku' AND mpn='$obj->mpn' AND id!= $obj->id") : $obj->count("marca_id='$obj->marca_id' AND empresas_id='$obj->empresas_id' AND sku='$obj->sku' AND mpn='$obj->mpn'");
            if ($old) {
                $obj->rollback();
                Flash::error('Ya existe un articulo registrado bajo esos parámetros.<br>' . $obj->descripcion);
                return FALSE;
            }
        } else {
            //Valido el ID
            $obj->id = Filter::get($obj->id, 'int');
            if (empty($obj->id)) {
                $obj->rollback();
                Flash::error('No se ha podido establecer la informacion del Articulo');
                return FALSE;
            }

            $obj->find_first("columns: cat_master.*", "conditions: cat_master.id = $obj->id");

            if ((Session::get('perfil_id') > Perfil::ADMIN)) {
                $obj->rollback();
                Flash::error('Tu no tienes los permisos para anular los registros del establecimiento');
                return FAlSE;
            }
        }
        $obj->commit();
//        DwOnline::pr($obj);
//        die();
        return ($obj->$method()) ? $obj : FALSE;
    }

    /**
     * Método que se ejecuta antes de guardar y/o modificar     
     */
    public function before_save() {
        $this->sku = filter_var(strtoupper($this->sku), FILTER_SANITIZE_STRING);
        $this->mpn = filter_var(strtoupper($this->mpn), FILTER_SANITIZE_STRING);
        $this->descripcion = filter_var(ucwords(strtolower($this->descripcion)), FILTER_SANITIZE_STRING);
        $this->detalle = filter_var(strtoupper($this->detalle), FILTER_SANITIZE_STRING);
    }

    /**
     * Callback que se ejecuta despues de sincronizar el catalogo
     */
    protected function after_create() {
        if ($this->tipo_articulo != 'c') {
            $catalogo = (new CatMaster());
            $utilidad = (new Utilidad)->find();
            //registramos los precios
            $catalogo->guardaPrecios($this, $utilidad, $this->precio_venta);

            if ($this->tipo_articulo == 'm') {
                $catalogows = (new CatWs());
                $galeria = (new Galeria)->getGaleriaCatalogo("$this->empresas_id");

                //busco galeria con numero de parte
                $iGaleria = $catalogows->findGaleria($this->mpn, $galeria);

                if (empty($iGaleria)) {
                    //registramos la foto
                    (new Galeria())->create([
                        'mpn' => $this->mpn,
                        'empresas_id' => $this->empresas_id
                    ]);
                }
            }
            //creamos los impuestos
            if (!empty($this->iva_id)) {
                CatImpuestos::setArticulosImpuestos('create', null, array('impuestos_id' => $this->iva_id, 'cat_master_id' => $this->id, 'usuario_registra' => Session::get('id'), 'estado' => CatImpuestos::ACTIVO));
            }
            if (!empty($this->ice_id)) {
                CatImpuestos::setArticulosImpuestos('create', null, array('impuestos_id' => $this->ice_id, 'cat_master_id' => $this->id, 'usuario_registra' => Session::get('id'), 'estado' => CatImpuestos::ACTIVO));
            }
            if (!empty($this->irbpnr_id)) {
                CatImpuestos::setArticulosImpuestos('create', null, array('impuestos_id' => $this->irbpnr_id, 'cat_master_id' => $this->id, 'usuario_registra' => Session::get('id'), 'estado' => CatImpuestos::ACTIVO));
            }
        }
    }

    /**
     * Callback que se ejecuta después de guardar/crear
     */
    public function after_save() {
        
    }

    /**
     * Callback que se ejecuta despues de actualizar
     */
    protected function after_update() {
        if (!empty($this->iva_id) OR ! empty($this->ice_id) OR ! empty($this->irbpnr_id)) {
            $impuestos = (new CatImpuestos())->find("cat_master_id = $this->id");

            //elimino los impuestos al antiguos
            $this->begin();
            foreach ($impuestos as $value) {
                if (!$value->delete("cat_master_id = $this->id")) {
                    $this->rollback();
                    return FALSE;
                }
            }
            $this->commit();

            if (!empty($this->iva_id)) {
                CatImpuestos::setArticulosImpuestos('create', null, array('impuestos_id' => $this->iva_id, 'cat_master_id' => $this->id, 'usuario_registra' => Session::get('id'), 'estado' => CatImpuestos::ACTIVO));
            }
            if (!empty($this->ice_id)) {
                CatImpuestos::setArticulosImpuestos('create', null, array('impuestos_id' => $this->ice_id, 'cat_master_id' => $this->id, 'usuario_registra' => Session::get('id'), 'estado' => CatImpuestos::ACTIVO));
            }
            if (!empty($this->irbpnr_id)) {
                CatImpuestos::setArticulosImpuestos('create', null, array('impuestos_id' => $this->irbpnr_id, 'cat_master_id' => $this->id, 'usuario_registra' => Session::get('id'), 'estado' => CatImpuestos::ACTIVO));
            }
        }
    }

    /**
     * Método para almacenar precios del producto
     */
    public function guardaPrecios($articulo, $utilidad, $pventa) {
        $catalogows = (new CatWs());
        $catalogoSnc = (new CatSnc());
        $actualiza = $catalogoSnc->update_all("estado = 0", "cat_master_id = $articulo->id");
        //busco utilida con porcentaje
        $precio_venta = empty($pventa) ? $catalogows->findPrecioVenta($articulo->precio_compra, $utilidad, $tipo = 'u') : $pventa;
        $precio_distri = $catalogows->findPrecioVenta($articulo->precio_compra, $utilidad, $tipo = 'd');

        $catalogoSnc->create([
            'cat_master_id' => $articulo->id,
            'instock' => $articulo->instock,
            'price' => $articulo->precio_compra,
            'precio_venta' => $precio_venta,
            'precio_distribuidor' => $precio_distri,
            'usuario_registro' => $articulo->usuario_registro,
            'estado' => CatMaster::ACTIVO
        ]);
    }

    /**
     * Método para cargar el catalogo completo
     * @return array
     */
    public function getListadoCatalogoOld($proveedor_id, $tipo) {
        $colm = "cat_master.*";
        $cond = "empresas_id = $proveedor_id AND tipo_articulo = '$tipo' AND pre_man = 0";
        return $this->find("columns: $colm", "conditions: $cond", "order: cat_master.id");
    }

    /**
     * Método para obtener la información del codigo del articulo
     * @return type
     */
    function codigoArticuloSistema() {
        $columnas = "cat_master.id, cat_master.codigo";
        $condicion = "cat_master.codigo != 'NULL' ";
        return $this->find_first("columns: $columnas", "conditions: $condicion", "order: id desc");
    }

    /*
     * ========================================================================
     *  MANEJO CATALOGO WEB SERVICE
     * ======================================================================== 
     */

    /**
     * Método para cargar el catalogo mediante WebService
     * @return array
     */
    public function getCatalogoJsonWs($tipo) {
        $colm = "cat_master.id, cat_master.sku, cat_master.mpn, im.mpn as im_mpn, cat_master.descripcion, cat_master.detalle, cat_master.type, cat_master.onsales, cat_master.nuevo, cat_master.nuevo, cat_master.pre_man,cat_master.frontend, cat_master.instock, im.imagen, im.url_foto, ";
        $colm .= "pr.precio_venta, pr.precio_distribuidor, ci.impuestos_id, impuestos.valor, impuestos.impuesto, marca.marca, marca.brand, categorias.slug, categorias.category, cat_master.estado ";
        $join = 'INNER JOIN marca ON marca.id = cat_master.marca_id ';
        $join .= 'INNER JOIN categorias ON categorias.id = cat_master.categorias_id ';
        $join .= "INNER JOIN ( SELECT cat_snc.cat_master_id, cat_snc.precio_venta, cat_snc.precio_distribuidor, cat_snc.registro_at FROM cat_snc WHERE estado = 1 GROUP BY cat_snc.cat_master_id DESC ) pr ON pr.cat_master_id = cat_master.id ";
        $join .= "LEFT JOIN ( SELECT cat_master_id, impuestos_id FROM cat_impuestos GROUP BY cat_master_id ASC ) ci ON ci.cat_master_id = cat_master.id ";
        $join .= "LEFT JOIN impuestos ON impuestos.id = ci.impuestos_id ";
        $join .= "LEFT JOIN galeria im ON im.mpn = cat_master.mpn ";
        $cond = "cat_master.web = " . CatMaster::ACTIVO;
        $cond .= " AND cat_master.tipo_articulo != 's'";
        $cond .= " AND cat_master.instock >= " . CatMaster::INACTIVO;
        $order = "cat_master.instock DESC";
        if (!empty($tipo)) {
            $rs = $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
            return json_encode($rs);
        } else {
            return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
        }
    }

    /*
     * ========================================================================
     *  MANEJO CATALOGO WEB
     * ======================================================================== 
     */

    /**
     * Método para buscar cat_master
     */
    public function getBuscaFrontEnd($value, $order = '', $page = 0) {
        $value = Filter::get($value, 'string');
        if (strlen($value) <= 0 OR ( $value == 'none')) {
            return NULL;
        }
        $colm = "cat_master.id, cat_master.sku, cat_master.mpn, im.mpn as im_mpn, cat_master.descripcion, cat_master.detalle, cat_master.type, cat_master.onsales, cat_master.nuevo, cat_master.nuevo, cat_master.pre_man,cat_master.frontend, cat_master.instock, im.imagen, im.url_foto, ";
        $colm .= "pr.precio_venta, pr.precio_distribuidor, ci.impuestos_id, impuestos.valor, impuestos.impuesto, marca.marca, marca.brand, categorias.slug, categorias.category, cat_master.estado ";
        $join = 'INNER JOIN marca ON marca.id = cat_master.marca_id ';
        $join .= 'INNER JOIN categorias ON categorias.id = cat_master.categorias_id ';
        $join .= "INNER JOIN ( SELECT cat_snc.cat_master_id, cat_snc.precio_venta, cat_snc.precio_distribuidor, cat_snc.registro_at FROM cat_snc WHERE estado = 1 GROUP BY cat_snc.cat_master_id DESC ) pr ON pr.cat_master_id = cat_master.id ";
        $join .= "LEFT JOIN ( SELECT cat_master_id, impuestos_id FROM cat_impuestos GROUP BY cat_master_id ASC ) ci ON ci.cat_master_id = cat_master.id ";
        $join .= "LEFT JOIN impuestos ON impuestos.id = ci.impuestos_id ";
        $join .= "LEFT JOIN galeria im ON im.mpn = cat_master.mpn ";
        $cond = "cat_master.instock > 1 AND ("
                . "cat_master.descripcion LIKE '%$value%' OR "
                . "cat_master.mpn LIKE '%$value%' OR "
                . "categorias.category LIKE '%$value%' OR "
                . "marca.marca LIKE '%$value%' "
                . ") AND cat_master.web = 1 "
                . " AND cat_master.estado != 0 ";

        $order = $this->get_order($order, 'description');

        if ($page) {
            return $this->paginated("columns: $colm", "join: $join", "conditions: $cond", "order: $order", "page: $page", "per_page: 27");
        } else {
            return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order", "per_page: 27");
        }
    }

}
