<?php



defined('BASEPATH') or exit('No direct script access allowed');

require('libraries/Gocardless_Instant_gateway.php');

/*

Module Name: GoCardLess Payment Gateway

Description: Use GoCardLess Payment seamlessly

Author: @uttam-official

Author URI: https://github.com/uttam-official

Version: 0.0.1

Requires at least: 2.3.*

*/

define('GOCARDLESS_MODULE', 'gocardless');



register_activation_hook(GOCARDLESS_MODULE, 'gocardless_activation');

register_deactivation_hook(GOCARDLESS_MODULE, 'gocardless_deactivation');

register_payment_gateway('gocardless_instant_gateway', GOCARDLESS_MODULE);

register_payment_gateway('gocardless_gateway', GOCARDLESS_MODULE);





function gocardless_activation()

{

    $CI = &get_instance();

    if (!$CI->db->table_exists(db_prefix() . 'gocardless_mandate')) {

        $CI->db->query('CREATE TABLE `' . db_prefix() . 'gocardless_mandate` (

        `companyid` int(11) NOT NULL UNIQUE,

        `gocardless_companyid` VARCHAR(50) NOT NULL,

        `mandateid` VARCHAR(50) NOT NULL,

        `status` VARCHAR(50)

        ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    }





    $filePath = APPPATH . '/views/themes/perfex/views/my_invoices.php';



    if (!file_exists($filePath)) {

        copy(__DIR__ . "/views/my_invoices.php", $filePath);
    }
}



function gocardless_deactivation()

{

    $CI = &get_instance();

    if ($CI->db->table_exists(db_prefix() . 'gocardless_mandate')) {

        $CI->db->query('DROP TABLE `' . db_prefix() . 'gocardless_mandate`');
    }



    $filePath = APPPATH . '/views/themes/perfex/views/my_invoices.php';



    if (file_exists($filePath)) {

        unlink($filePath);
    }
}





hooks()->add_action('admin_init', 'my_custom_menu_admin_items');



function my_custom_menu_admin_items()

{
    $CI = &get_instance();

    $CI->app_menu->add_sidebar_children_item('sales', [
        'slug'     => 'custom-menu-unique-id',
        'name'     => 'Direct Debit Details', 
        'href'     => admin_url('gocardless/mandate_details'), 
        'position' => 1, 
        'icon'     => '', 
    ]);

    $CI->app_menu->add_sidebar_menu_item('import-mandate', [
        'slug'     => 'custom-menu-unique-id',
        'name'     => 'Import Mandates', 
        'href'     => admin_url('gocardless/mandate_details/import_view'), 
        'position' => 1, 
        'icon'     => 'fa fa-file-import', 
    ]);
}







hooks()->add_action('after_invoice_added', 'after_invoice_added_hook', 10);

// hooks()->add_action('admin_init', 'after_invoice_added_hook', 10);



function after_invoice_added_hook($invoiceId)

{

    $CI = &get_instance();

    $data['invoiceid'] = $invoiceId;

    $invoice = $CI->invoices_model->get($invoiceId);

    if (!empty($invoice->is_recurring_from)) {

        $method = unserialize($invoice->allowed_payment_modes);

        $mandate = $CI->db->where('companyid', $invoice->clientid)->get(db_prefix() . 'gocardless_mandate')->row();

        if (in_array('gocardless_by_uk', $method) && !empty($mandate)) {

            $CI->load->library('gocardless_gateway');



            $CI->load->model('invoices_model');



            $data['invoice']   = $invoice;

            $data['amount'] = $invoice->total;

            $CI->load->model('payment_modes_model');

            $gateway = $CI->payment_modes_model->get('gocardless_by_uk');

            $data['gateway_fee'] = $gateway->instance->getFee($data['amount']);



            $CI->load->model('payment_attempts_model');



            $data['payment_attempt'] = $CI->payment_attempts_model->add([

                'reference' => app_generate_hash(),

                'amount' => $data['amount'],

                'fee' => $data['gateway_fee'],

                'invoice_id' => $data['invoiceid'],

                'payment_gateway' => $gateway->instance->getId()

            ]);



            $data['paymentmode'] = $gateway->instance->getId();



            $data['amount']     += $data['gateway_fee'];



            $CI->gocardless_gateway->process_recuring_payment($data, false);
        }
    }



    //error_log("Hook executed - Invoice ID: " . $invoiceId);

}
