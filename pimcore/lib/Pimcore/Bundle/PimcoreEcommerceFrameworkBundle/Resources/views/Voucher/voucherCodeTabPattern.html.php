<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


/**
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $this
 */
?>

<head>
    <link href="/bundles/pimcoreecommerceframework/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="/bundles/pimcoreecommerceframework/vendor/bootstrap/css/bootstrap-theme.min.css" rel="stylesheet">
    <link href="/bundles/pimcoreecommerceframework/vendor/pickadate.classic.css" rel="stylesheet">
    <link href="/bundles/pimcoreecommerceframework/vendor/pickadate.classic.date.css" rel="stylesheet">
    <link href="/bundles/pimcoreecommerceframework/css/voucherservice/style.css" rel="stylesheet">
</head>
<body>

<?php

$colors=[
'used'=>"#F7355B",
'free'=>"#47BFBD",
'reserved'=>"#FDC45B",
];

$seriesId = $this->getParam('id');
$urlParams = $this->getRequest()->query->all();

if ($this->paginator) {
    $this->paginator->setPageRange(10);

    $pagesCount = $this->paginator->getItemCountPerPage();

    $paginationTemplate = $this->render(
        "PimcoreEcommerceFrameworkBundle:Voucher/parts:paginator.html.php",
        get_object_vars($this->paginator->getPages("Sliding"))
    );
}

?>

