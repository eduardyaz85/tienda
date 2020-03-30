<?php /** @noinspection SyntaxError */

class MyHtml
{
    public static function resolve($source)
    {
        return PUBLIC_PATH . $source;
    }

    public static function optionForSelect($data, $idField, $displayField, $useEOL = true)
    {
        $eol = '';
        if ($useEOL == true) {
            $eol = PHP_EOL;
        }
        $result = '<option>Seleccione</option>' . $eol;
        foreach ($data as $item) {
            $result .= '<option value="' . $item->$idField . '">' .
                $item->$displayField . '</option>' . $eol;
        }
        return $result;
    }

    public static function dataForMonths()
    {
        return ['1' => 'Enero', '2' => 'Febrero', '3' => 'Marzo', '4' => 'Abril',
            '5' => 'Mayo', '6' => 'Junio', '7' => 'Julio', '8' => 'Agosto',
            '9' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'];

    }

    public static function dataForYears()
    {
        $result = [];

        for ($i = intval(date('Y') - 1); $i <= intval(date('Y')); $i++) {
            $result[$i] = $i;
        }
        return $result;
    }
}