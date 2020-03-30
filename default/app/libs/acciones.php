<?php

/**
 * Backend - KumbiaPHP Backend
 * PHP version 5
 * LICENSE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * ERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package Helper
 * @license http://www.gnu.org/licenses/agpl.txt GNU AFFERO GENERAL PUBLIC LICENSE version 3.
 * @author Manuel José Aguirre Garcia <programador.manuel@gmail.com>
 */
Load::models('sistema/auditorias');

class Acciones {

    public static function add($accion_realizada, $tabla_afectada = NULL, $metodo, $id) {
        try {
            if (Session::get('id') && Config::get('config.custom.guardar_auditorias') == 'On') {
                $obj = new Auditorias();
                $obj->usuarios_id = Session::get('id');
                $obj->accion_realizada = strip_tags($accion_realizada);
                $obj->id_tabla = $id;
                $obj->tipo = $metodo;
                $obj->tabla_afectada = strtoupper(strip_tags($tabla_afectada));
                $obj->ip = (Session::get('ip')) ? Session::get('ip') : DwUtils::getIp();
                $obj->router = DwUtils::setUrl();
                $obj->navegador = DwOnline::getBrowser($_SERVER['HTTP_USER_AGENT']);
                $obj->sistema = DwOnline::getPlatform($_SERVER['HTTP_USER_AGENT']);
                $obj->create();
            }
        } catch (KumbiaException $e) {
            View::exception($e);
        }
    }

}
