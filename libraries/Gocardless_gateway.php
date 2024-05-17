<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Gocardless_gateway extends App_gateway
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('gocardless_by_uk');
        $this->setName('Gocardless Direct Debit Payment');
        $this->setSettings(array(
            array(
                'name' => 'access_url',
                'label' => 'Api Endpoint',
                'type' => 'input',
            ),
            array(
                'name' => 'access_version',
                'label' => 'Api Version',
                'type' => 'input',
            ),
            array(
                'name' => 'access_token',
                'encrypted' => true,
                'label' => 'ACCESS TOKEN',
                'type' => 'input',
            ),
            array(
                'name' => 'currencies',
                'label' => 'settings_paymentmethod_currencies',
                'default_value' => 'GBP'
            ),
        ));
    }
    /**
     * Each time a customer click PAY NOW button on the invoice HTML area, the script will process the payment via this function.
     * You can show forms here, redirect to gateway website, redirect to Codeigniter controller etc..
     * @param  array $data - Contains the total amount to pay and the invoice information
     * @return mixed
     */
    public function process_recuring_payment($data, $rediret = true)
    {
        if (!empty($data)) {
            $invoiceUrl    = site_url('invoice/' . $data['invoice']->id . '/' . $data['invoice']->hash);
            try {
                $company_id = $data['invoice']->clientid;
                $mandate = $this->get_mandate($company_id);
                $reference = substr($data['payment_attempt']->reference, 0, 10);
                $CI = &get_instance();
                $CI->db->where('id', $data['invoice']->id)->update(db_prefix() . 'invoices', ['token' => $reference]);
                $payload = [
                    "payments" => [
                        "amount"            => $data['amount'] * 100,
                        "currency"          => $data['invoice']->currency_name,
                        "charge_date"       => date('Y-m-d'),
                        "reference"         => $reference,
                        "metadata"          => [
                            "order_dispatch_date"   => date('Y-m-d'),
                            "invoice_number"        => format_invoice_number($data['invoice']->id)
                        ],
                        "links"             => [
                            "mandate"               => $mandate->mandateid
                        ]
                    ]
                ];


                $response = $this->make_api_call('/payments', 'POST', json_encode($payload));
                $response = json_decode($response);
             
                if (!empty($response->payments)) {
                    $data = [
                        'amount'        => $data['amount'] ?? "",
                        'invoiceid'     => $data['invoice']->id ?? "",
                        'paymentmode'   => $data['paymentmode'] ?? "",
                        'note'          => $response->payments->status ?? "",
                        'transactionid' => $response->payments->id ?? "",
                    ];
                    if ($response->payments->status == "confirmed" || $response->payments->status == "paid_out") {
                        $this->addPayment($data);
                        set_alert('success', _l('online_payment_recorded_success'));
                    } elseif ($response->payments->status == "pending_submission") {
                        $this->addPayment($data);
                        set_alert('info', _l('payment_received_awaiting_confirmation'));
                    } else {
                        set_alert('warning', _l('invoice_payment_record_failed'));
                    }
                } else {
                    set_alert('danger', "Something wents wrong!");
                }

                if ($rediret) {
                    redirect($invoiceUrl);
                } else {
                    return 1;
                }
            } catch (Exception $e) {
                set_alert('danger', "Something wents wrong!");
                file_put_contents(__DIR__ . "/../response/dd_hook_error_" . time() . ".json", $e->getMessage());
                if ($rediret) {
                    redirect($invoiceUrl);
                } else {
                    return 0;
                }
            }
        }
        die;
    }
    public function get_redirect_link($company_id)
    {
        /**
         * Create Billing
         */
        $payload = [
            "billing_requests" => [
                "mandate_request" => [
                    "scheme" => "bacs",
                    "metadata"        => [
                        "customer_id" => $company_id
                    ]
                ]
            ]
        ];
        $response = $this->make_api_call('/billing_requests', 'POST', json_encode($payload));
        $response = json_decode($response);
        $billing_id = $response->billing_requests->id;
        $payload = [
            'billing_request_flows' => [
                'redirect_uri'  => site_url('gocardless/store_mandate/' . $billing_id),
                'exit_uri'      => site_url('gocardless'),
                'links'         => [
                    'customer'          => $company_id,
                    "billing_request"   => $billing_id
                ]
            ]
        ];
        $response = $this->make_api_call('/billing_request_flows', "POST", json_encode($payload));
        $response = json_decode($response);
        return $response->billing_request_flows->authorisation_url;
    }
    public function make_api_call($endpoint, $method, $payload = null)
    {
        $endpoint = $this->getSetting('access_url') . $endpoint;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $endpoint);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $this->decryptSetting('access_token'),
            'Content-Type: application/json',
            'GoCardless-Version: ' . $this->getSetting('access_version'),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        if (curl_error($curl)) {
            echo curl_error($curl);
        } else {
            return $response;
        }
        exit;
    }
    public function get_mandate($company_id, $withCanceled = false)
    {
        $CI = &get_instance();
        $CI->db->where('companyid', $company_id);
        !$withCanceled ? $CI->db->where('status<>', 'canceled') : "";
        return $CI->db->get(db_prefix() . 'gocardless_mandate')->row();
    }
    public function has_configured()
    {
        if (!empty($this->decryptSetting('access_token') && !empty($this->decryptSetting('access_url')) && !empty($this->decryptSetting('access_version')))) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * Each time a customer click PAY NOW button on the invoice HTML area, the script will process the payment via this function.
     * You can show forms here, redirect to gateway website, redirect to Codeigniter controller etc..
     * @param  array $data - Contains the total amount to pay and the invoice information
     * @return mixed
     */
    public function process_payment($data, $rediret = true, $instant = false)
    {
        $supportedCurrency = ["GBP", "EUR", "AUD"];
        if (!empty($data)) {
            $company_id = $data['invoice']->clientid;
            $mandate = $this->get_mandate($company_id);

            $invoiceUrl    = site_url('invoice/' . $data['invoice']->id . '/' . $data['invoice']->hash);
            if ($instant && !in_array($data['invoice']->currency_name, $supportedCurrency)) {
                set_alert('danger', "Gocardless not support payment with {$data['invoice']->currency_name}. Please pay with another payment gateway");
                redirect($invoiceUrl);
            }
           
            if (!$instant && !empty($mandate)) {
                $this->process_recuring_payment($data);
                die();
            }

            $reference = substr($data['payment_attempt']->reference, 0, 10);
            $CI = &get_instance();
            $CI->db->where('id', $data['invoice']->id)->update(db_prefix() . 'invoices', ['token' => $reference]);
            $invoiceNumber = format_invoice_number($data['invoice']->id);
            $payload = [
                "billing_requests" => [
                    "payment_request" => [
                        "description" => "Payment for invoice " . $invoiceNumber,
                        "amount" => $data['amount'] * 100,
                        "currency" => $data['invoice']->currency_name,
                        "charge_date" => date('Y-m-d'),
                        "reference" => $reference,
                        "metadata" => [
                            "invoice_number"    => $invoiceNumber,
                            "reference"         => $reference,
                            "gateway"           => $data['paymentmode']
                        ]
                    ],
                ]
            ];

            if (!empty($mandate)) {
                $payload['billing_requests']['links'] = [
                    "customer" => $mandate->gocardless_companyid
                ];
            }

            if (!$instant && empty($mandate)) {
                $payload['billing_requests']['mandate_request'] = [
                    "scheme" => "bacs",
                    "metadata"        => [
                        "customer_id" => get_client_user_id()
                    ]
                ];
            }

            $response = $this->make_api_call('/billing_requests', 'POST', json_encode($payload));
            $response = json_decode($response);
            $billing_id = $response->billing_requests->id ?? false;
            if (!empty($billing_id)) {
                $payload = [
                    "billing_request_flows" => [
                        "redirect_uri" => site_url('gocardless/success/' . $billing_id),
                        "exit_uri" => $invoiceUrl,
                        "customer_details_captured" => true,
                        "links" => [
                            "billing_request" => $billing_id
                        ]
                    ]
                ];
                $response = $this->make_api_call('/billing_request_flows', "POST", json_encode($payload));
                $response = json_decode($response);
                $payUrl = $response->billing_request_flows->authorisation_url ?? false;
                if (!empty($payUrl)) {
                    redirect($payUrl);
                } else {
                    set_alert('danger', "Something went wrong!");
                }
            } else {
                set_alert('danger', "Something went wrong!");
            }
            if ($rediret) {
                redirect($invoiceUrl);
            } else {
                return 1;
            }
        }
        die;
    }
    public function record_instant_payment($invoice, $response)
    {
        $payment = [
            'amount'        => $invoice->total ?? "",
            'invoiceid'     => $invoice->id ?? "",
            'paymentmode'   => $response->billing_requests->payment_request->metadata->gateway ?? "",
            'note'          => $response->billing_requests->status ?? "",
            'transactionid' => $response->billing_requests->links->payment_request_payment ?? "",
        ];
        if ($response->billing_requests->status == "fulfilled" || $response->billing_requests->status == "confirmed" || $response->billing_requests->status == "paid_out") {
            $this->addPayment($payment);
            $this->setGateway($payment['transactionid'], $payment['invoiceid'], $payment['paymentmode']);
            set_alert('success', _l('online_payment_recorded_success'));
        } elseif ($response->payments->status == "pending_submission") {
            $this->addPayment($payment);
            $this->setGateway($payment['transactionid'], $payment['invoiceid'], $payment['paymentmode']);
            set_alert('info', _l('payment_received_awaiting_confirmation'));
        } else {
            set_alert('warning', _l('invoice_payment_record_failed'));
        }
    }

    public function setGateway($transactionid, $invoiceId, $gateway)
    {
        $CI = &get_instance();
        $CI->db->where('transactionid', $transactionid)->where('invoiceid', $invoiceId)->update(db_prefix() . 'invoicepaymentrecords', ['paymentmode' => $gateway]);
    }
}
