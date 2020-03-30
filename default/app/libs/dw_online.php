<?php

/**
 * Asdin - Web | App
 *
 * @category    Librería para el manejo de fechas, generar codigo autoincremental
 * @package     Libs
 * @copyright   Copyright (c) 2019
 */
class DwOnline {

    /**
     * Función calcula precio con impuesto
     */
    static public function setPrecios($precio = '', $impuesto = '') {
        $nuevo = 0;
        if (!empty($precio) && !empty($impuesto)) {
            $nuevo = round($precio + ($precio * $impuesto) / 100, 2);
        } else {
            $nuevo = 0;
        }
        return $nuevo;
    }

    /**
     * Indica el estado de la orden de compra
     */
    static public function setEstadoOrden($estado) {
        if ($estado == 1) {
            $estado = '<span class="label label-warning">Generado</span>';
        } else if ($estado == 2) {
            $estado = '<span class="label label-primary">Enviado</span>';
        } else if ($estado == 3) {
            $estado = '<span class="label label-info">Aprobado Cliente</span>';
        } else if ($estado == 4) {
            $estado = '<span class="label label-success">Entregado</span>';
        } else if ($estado == 0) {
            $estado = '<span class="label label-danger">Cancelado</span>';
        }
        return $estado;
    }

    /**
     * Función consulta el codigo de documento de acuerdo al tipo
     */
    static public function setCodigoDocumento($codigo = '', $campo = '') {
        $objTablasTipos = new TablasTipos();
        if (!empty($codigo)) {
            $tabla = $objTablasTipos->find_first("id = $codigo");
        } else if (!empty($campo)) {
            $tabla = $objTablasTipos->find_first("codigo = '$campo'");
        } else {
            return FALSE;
        }
        return $tabla;
    }

