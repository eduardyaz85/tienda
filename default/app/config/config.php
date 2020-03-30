<?php

/**
 * KumbiaPHP Web Framework
 * Parámetros de configuracion de la aplicacion
 */
return [
    'application' => [
        /**
         * name: es el nombre de la aplicacion
         */
        'name' => 'Store Asdin',
        /**
         * production: On/Off
         */
        'production' => 'Off',
        /**
         * database: base de datos a utilizar
         */
        'database' => 'development',
        /**
         * dbdate: formato de fecha por defecto de la aplicacion
         */
        'dbdate' => 'YYYY-MM-DD',
        /**
         * debug: muestra los errores en pantalla (On/off)
         */
        'debug' => 'On',
        /**
         * log_exceptions: muestra las excepciones en pantalla (On/off)
         */
        'log_exceptions' => 'On',
        /**
         * cache_template: descomentar para habilitar cache de template
         */
        //'cache_template' => 'On',
        /**
         * cache_driver: driver para la cache (file, sqlite, memsqlite)
         */
        'cache_driver' => 'file',
        /**
         * metadata_lifetime: tiempo de vida de la metadata en cache
         */
        'metadata_lifetime' => '+1 year',
        /**
         * namespace_auth: espacio de nombres por defecto para Auth
         */
        'namespace_auth' => 'default',
        /**
         * routes: descomentar para activar routes en routes.php
         */
        'routes' => '1',
    ],
    'custom' => [
        /**
         * app_mayus: 
         */
        'app_mayus' => 'On',
        /**
         * app_update: 
         */
        'app_update' => 'Off',
        /**
         * app_update_time: 
         */
        'app_update_time' => '30 min',
        /**
         * app_version: 
         */
        'app_version' => '2.0',
        /**
         * app_logger: 
         */
        'app_logger' => 'On',
        /**
         * app_local: 
         */
        'app_local' => 'On',
        /**
         * app_office: 
         */
        'app_office' => 'On',
        /**
         * app_ajax: 
         */
        'app_ajax' => 'On',
        /**
         * datagrid: 
         */
        'datagrid' => '30',
        /**
         * app_nombre: 
         */
        'app_nombre' => 'Asdin',
        /**
         * login_exclusion: 
         */
        'login_exclusion' => 'admin,root',
        /**
         * type_reports: 
         */
        'type_reports' => 'html.printer',
        /**
         * minimo_clave: 
         */
        'minimo_clave' => '6',
        /**
         * guardar_auditorias: 
         */
        'guardar_auditorias' => 'On',
        /**
         * intentos_acceso: 
         */
        'intentos_acceso' => '3',
        /**
         * establecimientos: 
         */
        'establecimientos' => '2',
    ],
    'correo' => [
        /**
         * dominio: 
         */
        'dominio' => 'http://localhost:8977/catalogo/',
        /**
         * username: 
         */
        'username' => 'info@asdinec.com',
        /**
         * password: 
         */
        'password' => 'xxxxxxxx',
        /**
         * from_mail: 
         */
        'from_mail' => 'info@asdinec.com',
        /**
         * from_name: 
         */
        'from_name' => 'Mercadeo Asdin',
        /**
         * host: 
         */
        'host' => 'mail.asdinec.com',
        /**
         * port: 
         */
        'port' => '22',
    ],
];
