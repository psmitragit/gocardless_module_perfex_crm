<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style>
    #dataTable {
        width: 100% !important;
    }

    .rr .dt-table-loading.table,
    .rr .table-loading .dataTables_filter,
    .rr .table-loading .dataTables_length,
    .rr .table-loading .dt-buttons,
    .rr .table-loading table tbody tr,
    .rr .table-loading table thead th {
        opacity: 1 !important;
    }

    .rr .table-loading {
        background: unset;
    }

    .rr table.dataTable thead>tr>td.sorting_asc,
    .rr table.dataTable thead>tr>td.sorting_desc,
    .rr table.dataTable thead>tr>th.sorting_asc,
    .rr table.dataTable thead>tr>th.sorting_desc {
        background: transparent;
    }

    .rr .table-loading table thead tr {
        height: unset;
        min-height: unset;
    }
</style>
<div id="wrapper" class="rr">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-mt-0 tw-font-semibold tw-text-lg">Direct Debit Details</h4>
                <div class="clearfix"></div>

                <div class="panel_s tw-mt-2 sm:tw-mt-4">
                    <div class="panel-body panel-table-full">
                        <div class="">
                            <table id="dataTable" class="w-100 table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Company Name</th>
                                        <th>Gocardless Account Id</th>
                                        <th>Mandate Id</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($table as $k => $l) : ?>
                                        <tr>
                                            <td><?= $k + 1; ?></td>
                                            <td><?= $l->company_name ?? ""; ?></td>
                                            <td><?= $l->gocardless_companyid ?? ""; ?></td>
                                            <td><?= $l->mandateid ?? ""; ?></td>
                                            <td><?= ucwords(str_replace(['_', '-'], ' ', ($l->status ?? "pending_submission"))) ?></td>
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