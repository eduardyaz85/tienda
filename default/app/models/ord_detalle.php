<?php

/**
 * Asdin - Web | App
 *
 * Descripcion: Modelo encargado de registrar el detalle de ordenes
 *
 * @category    
 * @package     Models 
 * @author      Eduardo Yánez (asdin_shop@yahoo.com)
 * @copyright   Copyright (c) 2019 Asdin
 * @revision    1.0
 */
class OrdDetalle extends ActiveRecord {

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
        $this->has_many('cat_master');
        $this->validates_presence_of('cantidad', 'message: Ingresa la cantidad');
    }

    /**
     * Método para setear
     * 
     * @param array $data
     * @return
     */
    public static function setOrdenDetalle($method, $data, $optData = NULL) {
        $obj = new OrdDetalle($data);
        if ($optData) {
            $obj->dump_result_self($optData);
        }
//        DwOnline::pr($obj);
//        die();
        $obj->usuarios_id = Session::get('id');

        if ($method != 'delete') {
            if (empty($obj->precio_venta)) {
                Flash::info('Indica el precio del articulo');
                return FALSE;
            }
            if (empty($obj->cantidad)) {
                Flash::info('Indica la cantidad');
                return FALSE;
            }
            if (empty($obj->$obj->valor_total)) {
                $obj->valor_total = $obj->cantidad * $obj->precio_venta;
            }

            $old = (isset($obj->id)) ? $obj->count("cat_master_id='$obj->cat_master_id' AND ord_cabecera_id='$obj->ord_cabecera_id' AND id!= $obj->id") : $obj->count("cat_master_id='$obj->cat_master_id' AND ord_cabecera_id='$obj->ord_cabecera_id'");
            if ($old) {
                $obj->rollback();
                Flash::info('Lo sentimos, pero ya se encuentra un articulo registrado bajo el mismo nombre.<br>' . $obj->descripcion);
                return FALSE;
            }
        } else {
            //Valido el ID
            $obj->id = Filter::get($obj->id, 'int');
            if (empty($obj->id)) {
                Flash::error('El id no existe');
                return FALSE;
            }
            $obj->find_first("columns: ord_detalle.*, cat_master.descripcion", "join: INNER JOIN cat_master ON cat_master.id = ord_detalle.cat_master_id", "conditions: ord_detalle.id = $obj->id");
            if ((Session::get('perfil_id') > Perfil::ADMIN) && (Session::get('id') != $obj->usuarios_id)) {
                Flash::error('Tu no tienes los permisos para anular los registros de este detalle');
                return FAlSE;
            }
        }
//        DwOnline::pr($obj);
//        die();
        if ($method == 'delete') {
            $rs = $obj->$method();
            return ($rs) ? $obj : FALSE;
        }
        return ($obj->$method()) ? $obj->getInformacionDetalleCotizacion($obj->id) : FALSE;
    }

    /**
     * Método que devuelve las catalogoes paginadas o para un select
     * @param int $pag Número de página a mostrar.
     * @return ActiveRecord
     */
    public function getListadoDetalleCotizacion($factura_id, $page = 0, $order = '') {
        $colm = "ord_detalle.*, cat_master.mpn, cat_master.descripcion, cat_master.detalle, marca.marca, impuestos.valor ";
        $join = 'INNER JOIN cat_master ON cat_master.id = ord_detalle.cat_master_id ';
        $join .= 'LEFT JOIN impuestos ON impuestos.id = ord_detalle.impuestos_id ';
        $join .= 'LEFT JOIN marca ON marca.id = cat_master.marca_id ';
        $cond = "ord_detalle.ord_cabecera_id = $factura_id";
        $order = $this->get_order($order, 'id');
        if ($page) {
            return $this->paginated("columns: $colm", "join: $join", "conditions: $cond", "order: $order", "page: $page");
        }
        return $this->find("columns: $colm", "join: $join", "conditions: $cond", "order: $order");
    }

    /**
     * Método para obtener la información del detalle de una catalogo
     * @return type
     */
    public function getInformacionDetalleCotizacion($factura_id) {
        $colm = "ord_detalle.*, cat_master.descripcion, cat_master.mpn, cat_master.codigo, cat_master.instock, pr.precio_venta AS pventa, pr.precio_distribuidor, ci.impuestos_id, impuestos.valor, impuestos.impuesto ";
        $join = 'INNER JOIN cat_master ON cat_master.id = ord_detalle.cat_master_id ';
        $join .= "INNER JOIN ( SELECT cat_snc.cat_master_id, cat_snc.precio_venta, cat_snc.precio_distribuidor, cat_snc.registro_at FROM cat_snc GROUP BY cat_snc.cat_master_id DESC ) pr ON pr.cat_master_id = cat_master.id ";
        $join .= "LEFT JOIN ( SELECT cat_master_id, impuestos_id FROM cat_impuestos GROUP BY cat_master_id ASC ) ci ON ci.cat_master_id = cat_master.id ";
        $join .= "LEFT JOIN impuestos ON impuestos.id = ci.impuestos_id ";
        $cond = "ord_detalle.id = $factura_id";
        return $this->find_first("columns: $colm", "join: $join", "conditions: $cond");
    }

    /**
     * Método para obtener los totales del detalle catalogo
     */
    public function getTotalDetalleCotizacion($factura_id) {
        $columns = 'ord_detalle.id, ord_detalle.ord_cabecera_id, sum(ord_detalle.valor_total) AS total ';
        $conditions = "ord_detalle.ord_cabecera_id = $factura_id ";
        return $this->find_first("columns: $columns", "conditions: $conditions");
    }

    /**
     * Método para listar articulos del carrito por numero de orden
     */
    /*    public function getListadoCarritoPorOrden($codigo, $order = 'order.fecha_compra.asc', $page = 0) {
      $codigo = Filter::get($codigo, 'int');
      if (empty($codigo)) {
      return NULL;
      }
      $columns = 'ord_detalle.*, ord_cabecera.numero, cat_master.codigo, cat_master.sku, cat_master.mpn, cat_master.instock, cat_master.descripcion, marca.marca ';
      $join = 'INNER JOIN ord_cabecera ON ord_cabecera.id = ord_detalle.ord_cabecera_id ';
      $join .= 'LEFT JOIN cat_master ON cat_master.id = ord_detalle.cat_master_id ';
      $join .= 'LEFT JOIN marca ON marca.id = cat_master.marca_id ';
      $conditions = "ord_cabecera.id = $codigo";

      $order = $this->get_order($order, 'fecha_compra');

      if ($page) {
      return $this->paginated("columns: $columns", "join: $join", "conditions: $conditions", "order: $order", "page: $page");
      }
      return $this->find("columns: $columns", "join: $join", "conditions: $conditions", "order: $order");
      } */

    /**
     * Método para registro del catalogo en array masivo
     */
    public static function setRegistroItemsCarrito($data, $optData = null) {
        $obj = new OrdDetalle($data);
        $old = new OrdDetalle();
        $catalogo = new CatMaster();

        $obj->begin();
        if (!empty($optData)) {
            $skuList = '';
            //guardo el carrito
            foreach ($optData as $key => $value) {
                //verifico si existe el articulo en la orden para actualizar la cantidad
                $orden_item = $old->find_first("ord_cabecera_id = $obj->ord_cabecera_id AND cat_master_id= $value->codigo");

                $old_catalogo = $catalogo->getArticuloBackEnd($value->codigo);

                if (empty($old_catalogo->id)) {
                    Flash::error("Ops! Ha ocurrido un error, no existe: $value->articulo ");
                } else {
                    $sku = $skuList = empty($skuList) ? "$old_catalogo->sku" : ",$old_catalogo->sku";

                    $url = DwConect::getProducts($skuList); //pedir url
                    $stream = DwConect::getSslPage($url);
                    $jsonPHP = json_decode($stream);

                    if (empty($jsonPHP[0]->Sku)){
                        Flash::error("Producto no veridicado $value->articulo");
                    }

                    $data = array();
                    if (!empty($orden_item->cat_master_id) == $value->codigo) {
                        $metodo = 'update';
                        $data['id'] = $orden_item->id;
                        $cantidad = $value->quantity + $orden_item->cantidad;
                    } else {
                        $metodo = 'create';
                        $cantidad = $value->quantity;
                    }
                    if (empty($jsonPHP[0]->InStock)){
                        Flash::error("Producto SIN STOCK $value->articulo");
                    }
                    $data['cat_master_id'] = $value->codigo;
                    $data['ord_cabecera_id'] = $obj->ord_cabecera_id;
                    $data['instock'] = $jsonPHP[0]->InStock;
                    $data['cantidad'] = $cantidad;
                    $data['precio_venta'] = $old_catalogo->precio_venta;
                    if (Session::get('perfil_id') == Perfil::DISTRIBUIDOR) {
                        $data['precio_distribuidor'] = $old_catalogo->precio_distribuidor;
                        $data['valor_total'] = $old_catalogo->precio_distribuidor * $cantidad;
                    } else {
                        $data['valor_total'] = $old_catalogo->precio_venta * $cantidad;
                    }
                    $data['descuento'] = $value->discount_amount;
                    $data['impuestos_id'] = $old_catalogo->impuestos_id;
                    $data['usuarios_id'] = Session::get('id');
                    $data['estado'] = '1';

                    if ($rs = !OrdDetalle::setOrdenDetalle($metodo, NULL, $data)) {
                        Flash::info('Se ha producido un error al registrar el producto en el carrito');
                        return 'cancel';
                    }
                }
            }
        }
        $obj->commit();
        return TRUE;
    }

    /**
     * Método para totalizar la cantidad de articulos en el carrito
     */
    public function getTotalItems($orden) {
        $colm = "ord_detalle.id, sum(ord_detalle.cantidad) AS total";
        $cond = "ord_cabecera_id = '$orden' ";
        return $this->find_first("columns: $colm", "conditions: $cond");
    }

    /**
     * Método para totalizar la cantidad de articulos en el carrito
     */
    public function getSumaTotalItems($orden) {
        $colm = "ord_detalle.id, sum(ord_detalle.cantidad*ord_detalle.precio_venta) AS total";
        $cond = "ord_cabecera_id = '$orden' ";
        return $this->find_first("columns: $colm", "conditions: $cond");
    }

}
