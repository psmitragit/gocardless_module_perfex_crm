<?php
defined('BASEPATH') or exit('No direct script access allowed');
?>
<div class="tw-flex tw-items-center tw-justify-between tw-mb-3">
    <h4 class="tw-my-0 tw-font-semibold tw-text-lg tw-text-neutral-700 section-heading section-heading-tickets">
        <?= !empty($mandate) ? _l('View Direct Debit') : _l('Set up a Direct Debit'); ?>
    </h4>
</div>
<div class="panel_s">
    <div class="panel-body p-5">
        <?php if (!empty($mandate) && $mandate->status != "canceled") : ?>
            <?php if ($mandate->status == "pending_submission") : ?>
                <p class="text-center">Thank you for submitting the payment mandate. Please wait until verification is complete.</p>
            <?php else : ?>
                <p class="text-center">Your direct debit permission has been granted. Now all payments are hassle-free.</p>
            <?php endif; ?>
            <p class="text-center">Your mandate ID is <?= $mandate->mandateid; ?></p>
        <?php else : ?>
            <?php if (!empty($mandate) && $mandate->status == "canceled") : ?>
                <p class="text-center">Your request for direct debit has been rejected. Please resubmit.</p>
            <?php else : ?>
                <p class="text-center">You haven't given permission for Direct Debit. Please click the button below to grant permission or add your mandate ID if it already exists.</p>
            <?php endif; ?>
            <div class="m-5 text-center">
                <?= form_open(site_url('gocardless/create_mandate'), ['method' => 'post']); ?>
                <button class="btn btn-primary" type="submit">Set up a Direct Debit</button>
                <?= form_close(); ?>   
            </div>
        <?php endif; ?>
    </div>
</div>