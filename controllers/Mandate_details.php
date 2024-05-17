<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Mandate_details extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('clients_model');
    }
    public function index()
    {
        if (
            staff_cant('view', 'invoices')
            && staff_cant('view_own', 'invoices')
            && get_option('allow_staff_view_invoices_assigned') == '0'
        ) {
            access_denied('invoices');
        }

        close_setup_menu();

        $data['title'] = "Gocardless Direct Debit Mandate";
        $data['table'] = $this->db->select('gocardless_mandate.*,c.company as company_name')->join(db_prefix() . 'clients c', 'c.userid=gocardless_mandate.companyid')->get(db_prefix() . 'gocardless_mandate')->result();
        $this->load->view('admin/gocardless_details', $data);
    }
    public function import_view()
    {
        $mandates = [];
        try {
            $jsonRes = $this->get_active_mandates_by_customer();
            $res = json_decode($jsonRes);
            $mandates = $res->mandates;
        } catch (\Throwable $th) {
            // throw $th;
        }

        $data['customers'] = $this->clients_model->get();
        $data['mandates'] = $mandates;
        $this->load->view('admin/importMandates', $data);
    }
    public function get_active_mandates_by_customer($customerId = null)
    {
        $url = $customerId ? "/mandates?status=active&customer=$customerId" : "/mandates?status=active";
        $response = $this->gocardless_gateway->make_api_call($url, 'GET');
        return $response;
    }
    public function save_customer_mandate()
    {
        if (isset($_POST['companyid']) && $_POST['companyid'] !== '') {
            $data = [
                'companyid'             => $_POST['companyid'],
                'mandateid'             => $_POST['mandateid'],
                'gocardless_companyid'  => $_POST['gocardless_companyid'],
                'status'                => "active"
            ];
            $mandate = $this->get_mandate($_POST['companyid'], true);
            if (empty($mandate)) {
                $this->db->insert(db_prefix() . 'gocardless_mandate', $data);
            } else {
                $this->db->update(db_prefix() . 'gocardless_mandate', $data, ['companyid' => $_POST['companyid']]);
            }
            set_alert('success', 'Mandate details saved successfully.');
        } else {
            set_alert('danger', "Please select a customer.");
        }
        redirect($_SERVER['HTTP_REFERER']);
    }
    public function get_mandate($company_id, $withCanceled = false)
    {
        return $this->gocardless_gateway->get_mandate($company_id, $withCanceled);
    }
    public function checkIfCustomerSelectedMandate($cusId, $mandate)
    {
        $mandate = $this->get_mandate($cusId, true);
        if (!empty($mandate)) {
            var_dump($mandate);
            die;
        } else {
            return false;
        }
    }
}
