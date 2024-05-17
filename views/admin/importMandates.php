<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">Import Mandates</h4>
                        <hr class="hr-panel-heading" />
                        <?php if (!empty($mandates)) : ?>
                            <?php foreach ($mandates as $mandate) : ?>
                                <div class="row">
                                    <!-- Left section for bank details -->
                                    <div class="col-md-6">
                                        <h5>Mandate Details</h5>
                                        <p>ID: <?= $mandate->id ?></p>
                                        <p>Customer ID: <?= $mandate->links->customer ?></p>
                                        <p>Customer Bank Account: <?= $mandate->links->customer_bank_account ?></p>
                                        <p>Reference: <?= $mandate->reference ?></p>
                                        <p>Status: <?= strtoupper($mandate->status) ?></p>
                                        <p>Created on: <?= date('Y-m-d H:i', strtotime($mandate->created_at)) ?></p>
                                    </div>

                                    <!-- Right section for customer dropdown and save button -->
                                    <div class="col-md-6">
                                        <?= form_open(admin_url('gocardless/mandate_details/save_customer_mandate'), ['method' => 'post', 'class' => 'row']); ?>
                                        <input type="hidden" name="mandateid" value="<?= $mandate->id ?>">
                                        <input type="hidden" name="gocardless_companyid" value="<?= $mandate->links->customer ?>">
                                        <div class="col-md-8">
                                            <div class="form-group">
                                                <select id="customer" name="companyid" class="form-control">
                                                    <option value="">Select</option>
                                                    <?php foreach ($customers as $customer) { ?>
                                                        <option value="<?= $customer['userid']; ?>">
                                                            <?= $customer['company']; ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </div>
                                        <?= form_close(); ?>
                                        <small class="text-danger">Only one mandate per customer is allowed, any more than that will be updated to the last assigned mandate.</small>
                                    </div>
                                </div>
                                <hr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>