<?php
defined('BASEPATH') or exit('No direct script access allowed');
require('Gocardless_gateway.php');
class Gocardless_Instant_gateway extends App_gateway
{
    private $gocardless;
    public function __construct()
    {
        parent::__construct();
        $this->setId('gocardless_instant_by_uk');
        $this->setName('Gocardless Instant Bank Payment');
        $this->setSettings(array(
            array(
                'name' => 'currencies',
                'label' => 'settings_paymentmethod_currencies',
                'default_value' => 'GBP'
            ),
        ));

        $this->gocardless = new Gocardless_gateway();
    }

    public function process_payment($data, $rediret = true)
    {
        $this->gocardless->process_payment($data, true, true);
    }
}
