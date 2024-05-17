<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style>
    #dataTable {
        width: 100% !important;
    }
</style>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="_buttons">
                    <?php if (staff_can('create',  'subscription')) { ?>
                        <a href="<?php echo admin_url('gocardless/subscriptions/plan/create'); ?>" class="btn btn-primary pull-left display-block">
                            <i class="fa-regular fa-plus tw-mr-1"></i>
                            <?php echo _l('New subscription plan'); ?>
                        </a>
                    <?php } ?>

                </div>

                <div class="clearfix"></div>

                <div class="panel_s tw-mt-2 sm:tw-mt-4">
                    <div class="panel-body">
                        <div class="panel-table-full">
                            <table id="dataTable" class="w-100">
                                <thead>
                                    <tr>
                                        <td>#</td>
                                        <td>Name</td>
                                        <td>Amount</td>
                                        <td>Interval</td>
                                        <td>Day of Deduct</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($table as $l) : ?>
                                        <tr>
                                            <th>#</th>
                                            <th><?= $l->name ?? ""; ?></th>
                                            <th><?= $l->amount ?? ""; ?></th>
                                            <th><?= ucfirst($l->interval); ?></th>
                                            <th><?= $l->name ?></th>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        $('#dataTable').dataTable()
    });
</script>
</body>

</html>