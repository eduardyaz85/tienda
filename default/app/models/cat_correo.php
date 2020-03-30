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
class CatCorreo {

    //Se desabilita el logger para no llenar el archivo de "basura"
    public $logger = FALSE;

    /*
     * ========================================================================
     *  MANEJO CORREO
     * ======================================================================== 
     */

    /**
     * Envia correo resumen pedido
     */
    public function enviaResumenPedido($empresa, $orden, $detalle) {
        $correos = array($empresa->email, $orden->email);
        $cliente = ucwords(strtolower($orden->nombres)) . ' ' . ucwords(strtolower($orden->razon_social));

        if ($correos) {
            //TODO que este contenido del correo lo tome de una plantilla.
            if ($orden->tipo_orden == 'o') {
                $pedido = 'órden';
            } else {
                $pedido = 'cotización';
            }
            $asunto = "Bienvenido " . $cliente . ", tu $pedido es $orden->numero";

            // Activa el almacenamiento en búfer de la salida
            ob_start();
            // Carga el contenido del partial
            View::partial('correo/resumen', '', ['cliente' => $cliente, 'data' => $correos, 'orden' => $orden]);
            // Obtiene en $html el contenido del búfer actual y elimina el búfer de salida actual
            $body = ob_get_clean();

            if (Correo::send($correos, $cliente, $asunto, $body, $orden->numero)) {
                return true;
            } else {
                return false;
            }
        } else {
            Flash::error('No existe registro del email ingresado.');
        }
    }

}
