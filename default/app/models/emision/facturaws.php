<?php

class Facturaws {

    public function esign($data) {
        try {
            $firmador = APP_PATH . 'libs/jar/SignSRI.jar';
            $firma = APP_PATH . 'libs/jar/firma.p12';
            $documento = APP_PATH . 'libs/jar/factura.xml';
            $guarda = APP_PATH . 'libs/jar/firmados/';

            $firm = " java -jar $firmador $firma AsdiN10090 $documento $guarda firmado.xml";
            $result = shell_exec($firm);

            if (trim($result) === "ok") {
                Flash::valid('Documento Firmado Correctamente...');
                return TRUE;
            } else {
                Flash::valid('Error al firmar...');
                return FALSE;
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return;
    }

}
