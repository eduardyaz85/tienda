<?php

/**
 * Asdin - Web | App
 *
 * Descripcion: Modelo encargado de registrar el catalogo del proveedor
 *
 * @category    
 * @package     Models 
 * @author      Eduardo Yánez (asdin_shop@yahoo.com)
 * @copyright   Copyright (c) 2019 Asdin
 * @revision    1.0
 */
ini_set('max_execution_time', 620);

class CatWs {

    //Se desabilita el logger para no llenar el archivo de "basura"
    public $logger = FALSE;

    //URL CATALOGO
    const URL_TXT_CATALOGO = APP_PATH . 'temp/ws/catalogo.txt';
    //PRUEBAS
    const URL_JSON_FILE = 'files/productos.json';

    /**
     * Método para solicitar el catalogo
     */
    public function pideCatalogoProveedor($proveedor_id) {
        if (PRODUCTION) {
            //WebService base
//            $url = DwConect::peticionURL(); //pedir url
//            $stream = DwConect::getSslPage($url);
//            $jsonPHP = json_decode($stream);

            $stream = file_get_contents(self::URL_JSON_FILE);
            $jsonPHP = json_decode($stream);
        } else {
//            $url = DwConect::peticionURL(); //pedir url
//            $stream = DwConect::getSslPage($url);
//            $jsonPHP = json_decode($stream);
            //Base Local
            $stream = file_get_contents(self::URL_JSON_FILE);
            $jsonPHP = json_decode($stream);
        }

        try {
            //Guardamos los procesos de conexion
            $objCon = new CatConexion();
            if (!empty($jsonPHP->Message)) {
                $objCon->id = NULL;
                $objCon->empresas_id = $proveedor_id;
                $objCon->usuarios_id = empty(Session::get('id')) ? 1 : Session::get('id');
                $objCon->mensaje = "$jsonPHP->Message";
                $objCon->codigo_error = $jsonPHP->ErrorCode;
                $objCon->referencia = "$jsonPHP->Reference";
                $objCon->hora_ws = empty($jsonPHP->TimeStamp) ? '' : $jsonPHP->TimeStamp;

                if (!$objCon->create()) {
                    Flash::error("Ha ocurrido un error <br>$jsonPHP->ErrorCode");
                    return FALSE;
                } else {
                    Flash::error("Ops Web Service no disponible! <br>$jsonPHP->Message");
                    return TRUE;
                }
                return $objCon;
            } else {
                $objCon->id = NULL;
                $objCon->empresas_id = $proveedor_id;
                $objCon->usuarios_id = empty(Session::get('id')) ? 1 : Session::get('id');
                $objCon->mensaje = "Conexion Exitosa";
                $objCon->codigo_error = "ok";

                if (!$objCon->create()) {
                    Flash::error("Ha ocurrido un error en la Sincronizacion...");
                    return FALSE;
                }

                array_walk($jsonPHP, function($val, $key) use(&$jsonPHP) {
                    if ($val->Sku == 'PB100GEN02' OR $val->Sku == 'EM220PYV13' OR $val->Sku == 'PP006PRM73' OR $val->Sku == 'PP006PRM74' OR $val->Sku == 'PP007PRM05') {
                        unset($jsonPHP[$key]);
                    }
                });

                (new CatWs())->procesaCatalogo($jsonPHP, $objCon);
                return $objCon;
            }
        } catch (KumbiaException $e) {
            View::exception($e);
        }
    }

