<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Gocardless extends ClientsController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('invoices_model');
    }
    public function index()
    {
        if (is_client_logged_in()) {
            $client = get_client_user_id();
            $mandate = $this->get_mandate($client, true);
            $this->data['mandate'] = $mandate;
            $this->view('setting', $this->data);
            $this->layout();
        } else {
            redirect(site_url('/'));
        }
    }
    public function create_mandate()
    {
        if (is_client_logged_in() && empty($this->get_mandate(get_client_user_id()))) {
            $client = get_client_user_id();
            $link = $this->gocardless_gateway->get_redirect_link($client);
            redirect($link);
        } else {
            redirect(site_url('/'));
        }
    }
    public function store_mandate($billing_id)
    {
        if (is_client_logged_in() && empty($this->get_mandate(get_client_user_id()))) {
            $client = get_client_user_id();
            $response = $this->gocardless_gateway->make_api_call('/billing_requests/' . $billing_id, 'GET');
            $response = json_decode($response);
            $mandate = $response->billing_requests->links->mandate_request_mandate ?? '';
            $gocardless_customer = $response->billing_requests->links->customer ?? '';
            if (!empty($client) && !empty($mandate) && !empty($gocardless_customer)) {
                $this->db->insert(db_prefix() . 'gocardless_mandate', [
                    'companyid'             => $client,
                    'mandateid'             => $mandate,
                    'gocardless_companyid'  => $gocardless_customer,
                    'status'                => "pending_submission"
                ]);
                redirect(site_url('gocardless'));
            } else {
                echo "Client=" . $client;
                echo "mandate=" . $mandate;
                echo "gocardless_customer=" . $gocardless_customer;
                exit;
            }
        } else {
            redirect(site_url('/'));
        }
    }
    public function get_mandate($company_id, $withCanceled = false)
    {
        return $this->gocardless_gateway->get_mandate($company_id, $withCanceled);
    }
    public function callback()
    {
    }
    public function onboarding()
    {
    }
    public function webhook()
    {
        http_response_code(200);
        $response = file_get_contents('php://input');
        file_put_contents(__DIR__ . "/../response/hook_" . time() . ".json", $response);
        $response = json_decode($response);
        try {
            $hook = $this->gocardless_gateway->make_api_call("/webhooks/" . $response->meta->webhook_id, "GET");
            $hook = json_decode($hook);
            if ($hook->webhooks->id == $response->meta->webhook_id) {
                switch ($response->events[0]->resource_type) {
                    case 'mandates':
                        $this->processMandateHook($response);
                        break;
                    case 'payments':
                        $this->processPaymentHook($response);
                        break;
                    default:
                        # code...
                        break;
                }
            }
        } catch (\Throwable $th) {
            file_put_contents(__DIR__ . "/../response/hook_error_" . time() . ".json", $th->getMessage());
        }
    }

    protected function processMandateHook($response)
    {
        $mandateid = $response->events[0]->links->mandate;
        try {
            $hook = $this->gocardless_gateway->make_api_call("/mandates/" . $mandateid, "GET");
            $hook = json_decode($hook);
            $companyid = $hook->mandates->metadata->customer_id;
            $data = [
                'mandateid'             => $mandateid,
                'gocardless_companyid'  => $hook->mandates->links->customer,
                'status'                => $hook->mandates->status
            ];

            $mandates = $this->get_mandate($companyid, true);

            if (!empty($mandates)) {
                $this->db->update(db_prefix() . 'gocardless_mandate', $data, ['companyid' => $companyid]);
            } else {
                $data['companyid'] = $hook->mandates->metadata->customer_id;
                $this->db->insert(db_prefix() . 'gocardless_mandate', $data);
            }
        } catch (\Throwable $th) {
            file_put_contents(__DIR__ . "/../response/mandate_hook_error_" . time() . ".json", $th->getMessage());
        }
    }


    protected function processPaymentHook($response)
    {
        $paymentid = $response->events[0]->links->payment;
        try {
            $hook = $this->gocardless_gateway->make_api_call("/payments/" . $paymentid, "GET");
            $hook = json_decode($hook);
            $token = $hook->payments->metadata->reference;

            $invoice = $this->db->where('token', $token)->get(db_prefix() . "invoices")->row();
            if (!empty($invoice)) {
                $status = $hook->payments->status;

                if ($status == "confirmed" || $status == "paid_out") {
                    $status = 2;
                } elseif ($status == "failed" || $status == "cancelled") {
                    $status = 5;
                } else {
                    $status = 1;
                }

                $this->db->update(db_prefix() . "invoices", ['status' => $status], ['id' => $invoice->id]);

                $paymentRecord = $this->db->where('invoiceid', $invoice->id)->where('transactionid', $paymentid)->get(db_prefix() . "invoicepaymentrecords")->row();

                if (!empty($paymentRecord)) {
                    $this->db->where('id', $paymentRecord->id)->update(db_prefix() . "invoicepaymentrecords", [
                        'daterecorded' => date('Y-m-d H:i:s'),
                        'note'         => $hook->payments->status,
                    ]);
                } else {
                    $this->db->insert(db_prefix() . "invoicepaymentrecords", [
                        "invoiceid"     => $invoice->id,
                        "amount"        => $invoice->total,
                        "paymentmode"   => "gocardless_by_uk",
                        "paymentmethod" => "Instant Bank Payment",
                        "date"          => $hook->payments->charge_date,
                        "daterecorded"  => date('Y-m-d H:i:s'),
                        "note"          => $hook->payments->status,
                        "transactionid" => $hook->payments->id
                    ]);
                }
            }
        } catch (\Throwable $th) {
            file_put_contents(__DIR__ . "/../response/mandate_hook_error_" . time() . ".json", $th->getMessage());
        }
    }



    public function make_instant_payment($invoiceid)
    {
        $data['invoiceid'] = $invoiceid;
        $invoice = $this->invoices_model->get($invoiceid);
        $data['invoice']   = $invoice;
        $this->load->model('payment_modes_model');
        $gateway = $this->payment_modes_model->get('gocardless_by_uk');
        $data['gateway_fee'] = $gateway->instance->getFee($data['amount']);
        $this->load->model('payment_attempts_model');
        $data['payment_attempt'] = $this->payment_attempts_model->add([
            'reference' => app_generate_hash(),
            'amount' => $data['amount'],
            'fee' => $data['gateway_fee'],
            'invoice_id' => $data['invoiceid'],
            'payment_gateway' => $gateway->instance->getId()
        ]);
        $data['amount']     += $data['gateway_fee'];
        //$this->gocardless_gateway->process_payment($data, false);
        exit;
    }

    public function success($billing_id)
    {
        if (is_client_logged_in()) {
            $client = get_client_user_id();
            $mandate = $this->get_mandate(get_client_user_id(), true);
            $response = $this->gocardless_gateway->make_api_call('/billing_requests/' . $billing_id, 'GET');
            $response = json_decode($response);
            $mandateId = $response->billing_requests->links->mandate_request_mandate ?? '';
            $gocardless_customer = $response->billing_requests->links->customer ?? '';
            if ((empty($mandate) || $mandate->status == "canceled") && !empty($client) && !empty($mandateId) && !empty($gocardless_customer)) {
                $data = [
                    'companyid'             => $client,
                    'mandateid'             => $mandateId,
                    'gocardless_companyid'  => $gocardless_customer,
                    'status'                => "pending_submission"
                ];
                if (empty($mandate)) {
                    $this->db->insert(db_prefix() . 'gocardless_mandate', $data);
                } else {
                    $this->db->update(db_prefix() . 'gocardless_mandate', $data, ['id' => $mandate->id]);
                }
            }
            $invoiceToken = $response->billing_requests->payment_request->metadata->reference;
            $invoice = $this->db->where('token', $invoiceToken)->get(db_prefix() . 'invoices')->row();
            $this->gocardless_gateway->record_instant_payment($invoice, $response);
            $invoiceUrl    = site_url('invoice/' . $invoice->id . '/' . $invoice->hash);
            redirect($invoiceUrl);
        } else {
            redirect(site_url('/'));
        }
    }

}