    /**
     * Función calcular rango de fechas
     */
    static function check_in_range($fecha_inicio, $fecha_fin, $fecha) {
        $fecha_inicio = strtotime($fecha_inicio);
        $fecha_fin = strtotime($fecha_fin);
        $fecha = strtotime($fecha);

        if (($fecha >= $fecha_inicio) && ($fecha <= $fecha_fin)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Función para visualizar los arreglos preformateados
     */
    static public function pr($array) {
        echo '<pre>';
        print_r($array);
        echo '</pre>';
    }

    /**
     * Función genera el nuevo numero secuencial
     */
    static public function setNumeroSecuencial($codigo, $ceros = 5) {
        list($text, $nume) = explode("-", $codigo);
        $numero = $nume + 1;
        $cod = sprintf("%0" . $ceros . "s", $numero);
        return $text . '-' . $cod;
    }

    /**
     * Función genera el nuevo numero secuencial ordenes
     */
    static public function setSecuencialOrden($codigo, $ceros = 3) {
        list($text1, $text2, $nume) = explode("-", $codigo);
        $numero = $nume + 1;
        $cod = sprintf("%0" . $ceros . "s", $numero);
        return $text1 . '-' . $text2 . '-' . $cod;
    }

    /**
     * Función genera una cadena de 9 numeros
     */
    static public function setNumeroDocumento($numero, $ceros = 9) {
        $numero = $numero + 1;
        $cod = sprintf("%0" . $ceros . "s", $numero);
        return $cod;
    }

    /**
     * Función calcula dias
     */
    static public function CalcularDias($fecha_i, $fecha_f) {
        $dias = (strtotime($fecha_i) - strtotime($fecha_f)) / 86400;
        $dias = abs($dias);
        $dias = floor($dias);
        return $dias;
    }

    /**
     * Función recorta el nombre a 3 caracteres
     */
    static public function recortarDocumento($doc = '', $pos = 0, $can = 0) {
        return substr($doc, $pos, $can);
    }

    /**
     * Cambia decimal coma por punto 
     */
    static public function cambiarDecimal($String) {
        $String = str_replace(",", ".", $String);
        return $String;
    }

    /**
     * Indica el estado de la orden de compra
     */
    static public function setEstadoOrden1($estado) {
        if ($estado == 0) {
            $Cestado = 'Generado';
        } else if ($estado == 1) {
            $Cestado = 'Confirmado';
        } else if ($estado == 2) {
            $Cestado = 'Esperando Confirmacion';
        } else if ($estado == 3) {
            $Cestado = 'Aprobado Cliente';
        } else if ($estado == 4) {
            $Cestado = 'Enviado';
        } else if ($estado == 5) {
            $Cestado = 'Entregado';
        } else if ($estado == 6) {
            $Cestado = 'Cancelado';
        }
        return $Cestado;
    }

    /**
     * Indica el estado de la orden de compra
     */
    static public function setEstadoCompraClass($estado) {
        if ($estado == 0) {
            $Cestado = 'badge-default';
        } else if ($estado == 1) {
            $Cestado = 'badge-success';
        } else if ($estado == 2) {
            $Cestado = 'badge-warning';
        } else if ($estado == 3) {
            $Cestado = 'badge-info';
        } else if ($estado == 4) {
            $Cestado = 'badge-primary';
        } else if ($estado == 5) {
            $Cestado = 'badge-success';
        } else if ($estado == 6) {
            $Cestado = 'badge-danger';
        }
        return $Cestado;
    }

    /**
     * Estados Documentos
     */
    static public function estadoDocumentos($tipo) {
        if ($tipo == 1) {
            $estado = 'INFORME OPERATIVIDAD';
        } else if ($tipo == 2) {
            $estado = 'CERTIFICADO CALIBRACION';
        } else if ($tipo == 5) {
            $estado = 'RESPALDO BAJA';
        }
        return $estado;
    }

    /**
     * Estados actas
     */
    static public function estadoActas($tipo) {
        if ($tipo == 1) {
            $estado = '<span class="label label-primary">GENERADO</span>';
        } else if ($tipo == 2) {
            $estado = '<span class="label label-success">PROCESADO</span>';
        } else if ($tipo == 3) {
            $estado = '<span class="label label-danger">FINALIZADO</span>';
        } else if ($tipo == 0) {
            $estado = '<span class="label label-info">ELIMINADO</span>';
        }
        return $estado;
    }

    /**
     * Función generar slug excel
     */
    function ganeraSlugExcel($valor) {
        if (str_word_count($valor) > 1) {
            $separardor = '';
            $con = 2;
            $array = explode(' ', $valor);
            foreach ($array as $key => $value) {
                if (str_word_count($valor) == 2 && $con > 0) {
                    $separardor .= substr($value, 0, $con);
                    $con--;
                } else {
                    $separardor .= substr($value, 0, 1);
                }
            }
        } else {
            $separardor = substr($valor, 0, 3);
        }
        return $separardor;
    }

    /**
     * Función calcular edad
     */
    static public function calculaedad($fechanacimiento) {
        list($ano, $mes, $dia) = explode("-", $fechanacimiento);
        $ano_diferencia = date("Y") - $ano;
        $mes_diferencia = date("m") - $mes;
        $dia_diferencia = date("d") - $dia;
        if ($dia_diferencia < 0 || $mes_diferencia < 0)
            $ano_diferencia--;
        return $ano_diferencia;
    }

    /**
     * Función hora de recepcion
     */
    static public function horaRecepcion($hora) {
        list($hora1, $en, $dia) = explode("-", $hora);
        $hora_uno = date("Y") - $hora1;
        $mes_diferencia = date("m") - $en;
        $dia_diferencia = date("d") - $hora2;
        return $hora_uno;
    }

    /**
     * Función quitar signos fecha
     */
    static public function checkFecha($fecha, $separador = '-') {
        list($yy, $mm, $dd) = explode($separador, $fecha);
        if (is_numeric($yy) && is_numeric($mm) && is_numeric($dd)) {
//            return checkdate($yy, $mm, $dd);
        }
        $fechaNuev = $yy . $mm . $dd;
        return $fechaNuev;
    }

    /**
     * Función alterar fecha
     */
    static public function alterarFecha($fecha, $tipo = 1) {
        if ($tipo == 1) {
            return date("d-m-Y", strtotime($fecha));
        } else if ($tipo = 2) {
            return date("Y-m-d", strtotime($fecha));
        }
    }

    /**
     * Función formatear fecha
     */
    static public function formatearFecha($fecha, $separador = '/') {
        $dd = substr($fecha, 0, 2);
        $mm = substr($fecha, 2, 2);
        $yy = substr($fecha, 4, 4);
        $fechaNuev = $dd . $separador . $mm . $separador . $yy;
        return $fechaNuev;
    }

    /**
     * Función fecha con formato
     */
    static public function FechaFormato($fecha) {
        list($yy, $mm, $dd) = explode("-", $fecha);
        if (is_numeric($yy) && is_numeric($mm) && is_numeric($dd)) {
//            return checkdate($yy, $mm, $dd);
        }
        $arrayMeses = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');

        $arrayDias = array('Domingo', 'Lunes', 'Martes',
            'Miercoles', 'Jueves', 'Viernes', 'Sabado');

        return $arrayDias[date('w')] . ", " . $dd . " de " . $arrayMeses[$mm - 1] . " de " . $yy;
    }

    /**
     * Función quitar signos fecha
     */
    static public function checkHora($hora) {
        list($hh, $mm) = explode(":", $hora);
        if (is_numeric($hh) && is_numeric($mm)) {
//            return checkdate($hh, $mm, $dd);
        }
        $horaNuev = $hh . $mm;
        return $horaNuev;
    }

    static public function DiasFecha($fecha, $dias) {
        $nuevafecha = strtotime($dias . " day", strtotime($fecha));
        $nuevafecha = date('Y-m-d', $nuevafecha); //formatea nueva fecha
        return $nuevafecha; //retorna valor de la fecha
    }

    /**
     * Control calendario agenda
     */
    static public function fechaCalendario($valor) {
        $timer = explode(" ", $valor);
        $fecha = explode("-", $timer[0]);
        $fechex = $fecha[2] . "/" . $fecha[1] . "/" . $fecha[0];
        return $fechex;
    }

    /**
     * Separo 001 del ruc
     */
    static public function consultaRuc($num) {
        // pega os primeros 10 dígitos
        $value = substr($num, 0, 10);
        return $value;
    }

    /**
     * Busca en calendario agenda
     */
    static public function buscarEnArray($fecha, $array) {
        $total_eventos = count($array);
        for ($e = 0; $e < $total_eventos; $e++) {
            if ($array[$e]["fecha"] == $fecha)
                return true;
        }
    }

    /**
     * Detectar Plataforma o sistema operativo
     */
    static public function getPlatform($user_agent) {
        if (strpos($user_agent, 'Windows NT 10.0') !== FALSE)
            $plataforma = "Windows 10";
        elseif (strpos($user_agent, 'Windows NT 6.3') !== FALSE)
            $plataforma = "Windows 8.1";
        elseif (strpos($user_agent, 'Windows NT 6.2') !== FALSE)
            $plataforma = "Windows 8";
        elseif (strpos($user_agent, 'Windows NT 6.1') !== FALSE)
            $plataforma = "Windows 7";
        elseif (strpos($user_agent, 'Windows NT 6.0') !== FALSE)
            $plataforma = "Windows Vista";
        elseif (strpos($user_agent, 'Windows NT 5.1') !== FALSE)
            $plataforma = "Windows XP";
        elseif (strpos($user_agent, 'Windows NT 5.2') !== FALSE)
            $plataforma = 'Windows 2003';
        elseif (strpos($user_agent, 'Windows NT 5.0') !== FALSE)
            $plataforma = 'Windows 2000';
        elseif (strpos($user_agent, 'Windows ME') !== FALSE)
            $plataforma = 'Windows ME';
        elseif (strpos($user_agent, 'Win98') !== FALSE)
            $plataforma = 'Windows 98';
        elseif (strpos($user_agent, 'Win95') !== FALSE)
            $plataforma = 'Windows 95';
        elseif (strpos($user_agent, 'WinNT4.0') !== FALSE)
            $plataforma = 'Windows NT 4.0';
        elseif (strpos($user_agent, 'Windows Phone') !== FALSE)
            $plataforma = 'Windows Phone';
        elseif (strpos($user_agent, 'Windows') !== FALSE)
            $plataforma = 'Windows';
        elseif (strpos($user_agent, 'iPhone') !== FALSE)
            $plataforma = 'iPhone';
        elseif (strpos($user_agent, 'iPad') !== FALSE)
            $plataforma = 'iPad';
        elseif (strpos($user_agent, 'Debian') !== FALSE)
            $plataforma = 'Debian';
        elseif (strpos($user_agent, 'Ubuntu') !== FALSE)
            $plataforma = 'Ubuntu';
        elseif (strpos($user_agent, 'Slackware') !== FALSE)
            $plataforma = 'Slackware';
        elseif (strpos($user_agent, 'Linux Mint') !== FALSE)
            $plataforma = 'Linux Mint';
        elseif (strpos($user_agent, 'Gentoo') !== FALSE)
            $plataforma = 'Gentoo';
        elseif (strpos($user_agent, 'Elementary OS') !== FALSE)
            $plataforma = 'ELementary OS';
        elseif (strpos($user_agent, 'Fedora') !== FALSE)
            $plataforma = 'Fedora';
        elseif (strpos($user_agent, 'Kubuntu') !== FALSE)
            $plataforma = 'Kubuntu';
        elseif (strpos($user_agent, 'Linux') !== FALSE)
            $plataforma = 'Linux';
        elseif (strpos($user_agent, 'FreeBSD') !== FALSE)
            $plataforma = 'FreeBSD';
        elseif (strpos($user_agent, 'OpenBSD') !== FALSE)
            $plataforma = 'OpenBSD';
        elseif (strpos($user_agent, 'NetBSD') !== FALSE)
            $plataforma = 'NetBSD';
        elseif (strpos($user_agent, 'SunOS') !== FALSE)
            $plataforma = 'Solaris';
        elseif (strpos($user_agent, 'BlackBerry') !== FALSE)
            $plataforma = 'BlackBerry';
        elseif (strpos($user_agent, 'Android') !== FALSE)
            $plataforma = 'Android';
        elseif (strpos($user_agent, 'Mobile') !== FALSE)
            $plataforma = 'Firefox OS';
        elseif (strpos($user_agent, 'Mac OS X+') || strpos($user_agent, 'CFNetwork+') !== FALSE)
            $plataforma = 'Mac OS X';
        elseif (strpos($user_agent, 'Macintosh') !== FALSE)
            $plataforma = 'Mac OS Classic';
        elseif (strpos($user_agent, 'OS/2') !== FALSE)
            $plataforma = 'OS/2';
        elseif (strpos($user_agent, 'BeOS') !== FALSE)
            $plataforma = 'BeOS';
        elseif (strpos($user_agent, 'Nintendo') !== FALSE)
            $plataforma = 'Nintendo';
        else
            $plataforma = 'Unknown Platform';

        return $plataforma;
    }

    /**
     * Detectar Navegador visitante
     */
    static public function getBrowser($user_agent) {
        if (strpos($user_agent, 'Maxthon') !== FALSE)
            $agente = "Maxthon";
        elseif (strpos($user_agent, 'SeaMonkey') !== FALSE)
            $agente = "SeaMonkey";
        elseif (strpos($user_agent, 'Vivaldi') !== FALSE)
            $agente = "Vivaldi";
        elseif (strpos($user_agent, 'Arora') !== FALSE)
            $agente = "Arora";
        elseif (strpos($user_agent, 'Avant Browser') !== FALSE)
            $agente = "Avant Browser";
        elseif (strpos($user_agent, 'Beamrise') !== FALSE)
            $agente = "Beamrise";
        elseif (strpos($user_agent, 'Epiphany') !== FALSE)
            $agente = 'Epiphany';
        elseif (strpos($user_agent, 'Chromium') !== FALSE)
            $agente = 'Chromium';
        elseif (strpos($user_agent, 'Iceweasel') !== FALSE)
            $agente = 'Iceweasel';
        elseif (strpos($user_agent, 'Galeon') !== FALSE)
            $agente = 'Galeon';
        elseif (strpos($user_agent, 'Edge') !== FALSE)
            $agente = 'Microsoft Edge';
        elseif (strpos($user_agent, 'Trident') !== FALSE) //IE 11
            $agente = 'Internet Explorer';
        elseif (strpos($user_agent, 'MSIE') !== FALSE)
            $agente = 'Internet Explorer';
        elseif (strpos($user_agent, 'Opera Mini') !== FALSE)
            $agente = "Opera Mini";
        elseif (strpos($user_agent, 'Opera') || strpos($user_agent, 'OPR') !== FALSE)
            $agente = "Opera";
        elseif (strpos($user_agent, 'Firefox') !== FALSE)
            $agente = 'Mozilla Firefox';
        elseif (strpos($user_agent, 'Chrome') !== FALSE)
            $agente = 'Google Chrome';
        elseif (strpos($user_agent, 'Safari') !== FALSE)
            $agente = "Safari";
        elseif (strpos($user_agent, 'iTunes') !== FALSE)
            $agente = 'iTunes';
        elseif (strpos($user_agent, 'Konqueror') !== FALSE)
            $agente = 'Konqueror';
        elseif (strpos($user_agent, 'Dillo') !== FALSE)
            $agente = 'Dillo';
        elseif (strpos($user_agent, 'Netscape') !== FALSE)
            $agente = 'Netscape';
        elseif (strpos($user_agent, 'Midori') !== FALSE)
            $agente = 'Midori';
        elseif (strpos($user_agent, 'ELinks') !== FALSE)
            $agente = 'ELinks';
        elseif (strpos($user_agent, 'Links') !== FALSE)
            $agente = 'Links';
        elseif (strpos($user_agent, 'Lynx') !== FALSE)
            $agente = 'Lynx';
        elseif (strpos($user_agent, 'w3m') !== FALSE)
            $agente = 'w3m';
        else
            $agente = 'No hemos podido detectar su navegador';

        return $agente;
    }

    /*
     * Elimina caracteres especiales, acentos, comillas 
     */

    static public function limpiar($String) {
        $String = str_replace(array('á', 'à', 'â', 'ã', 'ª', 'ä'), "a", $String);
        $String = str_replace(array('Á', 'À', 'Â', 'Ã', 'Ä'), "A", $String);
        $String = str_replace(array('Í', 'Ì', 'Î', 'Ï'), "I", $String);
        $String = str_replace(array('í', 'ì', 'î', 'ï'), "i", $String);
        $String = str_replace(array('é', 'è', 'ê', 'ë'), "e", $String);
        $String = str_replace(array('É', 'È', 'Ê', 'Ë'), "E", $String);
        $String = str_replace(array('ó', 'ò', 'ô', 'õ', 'ö', 'º'), "o", $String);
        $String = str_replace(array('Ó', 'Ò', 'Ô', 'Õ', 'Ö'), "O", $String);
        $String = str_replace(array('ú', 'ù', 'û', 'ü'), "u", $String);
        $String = str_replace(array('Ú', 'Ù', 'Û', 'Ü'), "U", $String);
        $String = str_replace(array('[', '^', '´', '`', '¨', '~', ']'), "", $String);
        $String = str_replace("ç", "c", $String);
        $String = str_replace("Ç", "C", $String);
        $String = str_replace("ñ", "n", $String);
        $String = str_replace("Ñ", "N", $String);
        $String = str_replace("Ý", "Y", $String);
        $String = str_replace("ý", "y", $String);

        $String = str_replace("&aacute;", "a", $String);
        $String = str_replace("&Aacute;", "A", $String);
        $String = str_replace("&eacute;", "e", $String);
        $String = str_replace("&Eacute;", "E", $String);
        $String = str_replace("&iacute;", "i", $String);
        $String = str_replace("&Iacute;", "I", $String);
        $String = str_replace("&oacute;", "o", $String);
        $String = str_replace("&Oacute;", "O", $String);
        $String = str_replace("&uacute;", "u", $String);
        $String = str_replace("&Uacute;", "U", $String);
        return $String;
    }

}
