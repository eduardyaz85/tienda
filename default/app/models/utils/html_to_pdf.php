<?php

// Require composer autoload
require_once APP_PATH . '../../vendor/autoload.php';

use Mpdf\Mpdf;
use Mpdf\HTMLParserMode;
use Mpdf\Output\Destination;

class HtmlToPdf {

    public static function getOrdenPdf($empresa, $orden, $detalle, $tipo) {
        // Activa el almacenamiento en búfer de la salida
        ob_start();
        // Carga el contenido del partial
        View::partial('reportes/pdf', '', ['empresa' => $empresa, 'orden' => $orden, 'detalle' => $detalle]);
        // Obtiene en $html el contenido del búfer actual y elimina el búfer de salida actual
        $html = ob_get_clean();
        // Crea una instancia de la clase y le pasa el directorio temporal
        $mpdf = new Mpdf(['tempDir' => APP_PATH . 'temp/mpdf', 'margin-top' => 30]);
        $mpdf->justifyB4br = true;
        $mpdf->SetTitle("Asdin");
        $mpdf->SetAuthor("Asdin");
        // Carga el CSS externo
        $stylesheet = file_get_contents('css/pdf.css');
        $mpdf->WriteHTML($stylesheet, HTMLParserMode::HEADER_CSS);
        $mpdf->margin_header = // Escribe el contenido HTML (Template + View):
                $mpdf->WriteHTML($html);
        // Título por defecto
        if (!isset($orden->numero)) {
            $orden->numero = 'Report';
        }

        if ($tipo == 'F') {
            // Guarda un archivo PDF en un directorio
            $file = dirname(APP_PATH) . "/public/files/pdf/$orden->numero.pdf";
            $mpdf->Output($file, "F");
            @chmod("$file", 0777); //Permisos   
            // Devuelve true
            $exists = is_file($file);
            if (!empty($exists)) {
                HtmlToPdf::getSendPdf($empresa, $orden, $detalle);
            }
            return $exists;
        } else {
            // Obliga la descarga del PDF
            $mpdf->Output("$orden->numero.pdf", Destination::DOWNLOAD);
            exit;
        }
    }

    public static function getSendPdf($empresa, $orden, $detalle) {
        $catCorreo = new CatCorreo();
        $catCorreo->enviaResumenPedido($empresa, $orden, $detalle);
        return TRUE;
    }

}
