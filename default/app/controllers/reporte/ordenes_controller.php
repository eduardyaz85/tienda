<?php

Load::models('sistema/usuarios', 'utils/html_to_pdf');

class OrdenesController extends AppController {

    /**
     * Método para imprimir reportes a pdf
     * @param type $orden
     * @return type
     */
    public function pdf($key) {
        if (!$id = Security::getKey($key, 'dwl_orden', 'int')) {
            return FALSE;
        }

        $orden = new OrdCabecera();
        if (!$orden->getInformacionOrden($id)) {
            Flash::error('Lo sentimos, no se pudo establecer la información de la orden');
            return Redirect::to('index/');
        }
        $detalle = (new OrdDetalle)->getListadoDetalleCotizacion($orden->id);
        $empresa = (new Empresas)->getInformacionEmpresa(Empresas::EMPRESA);
        $this->orden = $orden;
        //Modifica el nombre del archivo a descargar
        $this->fileName = $orden->numero;
        //Llama a crear un PDF
        HtmlToPdf::getOrdenPdf($empresa, $orden, $detalle, $tipo = 'D');
        View::template(NULL);
    }

}
