<?php

/**
 *
 * Extension para renderizar los menús
 *
 * @category    Helpers
 * @package     Helpers
 */
Load::models('sistema/menu');

class DwMenuBackend {

    /**
     * Variable que contiene los menús 
     */
    protected static $_main = null;

    /**
     * Variable que contien los items del menú
     */
    protected static $_items = null;

    /**
     * Variabla para indicar el entorno
     */
    protected static $_entorno;

    /**
     * Variable para indicar el perfil
     */
    protected static $_perfil;

    /**
     * Método para cargar en variables los menús
     * @param type $perfil
     */
    public static function load($entorno, $perfil = NULL) {
        self::$_entorno = $entorno;
        self::$_perfil = $perfil;
        $menu = new Menu();
        if (self::$_main == NULL) {
            self::$_main = $menu->getListadoMenuPadres($entorno, $perfil);
        }
        if (self::$_items == NULL && self::$_main) {
            foreach (self::$_main as $menu) {
                self::$_items[$menu->menu] = $menu->getListadoSubmenu($entorno, $menu->id, $perfil);
            }
        }
    }

    /**
     * Método para renderizar el menú de escritorio
     */
    public static function desktop() {
        $route = trim(Router::get('module'), '/');
        $controller = trim(Router::get('controller'), '/');
        $html = "";
        if (!empty(self::$_main)) {
            $html .= '<ul class="sidebar-menu" data-widget="tree">' . PHP_EOL;
            $html .= '<li class="header">MENÚ</li>' . PHP_EOL;
            foreach (self::$_main as $main) {
                $active = (strtolower($main->menu) == strtolower($route)) ? 'active menu-open ' : null;
                if (self::$_entorno == Menu::BACKEND) {
                    if (empty($main->menu_id) && empty($main->recurso_id) && $main->url != '#') {
                        $html .= '<li class="' . $active . ' treeview  menu-open">' . PHP_EOL;
                        $html .= '<li class="' . $active . '">' . DwHtml::link($main->url, '<span>' . $main->menu . '</span>', null, trim($main->icono)) . '</li>' . PHP_EOL;
                    } else {

                        $html .= '<li class="' . $active . ' treeview">' . PHP_EOL;
                        $html .= DwHtml::link($main->url, '<span>' . strtoupper($main->menu) . '</span><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>', NULL, trim($main->icono)) . PHP_EOL;

                        $submenu = $main->getListadoSubmenu(self::$_entorno, $main->id, self::$_perfil);
                        if ($submenu) {
                            $html .= '<ul class="treeview-menu">' . PHP_EOL;
                            foreach ($submenu as $tmp) {
                                $rw = DwOnline::limpiar($tmp->menu);
                                $activeLi = (strtolower($rw) == strtolower($controller)) ? 'active' : null;
                                $html .= '<li class="' . $activeLi . '">' . DwHtml::link($tmp->url, strtoupper($tmp->menu), null, trim($tmp->icono)) . '</li>' . PHP_EOL;
                            }
                            $html .= '</ul>' . PHP_EOL;
                        }
                    }
                    $html .= "</li>" . PHP_EOL;
                }
            }
            $html .= '<li class="header">' . APP_NAME . '</li>' . PHP_EOL;
            $html .= "</ul>" . PHP_EOL;
        }
        return $html;
    }

}
