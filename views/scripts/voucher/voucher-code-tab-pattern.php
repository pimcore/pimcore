<head>
    <link href="/plugins/OnlineShop/static/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="/plugins/OnlineShop/static/vendor/bootstrap/css/bootstrap-theme.min.css" rel="stylesheet">
    <link href="/plugins/OnlineShop/static/vendor/pickadate.classic.css" rel="stylesheet">
    <link href="/plugins/OnlineShop/static/vendor/pickadate.classic.date.css" rel="stylesheet">
    <link href="/plugins/OnlineShop/static/css/voucherservice/style.css" rel="stylesheet">
</head>
<body>

<?


$seriesId = $this->getParam('id');
$url = $this->url(['controller' => 'voucher', 'action' => 'voucher-code-tab', "id" => $seriesId], 'plugins', true);
if ($this->paginator) {
    $this->paginator->setCurrentPageNumber($this->getParam('page'));
    $this->paginator->setPageRange(10);

    $pagesCount = $this->paginator->getItemCountPerPage();

    $paginationTemplate = $this->paginationControl($this->paginator,
        'Sliding',
        'voucher/parts/paginator.php',
        ['seriesId' => $seriesId]
    );
}

?>

<div class="container-fluid">
    <div id="content">
        <ul id="tabs" class="nav nav-tabs" data-tabs="tabs">
            <li class="active"><a href="#manager" data-toggle="tab"><?=$this->ts('plugin_onlineshop_voucherservice_tab-manager')?></a></li>
            <li><a href="#statistics" id="statistic-tab" data-toggle="tab"><?=$this->ts('plugin_onlineshop_voucherservice_tab-statistics')?></a></li>
        </ul>

        <div id="my-tab-content" class="tab-content">
            <div class="tab-pane active" id="manager">
                <div class="row">
                    <div class="col col-sm-12">
                        <h2><?=$this->ts('plugin_onlineshop_voucherservice_tab-manager-headline')?></h2>
                    </div>
                </div>

                <div class="row header">
                    <div class="col col-sm-4">
                        <button type="button" class="btn btn-primary js-modal" data-modal="generate"><?=$this->ts('plugin_onlineshop_voucherservice_generate-button')?></button>
                        <? if ($this->voucherType != "single") { ?>
                        <button type="button" class="btn btn-default js-modal" data-modal="cleanUp"><?=$this->ts('plugin_onlineshop_voucherservice_cleanup-button')?></button>
                        <? } ?>
                    </div>


                    <!--Info and Error Messages Container-->

                    <div class="col col-sm-4">
                        <? if ($this->msg['error']) { ?>
                            <div class="alert alert-danger js-fadeout"> <?= $this->msg['error'] ?>  </div>
                        <? } elseif ($this->msg['success']) { ?>
                            <div class="alert alert-success js-fadeout"> <?= $this->msg['success'] ?>  </div>
                       <? } elseif ($this->msg['result']) { ?>
                            <div class="alert alert-info js-fadeout"> <?= $this->msg['result'] ?>  </div>
                        <? } ?>
                    </div>


                    <div class="col col-sm-4 text-right">
                        <div class="btn-group">
                            <button type="button" class="btn btn-default"><span class="glyphicon glyphicon-export"></span> <?=$this->ts('plugin_onlineshop_voucherservice_export-button')?></button>
                        </div>
                    </div>
                </div>

                <div class="row content-block token-container">

                    <div class="col col-sm-8 token-overview">
                        <div class=" row">
                            <div class="col col-sm-5">
                                <h3 style="float: left;"><i class="glyphicon glyphicon-list"></i>&nbsp;<?=$this->ts('plugin_onlineshop_voucherservice_token-overview-headline')?></h3>
                            </div>
                            <div class="col col-sm-7 text-right">
                                <?= $paginationTemplate ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col col-sm-6">
                                <? if ($this->voucherType != "single") { ?>
                                    <h5 class="subtitle"><?= number_format($this->count, 0, ',', ' ') ?> <?=$this->ts('plugin_onlineshop_voucherservice_result-text')?></h5>
                                <? } ?>
                            </div>
                            <?if($this->paginator){?>
                            <div class="col col-sm-6 text-right">
                                <h5 class="subtitle pages"><?=$this->ts('plugin_onlineshop_voucherservice_tokens-per-page')?>
                                    <a class="pages-count <?if($pagesCount == 25){echo "active";}?>" href="<?= $this->url(array('tokensPerPage' => 25, 'id' => $seriesId)); ?>">25&nbsp;</a>
                                    <a class="pages-count <?if($pagesCount == 50){echo "active";}?>" href="<?= $this->url(array('tokensPerPage' => 50, 'id' => $seriesId)); ?>">50&nbsp;</a>
                                    <a class="pages-count <?if($pagesCount == 100){echo "active";}?>" href="<?= $this->url(array('tokensPerPage' => 100, 'id' => $seriesId)); ?>">100&nbsp;</a>
                                </h5>
                            </div>
                            <?}?>
                        </div>

                        <div class="table-container">
                            <table class="table">
                                <thead>
                                <tr class="active">
                                    <th><span class="sort glyphicon glyphicon-chevron-down" data-criteria="token"></span>&nbsp;<?=$this->ts('plugin_onlineshop_voucherservice_table-token')?></th>
                                    <th class="text-center"><span class="sort glyphicon glyphicon-chevron-down" data-criteria="usages"></span>&nbsp;<?=$this->ts('plugin_onlineshop_voucherservice_table-usages')?></th>
                                    <th class="text-center"><span class="sort glyphicon glyphicon-chevron-down" data-criteria="length"></span>&nbsp;<?=$this->ts('plugin_onlineshop_voucherservice_table-length')?></th>
                                    <th class="text-center"><span class="sort glyphicon glyphicon-chevron-down active" data-criteria="timestamp"></span>&nbsp;<?=$this->ts('plugin_onlineshop_voucherservice_table-date')?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <? if ($this->paginator) { ?>
                                    <? foreach ($this->paginator as $code) { ?>
                                        <tr>
                                            <td class="token"><?= $code['token'] ?></td>
                                            <td class="text-center"><?= (int)$code['usages'] ?></td>
                                            <td class="text-center"><?= (int)$code['length'] ?></td>
                                            <td class="text-center"><?= $code['timestamp'] ?></td>
                                        </tr>
                                    <? } ?>
                                <? } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <? if ($this->voucherType != "single") { ?>
                    <div class="col col-sm-4 filter">
                        <h3><i class="glyphicon glyphicon-search"></i> &nbsp;<?=$this->ts('plugin_onlineshop_voucherservice_filter-headline')?></h3>

                        <form class="form-horizontal js-filter-form" action="<?=$this->url()?>">
                            <div class="form-group">
                                <div class=" col col-sm-12">
                                    <label><?=$this->ts('plugin_onlineshop_voucherservice_filter-token')?></label>
                                    <input type="text" name="token" value="<?= $this->getParam('token') ?>" placeholder="token"
                                           class="form-control"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class=" col col-sm-6">
                                    <label><?=$this->ts('plugin_onlineshop_voucherservice_filter-from-date')?></label>
                                    <input type="text" name="creation_from" value="<?= $this->getParam('creation_from') ?>"
                                           placeholder="YYYY/MM/DD" class="js-datepicker form-control"/>
                                </div>
                                <div class=" col col-sm-6">
                                    <label><?=$this->ts('plugin_onlineshop_voucherservice_filter-to-date')?></label>
                                    <input type="text" name="creation_to" value="<?= $this->getParam('creation_to') ?>"
                                           placeholder="YYYY/MM/DD" class="js-datepicker form-control"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class=" col col-sm-6">
                                    <label><?=$this->ts('plugin_onlineshop_voucherservice_filter-usages')?></label>
                                    <input type="number" name="usages" value="<?= $this->getParam('usages') ?>" min="0"
                                           placeholder="usages" class="form-control"/>
                                </div>
                                <div class=" col col-sm-6">
                                    <label><?=$this->ts('plugin_onlineshop_voucherservice_filter-length')?></label>
                                    <input type="number" name="length" value="<?= $this->getParam('length') ?>" min="0"
                                           placeholder="length" class="form-control"/>
                                </div>
                            </div>

                            <input type="hidden" name="id" value="<?= $this->getParam('id') ?>">

                            <div class="form-group">
                                <div class=" col col-sm-12">
                                    <button class="btn btn-primary" type="submit"><?=$this->ts('plugin_onlineshop_voucherservice_apply-filter-button')?></button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <?}?>
                </div>
            </div>
            <div class="tab-pane" id="statistics">
                <div class="row">
                    <div class="col col-sm-12">
                        <h2><?=$this->ts('plugin_onlineshop_voucherservice_tab-statistics-headline')?></h2>
                    </div>
                </div>

                <div class="row header">

                    <div class="col col-sm-4">
                        <? if ($this->error) { ?>
                            <div class="alert alert-danger"> <?= $this->error ?>  </div>
                        <? } ?>
                    </div>
                    <div class="col col-sm-8 text-right">
                        <div class="btn-group">
                            <button type="button" class="btn btn-default js-modal" data-modal="cleanup-reservations"><span class="glyphicon glyphicon-refresh"></span>
                                <?=$this->ts('plugin_onlineshop_voucherservice_cleanup-reservations-button')?></button>
                        </div>
                    </div>
                </div>

                <div class="row border content-block">
                    <div class="col col-sm-3">
                        <div class="statistics">
                            <h3><?=$this->ts('plugin_onlineshop_voucherservice_token-statistic-headline')?></h3>
                        </div>
                        <canvas id="canvas-token"></canvas>
                        <table class="table current-data" style="margin-top: 35px;">
                            <tbody>
                            <tr>
                                <td><?=$this->ts('plugin_onlineshop_voucherservice_token-overall')?></td>
                                <td><?= number_format($this->statistics['overallCount'], 0, ',', ' ') ?></td>
                            </tr>
                            <tr>
                                <td><?=$this->ts('plugin_onlineshop_voucherservice_token-used')?></td>
                                <td><?= number_format($this->statistics['usageCount'], 0, ',', ' ') ?></td>
                            </tr>
                            <tr>
                                <td><?=$this->ts('plugin_onlineshop_voucherservice_token-reserved')?></td>
                                <td><?= number_format($this->statistics['reservedCount'], 0, ',', ' ') ?></td>
                            </tr>
                            <tr>
                                <td><?=$this->ts('plugin_onlineshop_voucherservice_token-free')?></td>
                                <td><?= number_format($this->statistics['freeCount'], 0, ',', ' ') ?></td>
                            </tr>
                            </tbody>
                        </table>

                    </div>
                    <div class="col col-sm-9 canvas-container">
                        <div class="statistics">
                            <h3><?=$this->ts('plugin_onlineshop_voucherservice_usage-headline')?></h3>
                        </div>
                        <canvas id="canvas-usage" height="130" style="padding-right: 50px;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Modal Templates -->
