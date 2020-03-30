<?php

/**
 * Controller por defecto si no se usa el routes
 *
 */
class MailController extends AppController {

    public $page_title = 'Asdinec';

    public function suscripcion() {
        View::template(NULL);
    }

    public function productos() {
        View::template(NULL);
    }

}
