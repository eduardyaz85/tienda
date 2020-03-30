<?php

Load::models('banner');

class DwBanner {

    /**
     * Método para generar el banner principal
     * @param type $format
     */
    public static function getBannerIndex() {
        $html = '';
        $banner = new Banner();
        $banners = $banner->getBannerIndex();
        //genero el banner
        foreach ($banners as $value) {
//            DwOnline::pr($value->imagen);
//            die();
            $html .= '<div class="banner banner-1">' . PHP_EOL;
            $html .= DwHtml::img("upload/banner/$value->imagen", NULL, array('id' => 'img-banner')) . PHP_EOL;
            $html .= '<div class="banner-caption text-center">' . PHP_EOL;
//            $html .= '<h1>' . $value->label1 . '.</h1>' . PHP_EOL;
//            $html .= '<h3 class="white-color font-weak">' . $value->label2 . '</h3>' . PHP_EOL;
//            $html .= '<button class="primary-btn">Comprar</button>' . PHP_EOL;
            $html .= '</div>' . PHP_EOL;
            $html .= '</div>' . PHP_EOL;
        }
        return $html;
    }

}