    /**
     * Método para separar el catalogo
     */
    public function procesaCatalogo($jsonPHP, $objCon) {
        $catalogows = (new CatWs());
        $old_catalogo = (new CatMaster)->getListadoCatalogoOld($objCon->empresas_id, $tipo = 'c');

        if (!empty($jsonPHP)) {
            //array para actualizar catalogo
            $updCatalogo = array();
            $newCatalogo = array();
            foreach ($jsonPHP as $key => $new) {
                //busco el material en la base de datos
                $oldArt = $catalogows->findArticulo($new->Sku, $new->Mpn, $old_catalogo);
                if (!empty($oldArt->id)) {
                    $precio_nuevo = round($new->Price->UnitPrice, 4);
                    $old_precio = round($oldArt->precio_compra, 4);
                    if ($new->InStock != $oldArt->instock OR $precio_nuevo != $old_precio) {
                        $new->id = $oldArt->id;
                        $new->precio_compra = $oldArt->precio_compra;
                        $new->cambia_precio = ($precio_nuevo != $old_precio) ? 1 : 0;
                        unset($jsonPHP[$key]);
                        $updCatalogo[] = $new;
                    }
                } else {
                    if ($new->InStock >= 2) {
                        unset($jsonPHP[$key]);
                        $newCatalogo[] = $new;
                    }
                }
            }

            if (!empty($updCatalogo)) {
                $catalogows->actualizaCatalogo($updCatalogo, $objCon);
            }
            if (!empty($newCatalogo)) {
                $catalogows->procesaComplementos($newCatalogo, $objCon);
            }
        }
    }

    /**
     * Método para separar marcas, categorias y subcategorias
     */
    public function procesaComplementos($catalogo, $objCon) {
        $catalogows = (new CatWs());

        $newMarcas = array();
        $newCategorias = array();
        $newSubCategorias = array();
        foreach ($catalogo as $key => $value) {
            //agrupo las marcas
            if ($value->Brand->Description) {
                $repeat = false;
                for ($i = 0; $i < count($newMarcas); $i++) {
                    if ($newMarcas[$i]['marca'] == $value->Brand->Description) {
                        $repeat = true;
                        break;
                    }
                }
                if ($repeat == false) {
                    $newMarcas[] = array('marca' => $value->Brand->Description, 'brand' => $value->Brand->BrandId);
                }
            }
            //agrupo las categorias
            if ($value->Category->CategoryId) {
                $repeat = false;
                for ($i = 0; $i < count($newCategorias); $i++) {
                    if ($newCategorias[$i]['slug'] == $value->Category->CategoryId) {
                        $repeat = true;
                        break;
                    }
                }
                if ($repeat == false) {
                    $newCategorias[] = array('slug' => $value->Category->CategoryId, 'category' => $value->Category->Description);
                }
            }
            //agrupo las sub-categorias
            if ($value->Category->Description) {
                foreach ($value->Category->Subcategories as $key => $row) {
                    $repeat = false;
                    for ($i = 0; $i < count($newSubCategorias); $i++) {
                        if ($newSubCategorias[$i]['slug'] == $row->CategoryId) {
                            $repeat = true;
                            break;
                        }
                    }
                    if ($repeat == false) {
                        $newSubCategorias[] = array('slug' => $row->CategoryId, 'category' => $row->Description);
                    }
                }
            }
        }

        if (!empty($newMarcas)) {
            $catalogows->guardaMarcas($newMarcas);
        }
        if (!empty($newCategorias)) {
            $catalogows->guardaCategorias($newCategorias);
        }
        if (!empty($newSubCategorias)) {
            $catalogows->guardaSubCategorias($newSubCategorias);
        }
        if (!empty($catalogo)) {
            $catalogows->guardaCatalogo($catalogo, $objCon);
        }
    }