<div class="container-fluid">
    <div id="content">
        <ul id="tabs" class="nav nav-tabs" data-tabs="tabs">
            <li class="active"><a href="#manager" data-toggle="tab"><span class="glyphicon glyphicon-home"></span>&nbsp; <?=$this->translateAdmin('plugin_onlineshop_voucherservice_tab-manager')?></a></li>
            <li><a href="#statistics" id="statistic-tab" data-toggle="tab"><span class="glyphicon glyphicon-stats"></span>&nbsp; <?=$this->translateAdmin('plugin_onlineshop_voucherservice_tab-statistics')?></a></li>
        </ul>

        <div id="my-tab-content" class="tab-content">
            <div class="tab-pane active" id="manager">
                <div class="row">
                    <div class="col col-sm-12">
                        <h2><?=$this->translateAdmin('plugin_onlineshop_voucherservice_tab-manager-headline')?></h2>
                    </div>
                </div>

                <div class="row header">
                    <div class="col col-sm-4">
                        <button type="button" class="btn btn-primary js-modal" data-modal="generate"><?=$this->translateAdmin('plugin_onlineshop_voucherservice_generate-button')?></button>
                        <?php if ($this->voucherType != "single") { ?>
                        <button type="button" class="btn btn-default js-modal" data-modal="cleanUp"><?=$this->translateAdmin('plugin_onlineshop_voucherservice_cleanup-button')?></button>
                        <?php } ?>
                    </div>

                    <!--Info and Error Messages Container-->

                    <div class="col col-sm-4">
                        <?php if ($this->msg['error']) { ?>
                            <div class="alert alert-danger js-fadeout"> <?= $this->msg['error'] ?>  </div>
                        <?php } elseif ($this->msg['success']) { ?>
                            <div class="alert alert-success js-fadeout"> <?= $this->msg['success'] ?>  </div>
                       <?php } elseif ($this->msg['result']) { ?>
                            <div class="alert alert-info js-fadeout"> <?= $this->translateAdmin($this->msg['result']) ?>  </div>
                        <?php } ?>
                    </div>

                    <div class="col col-sm-4 text-right">
                        <div class="btn-group">
                            <?php if ($this->supportsExport): ?>
                                <?php
                                    $exportUrl = $this->path('pimcore_ecommerce_backend_voucher_export-tokens', array_merge($urlParams, ['format' => 'csv']));
                                ?>

                                <a class="btn btn-default" href="<?= $exportUrl ?>" target="_blank">
                                    <span class="glyphicon glyphicon-export"></span>
                                    <?= $this->translateAdmin('plugin_onlineshop_voucherservice_export-button') ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row content-block token-container">

                    <div class="col col-sm-8 token-overview">
                        <div class=" row">
                            <div class="col col-sm-5">
                                <h3 style="float: left;"><i class="glyphicon glyphicon-list"></i>&nbsp;<?=$this->translateAdmin('plugin_onlineshop_voucherservice_token-overview-headline')?></h3>
                            </div>
                            <div class="col col-sm-7 text-right">
                                <?= $paginationTemplate ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col col-sm-6">
                                <?php if ($this->voucherType != "single") { ?>
                                    <h5 class="subtitle"><?= number_format($this->count, 0, ',', ' ') ?> <?=$this->translateAdmin('plugin_onlineshop_voucherservice_result-text')?></h5>
                                <?php } ?>
                            </div>
                            <?php if($this->paginator){?>
                            <div class="col col-sm-6 text-right">
                                <h5 class="subtitle pages"><?=$this->translateAdmin('plugin_onlineshop_voucherservice_tokens-per-page')?>
                                    <a class="pages-count <?php if($pagesCount == 25){echo "active";}?>" href="<?=$this->pimcoreUrl(['tokensPerPage' => 25])?>">25&nbsp;</a>
                                    <a class="pages-count <?php if($pagesCount == 75){echo "active";}?>" href="<?=$this->pimcoreUrl(['tokensPerPage' => 75])?>">75&nbsp;</a>
                                    <a class="pages-count <?php if($pagesCount == 150){echo "active";}?>" href="<?=$this->pimcoreUrl(['tokensPerPage' => 150])?>">150&nbsp;</a>
                                </h5>
                            </div>
                            <?php } ?>
                        </div>

                        <div class="table-container">
                            <table class="table">
                                <thead>
                                <tr class="active">
                                    <th><span class="sort glyphicon glyphicon-chevron-down" data-criteria="token"></span>&nbsp;<?=$this->translateAdmin('plugin_onlineshop_voucherservice_table-token')?></th>
                                    <th class="text-center"><span class="sort glyphicon glyphicon-chevron-down" data-criteria="usages"></span>&nbsp;<?=$this->translateAdmin('plugin_onlineshop_voucherservice_table-usages')?></th>
                                    <th class="text-center"><span class="sort glyphicon glyphicon-chevron-down" data-criteria="length"></span>&nbsp;<?=$this->translateAdmin('plugin_onlineshop_voucherservice_table-length')?></th>
                                    <th class="text-center"><span class="sort glyphicon glyphicon-chevron-down active" data-criteria="timestamp"></span>&nbsp;<?=$this->translateAdmin('plugin_onlineshop_voucherservice_table-date')?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if ($this->paginator) { ?>
                                    <?php foreach ($this->paginator as $code) { ?>
                                        <tr>
                                            <td class="token"><?= $code->getToken() ?></td>
                                            <td class="text-center"><?= (int)$code->getUsages() ?></td>
                                            <td class="text-center"><?= (int)$code->getLength() ?></td>
                                            <td class="text-center"><?= $code->getTimestamp() ?></td>
                                        </tr>
                                    <?php } ?>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php if ($this->voucherType != "single") { ?>
                    <div class="col col-sm-4 filter">
                        <h3><i class="glyphicon glyphicon-search"></i> &nbsp;<?=$this->translateAdmin('plugin_onlineshop_voucherservice_filter-headline')?></h3>

                        <form class="form-horizontal js-filter-form" action="<?= $this->pimcoreUrl([], null, true) ?>">
                            <div class="form-group">
                                <div class=" col col-sm-12">
                                    <label><?=$this->translateAdmin('plugin_onlineshop_voucherservice_filter-token')?></label>
                                    <input type="text" name="token" value="<?= $this->getParam('token') ?>" placeholder="token"
                                           class="form-control"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class=" col col-sm-6">
                                    <label><?=$this->translateAdmin('plugin_onlineshop_voucherservice_filter-from-date')?></label>
                                    <input type="text" name="creation_from" value="<?= $this->getParam('creation_from') ?>"
                                           placeholder="YYYY/MM/DD" class="js-datepicker form-control"/>
                                </div>
                                <div class=" col col-sm-6">
                                    <label><?=$this->translateAdmin('plugin_onlineshop_voucherservice_filter-to-date')?></label>
                                    <input type="text" name="creation_to" value="<?= $this->getParam('creation_to') ?>"
                                           placeholder="YYYY/MM/DD" class="js-datepicker form-control"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class=" col col-sm-6">
                                    <label><?=$this->translateAdmin('plugin_onlineshop_voucherservice_filter-usages')?></label>
                                    <input type="number" name="usages" value="<?= $this->getParam('usages') ?>" min="0"
                                           placeholder="usages" class="form-control"/>
                                </div>
                                <div class=" col col-sm-6">
                                    <label><?=$this->translateAdmin('plugin_onlineshop_voucherservice_filter-length')?></label>
                                    <select class="form-control" name="length" >
                                        <?php foreach($this->tokenLengths as $length => $amount){ ?>
                                            <option value="<?=$length ?>" <?php if ($this->getParam('length') == $length) { echo "selected"; } ?>"> <?= $length?> </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <input type="hidden" name="id" value="<?= $this->getParam('id') ?>">

                            <div class="form-group">
                                <div class=" col col-sm-12">
                                    <button class="btn btn-primary" type="submit"><?=$this->translateAdmin('plugin_onlineshop_voucherservice_apply-filter-button')?></button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <div class="tab-pane" id="statistics">
                <div class="row">
                    <div class="col col-sm-12">
                        <h2><?=$this->translateAdmin('plugin_onlineshop_voucherservice_tab-statistics-headline')?></h2>
                    </div>
                </div>

                <div class="row header">

                    <div class="col col-sm-4">
                        <?php if ($this->error) { ?>
                            <div class="alert alert-danger"> <?= $this->error ?>  </div>
                        <?php } ?>
                    </div>
                    <div class="col col-sm-8 text-right">
                        <div class="btn-group">
                            <button type="button" class="btn btn-default js-modal" data-modal="cleanup-reservations"><span class="glyphicon glyphicon-refresh"></span>
                                <?=$this->translateAdmin('plugin_onlineshop_voucherservice_cleanup-reservations-button')?></button>
                        </div>
                    </div>
                </div>

                <?= $this->template('PimcoreEcommerceFrameworkBundle:Voucher/parts:statistics.html.php', ['statistics' => $this->statistics, 'colors' => $colors]) ?>
            </div>
        </div>
    </div>
</div>


<!-- Modal Templates -->
<?= $this->template('PimcoreEcommerceFrameworkBundle:Voucher/parts/modals/pattern:cleanupModal.html.php', ['id' => $seriesId]) ?>
<?= $this->template('PimcoreEcommerceFrameworkBundle:Voucher/parts/modals/pattern:generateModal.html.php', ['settings' => $this->settings, 'urlParams' => $urlParams]) ?>
<?= $this->template('PimcoreEcommerceFrameworkBundle:Voucher/parts/modals:cleanupReservationsModal.html.php', ['urlParams' => $urlParams]) ?>

<!--Plugin and Lib Scripts -->
<script src="/bundles/pimcoreecommerceframework/vendor/jquery-2.1.3.min.js"></script>
<script src="/bundles/pimcoreecommerceframework/vendor/bootstrap/js/bootstrap.min.js"></script>

<script src="/bundles/pimcoreecommerceframework/vendor/picker.v3.5.3.js"></script>
<script src="/bundles/pimcoreecommerceframework/vendor/picker.date.v3.5.3.js"></script>
<script src="/bundles/pimcoreecommerceframework/vendor/chart.min.js"></script>

<script src="/bundles/pimcoreecommerceframework/js/voucherservice/voucherSeriesTabScript.js"></script>


<!--Script for statistics-->
<?php if (is_array($this->statistics['usage'])) { ?>
    <?= $this->template('PimcoreEcommerceFrameworkBundle:Voucher/parts:usageStatisticScript.html.php', ['usage' => $this->statistics['usage'], 'colors'=>$colors]) ?>
<?php } ?>

<!--Script for tab view-->

<script>
    $(document).ready(function ($) {

        var documentBody = $('body');

        /**
         *  Init Datepicker
         */
        $('.js-datepicker').pickadate({
            formatSubmit: 'yyyy-mm-dd',
            format: 'yyyy-mm-dd',
            disabled: true
        });

        var form = $('.js-filter-form');

        documentBody.on('click', 'th span.sort', function (e) {
            if($(this).hasClass('active')){
                $(this).toggleClass('glyphicon-chevron-down').toggleClass('glyphicon-chevron-up');
            }

            var criteria = $("<input>").attr("type", "hidden")
                .attr("name", "sort_criteria").val($(this).data('criteria'));


            var sort_order = $(this).hasClass('glyphicon-chevron-down') ? "DESC" : "ASC";

            var order = $("<input>").attr("type", "hidden")
                .attr("name", "sort_order").val(sort_order);

            form.append(criteria);
            form.append(order);

            form.submit();
        });

        /**
         * Init sort parameter and display of icon
         */
        var initSort = function () {
            var criteria = "<?=$this->getParam('sort_criteria')?>";
            var order = "<?=$this->getParam('sort_order')?>";
            var sortItemActive = $('th .sort[data-criteria="' + criteria + '"]');
            var sortItems = $('th .sort');

            if (criteria) {
                sortItems.removeClass('active');
                sortItemActive.addClass('active');
            }

            if (order == "ASC") {
                sortItemActive.removeClass("glyphicon-chevron-down");
                sortItemActive.addClass("glyphicon-chevron-up");
            } else {
                sortItemActive.removeClass("glyphicon-chevron-up");
                sortItemActive.addClass("glyphicon-chevron-down");
            }
        };

        initSort();

    });
</script>
</body>
