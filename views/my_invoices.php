<?php defined('BASEPATH') or exit('No direct script access allowed');
$company_id = get_client_user_id();
$this->db->where('companyid', $company_id);
$this->db->where('status<>', 'canceled');
$mandate = $this->db->get(db_prefix() . 'gocardless_mandate')->row();

?>

<div class="tw-mt-0 tw-font-semibold row tw-text-lg tw-text-neutral-700 section-heading section-heading-invoices">
    <h4 class="col-md-6">
        <?php echo _l('clients_my_invoices'); ?>
    </h4>
    <div class="col-md-6 text-right mb-2">
        <?php if (has_contact_permission('invoices')) { ?>
            <span class="tw-text-sm">
                <a href="<?php echo site_url('clients/statement'); ?>" class="view-account-statement btn btn-info">
                    <?php echo _l('view_account_statement'); ?>
                </a>
            </span>
        <?php } ?>
        <?php if (has_contact_permission('invoices')) { ?>
            &ensp;<span class="tw-text-sm">
                <a href="<?php echo site_url('gocardless'); ?>" class="view-account-statement btn btn-primary">
                    <?= !empty($mandate) ? _l('View Direct Debit') : _l('Set up a Direct Debit'); ?>
                </a>
            </span>
        <?php } ?>
    </div>
</div>
<div class="panel_s">
    <div class="panel-body">
        <?php get_template_part('invoices_stats'); ?>
        <hr />
        <table class="table dt-table table-invoices" data-order-col="1" data-order-type="desc">
            <thead>
                <tr>
                    <th class="th-invoice-number"><?php echo _l('clients_invoice_dt_number'); ?></th>
                    <th class="th-invoice-date"><?php echo _l('clients_invoice_dt_date'); ?></th>
                    <th class="th-invoice-duedate"><?php echo _l('clients_invoice_dt_duedate'); ?></th>
                    <th class="th-invoice-amount"><?php echo _l('clients_invoice_dt_amount'); ?></th>
                    <th class="th-invoice-status"><?php echo _l('clients_invoice_dt_status'); ?></th>
                    <?php
                    $custom_fields = get_custom_fields('invoice', ['show_on_client_portal' => 1]);
                    foreach ($custom_fields as $field) { ?>
                        <th><?php echo e($field['name']); ?></th>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $invoice) { ?>
                    <tr>
                        <td data-order="<?php echo e($invoice['number']); ?>"><a href="<?php echo site_url('invoice/' . $invoice['id'] . '/' . $invoice['hash']); ?>" class="invoice-number"><?php echo e(format_invoice_number($invoice['id'])); ?></a></td>
                        <td data-order="<?php echo e($invoice['date']); ?>"><?php echo e(_d($invoice['date'])); ?></td>
                        <td data-order="<?php echo e($invoice['duedate']); ?>"><?php echo e(_d($invoice['duedate'])); ?></td>
                        <td data-order="<?php echo e($invoice['total']); ?>">
                            <?php echo e(app_format_money($invoice['total'], $invoice['currency_name'])); ?></td>
                        <td><?php echo format_invoice_status($invoice['status'], 'inline-block', true); ?></td>
                        <?php foreach ($custom_fields as $field) { ?>
                            <td><?php echo get_custom_field_value($invoice['id'], $field['id'], 'invoice'); ?></td>
                        <?php } ?>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>