    /**
     * Método para guardar Marcas
     */
    public function guardaMarcas($newMarcas) {
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

    /**
     * Método para guardar Categorias
     */
    public function guardaCategorias($newCategorias) {
        $categorias = (new Categorias)->find("tipo != 'i' AND parent_id = 0");

        foreach ($newCategorias as $key => $value) {
            $jCategoria = filter_var($value['category'], FILTER_SANITIZE_STRING);

            //busco id categoria
            $iCategoria = (new CatWs())->findCategoria($value['slug'], $categorias);

            //creo la categoria
            if (empty($iCategoria)) {
                if (!Categorias::setCategorias('create', NULL, ['category' => $jCategoria, 'slug' => $value['slug'], 'parent_id' => 0, 'tipo' => 'a', 'activo' => 1])) {
                    Flash::info('Se ha producido un error al crear la categoria');
                    return FALSE;
                }
            }
        }
    }

    /**
     * Método para guardar SubCategorias
     */
    public function guardaSubCategorias($newSubCategorias) {
        $categoria = (new Categorias);
        $categorias = $categoria->find("tipo != 'i' AND parent_id = 0");
        $subCategorias = $categoria->find("tipo != 'i' AND parent_id != 0");

        foreach ($newSubCategorias as $key => $value) {
            $jSubCategoria = filter_var($value['category'], FILTER_SANITIZE_STRING);
            $cateSlug = explode(".", $value['slug']);

            //busco la categoria
            $iCategoria = (new CatWs())->findCategoria($cateSlug['0'], $categorias);

            //busco la sub-categoria
            $isCategoria = (new CatWs())->findCategoria($value['slug'], $subCategorias);

            if (empty($isCategoria)) {
                $url = str_replace('.', '', $value['slug']);
                if (!Categorias::setCategorias('create', NULL, ['category' => $jSubCategoria, 'slug' => $value['slug'], 'parent_id' => $iCategoria->id, 'url' => $url, 'tipo' => 'a', 'activo' => 1])) {
                    Flash::info('Se ha producido un error al crear la sub-categoria');
                    return FALSE;
                }
            }
        }
    }

    /**
     * Método para guardar Articulos del Catalogo
     */
    public function guardaCatalogo($newCatalogo, $objCon) {
        $catalogows = (new CatWs());
        $marcas = (new Marca)->find();
        $utilidad = (new Utilidad)->find();
        $categorias = (new Categorias)->find("tipo != 'i' AND parent_id != 0");
        $galeria = (new Galeria)->getGaleriaCatalogo("$objCon->empresas_id");

        try {
            //cargo el catalogo Actual
            foreach ($newCatalogo as $key => $new) {
                $catalogows->creaArticulo($new, $objCon, $marcas, $categorias, $galeria, $utilidad);
            }
            return TRUE;
        } catch (KumbiaException $e) {
            Flash::error($e->getMessage());
            return FALSE;
        }
    }

    /*
     * ========================================================================
     *  MANEJO ARTICULOS CREAR/MODIFICAR
     * ======================================================================== 
     */

    /**
     * Método crear Articulo
     */
    public function creaArticulo($new, $objCon, $marcas, $categorias, $galeria, $utilidad) {
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
            $data['descripcion'] = filter_var(ucwords(strtolower($new->Description)), FILTER_SANITIZE_STRING);
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

                    if (empty($iGaleria->id)) {
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

    /**
     * Método para actualizar Articulos del Catalogo
     */
    public function actualizaCatalogo($updCatalogo, $objCon) {
        $catalogows = (new CatWs());
        $utilidad = (new Utilidad)->find();

        try {
            //cargo el catalogo Actual
            foreach ($updCatalogo as $key => $row) {
                $catalogo = (new CatMaster)->find_first($row->id);

                //registramos los precios si tiene algún cambio
                if (!empty($row->cambia_precio)) {
                    $catalogows->guardaPrecios($row, $catalogo, $utilidad);
                }

                //actuzalizamos los registros diferentes
                $catalogo->cat_conexion_id = $objCon->empresas_id;
                $catalogo->descripcion = ucwords(strtolower($catalogo->descripcion));
                $catalogo->onsales = ($row->OnSale == TRUE) ? 1 : 0;
                $catalogo->instock = $row->InStock; //actualiza stock
                $catalogo->nuevo = ($row->New == TRUE) ? 1 : 0;
                $catalogo->precio_compra = $row->Price->UnitPrice; //actualiza precio
                $catalogo->web = empty($row->InStock) ? 0 : 1;
                $catalogo->estado = empty($row->InStock) ? 0 : 1;

                if (!$catalogo->update()) {
                    Flash::error('Ops! no se pudo actualizar el articulo<br>' . $catalogo->Description);
                    return 'cancel';
                }
            }
            return TRUE;
        } catch (KumbiaException $e) {
            Flash::error($e->getMessage());
            return FALSE;
        }
    }

    /*
     * ========================================================================
     *  MANEJO COSTO Y BUSCA MARCAS, CATEGORIAS Y GALERIA
     * ======================================================================== 
     */

    /**
     * Método para buscar id de Marca
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

    /**
     * Método para buscar id de Categoria
     */
    public function findCategoria($jCategoria, $categorias) {
        $iCategoria = null;
        foreach ($categorias as $data) {
            if (trim($data->slug) === trim($jCategoria)) {
                $iCategoria = $data;
                break;
            }
        }
        return $iCategoria;
    }

    /**
     * Método para buscar id del Articulo
     */
    public function findArticulo($jSku, $jMpn, $catalogos) {
        $iCatalogo = null;
        foreach ($catalogos as $data) {
            if (strtoupper($data->sku) === strtoupper($jSku) && strtoupper($data->mpn) === strtoupper($jMpn)) {
                $iCatalogo = $data;
                break;
            }
        }
        return $iCatalogo;
    }

    /**
     * Método para buscar galeria
     */
    public function findGaleria($jMpn, $galeria) {
        $iGaleria = null;
        foreach ($galeria as $data) {
            if (strtoupper($data->mpn) === strtoupper($jMpn)) {
                $iGaleria = $data;
                break;
            }
        }
        return $iGaleria;
    }

    /**
     * Método para buscar el porcentaje de utilidad
     */
    public function findPrecioVenta($jPrecio, $utilidad, $tipo) {
        $iPrecio = null;
        foreach ($utilidad as $data) {
            if ("$data->tipo" == "$tipo") {
                if ($jPrecio >= $data->desde && $jPrecio <= $data->hasta) {
                    $uPrecio = ($jPrecio * $data->porcentaje) / 100;
                    $total = $jPrecio + $uPrecio;
                    $iPrecio = round($total, 2);
                    break;
                }
            }
        }
        return $iPrecio;
    }

    /**
     * Método para almacenar precios del producto
     */
    public function guardaPrecios($articulo, $catalogo, $utilidad) {
        $catalogows = (new CatWs());
        $catalogoSnc = (new CatSnc());
        $actualiza = $catalogoSnc->update_all("estado = 0", "cat_master_id = $catalogo->id");

        //busco utilida con porcentaje
        $precio_venta = $catalogows->findPrecioVenta($articulo->Price->UnitPrice, $utilidad, $tipo = 'u');
        $precio_distri = $catalogows->findPrecioVenta($articulo->Price->UnitPrice, $utilidad, $tipo = 'd');

        $catalogoSnc->create([
            'cat_master_id' => $catalogo->id,
            'instock' => $articulo->InStock,
            'price' => $articulo->Price->UnitPrice,
            'precio_venta' => $precio_venta,
            'precio_distribuidor' => $precio_distri,
            'usuario_registro' => empty(Session::get('id')) ? 1 : Session::get('id'),
            'estado' => CatMaster::ACTIVO
        ]);
    }

    /*
     * ========================================================================
     *  MANEJO CATALOGO FRONTEND
     * ======================================================================== 
     */

    /**
     * Método para solicitar el catalogo
     */
    public function getCatalogoWs() {
        $json = file_get_contents(self::URL_TXT_CATALOGO);
        $jsonPHP = json_decode($json);

        $catalogo = array();
        foreach ($jsonPHP as $key => $new) {
            if (!empty($new->onsales) && !empty($new->frontend)) {
                unset($jsonPHP[$key]);
                $catalogo['sales'][] = $new;
            }
            if (!empty($new->nuevo) && !empty($new->frontend)) {
                unset($jsonPHP[$key]);
                $catalogo['nuevo'][] = $new;
            }
            if (!empty($new->frontend) && empty($new->onsales) && empty($new->nuevo)) {
                unset($jsonPHP[$key]);
                $catalogo['recomendado'][] = $new;
            }
        }
        foreach ($jsonPHP as $key => $new) {
            unset($jsonPHP[$key]);
            $catalogo['catalogo'][] = $new;
        }
//        DwOnline::pr($catalogo);
//        die();
        return $catalogo;
    }

    /**
     * Método para leer el catalogo en txt
     */
    public static function getLeeCatalogo($acccion, $page) {
        $json = file_get_contents(self::URL_TXT_CATALOGO);
        $jsonPHP = json_decode($json);

        //Armo un nuevo array para ordenarlos 
        $catalogo = array();
        if (!empty($jsonPHP)) {
            foreach ($jsonPHP as $key => $row) {
                if (!empty($row->nuevo) && $acccion == 'nuevo') {
                    $catalogo[] = $row;
                }
                if (!empty($row->onsales) && $acccion == 'sales') {
                    $catalogo[] = $row;
                }
                if (!empty($row->frontend) && $acccion == 'recomendado') {
                    $catalogo[] = $row;
                }
                if (empty($row->nuevo) && empty($row->onsales) && empty($row->frontend) && $acccion == 'catalogo') {
                    $catalogo[] = $row;
                }
            }
        }
        $array = json_decode(json_encode($catalogo), True);
        $result = DwUtils::orderArray($array, 'descripcion', TRUE);
//        DwOnline::pr($result);
//        die();
        //Pagino el array
        $paginate = new DwPaginate();
        $paginate->paginate($result, "page: $page");
        return $paginate->paginate($result, "page: $page");
    }

    static function cmp($a, $b) {
        return strcmp($a["ocurrencia"], $b["ocurrencia"]);
    }

    public function buscarPorTexto($texto = '') {
        $result = [];
        DwOnline::pr($texto);
        die();
        $stream = file_get_contents(self::URL_TXT_CATALOGO);
        $materiales = json_decode(DwUtils::sanearStringJson($stream));

        $palabras = explode(' ', trim(strtoupper($texto)));


        foreach ($materiales as $data) {
//si vamos a buscar usando OR es igual hacer comparación de posicionamiento en todo el string
            $textoBuscar = strtoupper($data->DESCRIPCION_MAT) . ' ' . strtoupper($data->DESCRIPCION_MAT_TRADUCCION) . ' ' .
                    $data->PART_NUMBER . ' ' . $data->NOMBRE_MAR;

            $agregarElemento = false; //asumimos que no encuentra lo que busca
            $cuentaOcurrencia = 1; // se asume coincidencia completa de la palabra dentro del texto de busqueda

            $sumaOcurrencia = 0; //

            if (count($palabras) > 1) { //hay más de una palabra en el texto de búsqueda
//$cuentaOcurrencia = count($palabras); //se cambia para calcular el porcentaje de acierto de las palabras y ordenar por este resultado
                $textoBuscar = filter_var($textoBuscar, FILTER_SANITIZE_STRING);
                for ($pal = 0; $pal < count($palabras); $pal ++) {
                    $search = filter_var($palabras[$pal], FILTER_SANITIZE_STRING);
                    if (stripos($textoBuscar, $search) != FALSE) {
                        $agregarElemento = true; //alguna de las palabras de búsqueda fue encontrada
                        $sumaOcurrencia += 1 * (strlen($palabras[$pal]) / strlen($texto));
                    }
                }
            } else {
                if (stripos($textoBuscar, $texto) != FALSE) {
                    $agregarElemento = true;
                    $sumaOcurrencia = 1;
                }
            }

            if ($agregarElemento == TRUE) {
//$cuentaOcurrencia = $sumaOcurrencia / $cuentaOcurrencia;
//Logger::debug('*** AGREGAR *** ' . $data->DESCRIPCION_MAT . ' -> ' . $sumaOcurrencia);
                if ($sumaOcurrencia > 0.25) {
                    $result[] = [
                        'descrip' => $data->DESCRIPCION_MAT,
                        'id_marca' => $data->ID_MARCA,
                        'num_part' => $data->PART_NUMBER,
                        'precio_unidad' => DwOnline::cambiarDecimal($data->PRECIO_UNITARIO),
                        'id_material' => $data->ID_MATERIAL,
                        'cod_material' => $data->CODIGO_MATERIAL,
                        'marca' => $data->NOMBRE_MAR,
                        'unidad' => $data->NOMBRE_UNIDAD_MATERIAL,
                        'procedencia' => $data->TIPO_ORDEN,
                        'proveedor' => $data->NOMBRE_PROVEEDOR,
                        'codpil' => $data->CODIGO_MATERIAL,
                        'fecha' => $data->FECHA_COMPRA,
                        'numero_orden' => $data->NUMERO_ORDEN,
                        'ocurrencia' => $cuentaOcurrencia,
                        'origen' => 'ws'
                    ];
                }
            }
        }

        usort($result, ["Matews", "cmp"]);

//        Logger::debug($result);

        return $result;
    }

}