<?= $this->template('voucher/parts/modals/pattern/cleanup-modal.php', ['id' => $seriesId]) ?>
<?= $this->template('voucher/parts/modals/pattern/generate-modal.php', ['settings' => $this->settings, 'id' => $seriesId]) ?>
<?= $this->template('voucher/parts/modals/cleanup-reservations-modal.php', ['id' => $seriesId]) ?>

<!--Plugin and Lib Scripts -->
<script src="/plugins/OnlineShop/static/vendor/jquery-2.1.3.min.js"></script>
<script src="/plugins/OnlineShop/static/vendor/bootstrap/js/bootstrap.min.js"></script>

<script src="/plugins/OnlineShop/static/vendor/picker.v3.5.3.js"></script>
<script src="/plugins/OnlineShop/static/vendor/picker.date.v3.5.3.js"></script>
<script src="/plugins/OnlineShop/static/vendor/chart.min.js"></script>

<script src="/plugins/OnlineShop/static/js/voucherservice/voucherSeriesTabScript.js"></script>

<!--Script for tab view-->


<? if (is_array($this->statistics['usage'])) { ?>
    <?= $this->template('voucher/parts/usageStatisticScript.php', ['usage' => $this->statistics['usage']]) ?>
<? } ?>

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

        documentBody.on('click', 'th span.sort', function (e) {
            var form = $('.js-filter-form');

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
