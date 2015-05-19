<head>

    <link href="/plugins/OnlineShop/static/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="/plugins/OnlineShop/static/vendor/bootstrap/css/bootstrap-theme.min.css" rel="stylesheet">


    <link href="/plugins/OnlineShop/static/vendor/pickadate.classic.css" rel="stylesheet">
    <link href="/plugins/OnlineShop/static/vendor/pickadate.classic.date.css" rel="stylesheet">

    <style type="text/css">

        .border{
            border: 1px solid #ccc;
        }
        div.statistics {
            padding: 25px;
        }

        h3 {
            margin-top: 0;
        }

        div.table-container table td {
            font-size: 0.8em;
            padding: 2px;
        }

        div.table-container table tr td:first-of-type{
            max-width: 300px;
            word-wrap: break-word;
        }
        .token {
            font-family: courier;
        }

        div.form-group.form-group-50 {
            width: 50%
        }

        .form-control.form-control-25 {
            width: 25%;
        }

        .form-control.form-control-50 {
            width: 50%;
        }

        .form-control[type="checkbox"]{
            height: 25px;
        }
        .form-control.js-datepicker{
            cursor: pointer;
            background-color: #ffffff;
        }

        .header button.btn, .header a.btn {
            min-width: 150px;
        }

        .tab-content {
            border: 1px solid #ccc;
            border-top: 0;
            padding: 35px;
        }

        .nav.nav-tabs {
            margin-top: 20px;
        }

        div.token-container {
            border: 1px solid #ccc;
            padding: 25px;
        }

        div.token-overview {
            padding-right: 7%;
        }

        div.header {
            padding: 25px;
            border: 1px solid #ccc;
            margin-bottom: 25px;
        }

        div.paging {
            padding-top: 15px;
        }

        ul.pagination {
            margin: 0;
        }

        div.alert {
            margin-bottom: 0;
            padding: 6px 25px;
        }

        .token-container .col h3 {
            margin-top: 5px;
            margin-bottom: 30px;
        }

        .table th {
            padding-top: 0;
        }

        .table.table-only-body{
            margin-bottom: 0;
            margin-top: 15px;
        }

        .form-horizontal {
            margin-top: 45px;
        }

        .token-container .table-container{
            margin-top: 20px;
        }
        .table.table-only-body tr:first-of-type td{
            border-top: 0;
        }

        .form-horizontal div.form-group {
            margin: 15px 0;
        }

        .filter .form-group .col {
            padding-right:0;
        }

        .token-container h5.subtitle{
            color: #909090;
            margin: 0;
            margin-left: 40px;
            margin-top: -15px;
        }

        /*  MEDIA QUERIES   */

        @media screen and (max-width: 1366px) {
            div.header, div.token-container {
                padding: 15px 5px;
            }

            div.col {
                padding: 0 5px;
            }

            .token-container .token-overview h3, .token-container h5 {
                padding-left: 15px;;
            }

            .token-container .col.filter {
                padding-left: 25px;
            }
        }

        /*  MODAL STYLING  */

        div.modal-body {
            padding: 0;
            padding-top: 20px;
            padding-right: 20px;
        }

        div.modal-body-content, div.modal-footer {
            padding: 15px 35px;
        }

        .modal-body-content .form-group {
            margin: 25px 0;
        }

        .modal h3 {
            margin-top: 0;
        }

        #canvas{
            padding-right: 25px;
        }

        div.content-block{
            padding-bottom: 50px;;
        }

        .sort:hover{
            cursor: pointer;
        }
        .sort{
            font-size: 0.7em;
        }
        .sort.active{
            font-size: 1em;
        }
    </style>
</head>
<body>
<?
$url = "/plugin/OnlineShop/voucher/voucher-code-tab?id=" . $this->getParam('id');

if ($this->paginator) {
    $this->paginator->setCurrentPageNumber($this->getParam('page'));
    $this->paginator->setItemCountPerPage(20);
    $this->paginator->setPageRange(10);

    $paginationTemplate = $this->paginationControl($this->paginator,
        'Sliding',
        'voucher/parts/paginator.php',
        ['url' => $url]
    );
}
?>

<div class="container-fluid">
    <div id="content">
        <ul id="tabs" class="nav nav-tabs" data-tabs="tabs">
            <li class="active"><a href="#manager" data-toggle="tab">Manager</a></li>
            <li><a href="#statistics" id="statistic-tab" data-toggle="tab">Statistics</a></li>
        </ul>

        <div id="my-tab-content" class="tab-content">
            <div class="tab-pane active" id="manager">
                <div class="row">
                    <div class="col col-sm-12">
                        <h2>Voucher Service Tokenmanager</h2>
                    </div>
                </div>

                <div class="row header">
                    <div class="col col-sm-4">
                        <button type="button" class="btn btn-primary js-modal" data-modal="generate">Generate</button>
                        <? if ($this->voucherType != "single") { ?>
                        <button type="button" class="btn btn-default js-modal" data-modal="cleanUp">CleanUp</button>
                        <? } ?>
                    </div>

                    <div class="col col-sm-4">
                    <? if ($this->error) { ?>
                        <div class="alert alert-danger"> <?= $this->error ?>  </div>
                    <? } ?>
                    </div>
                    <div class="col col-sm-4 text-right">
                        <div class="btn-group">
                            <button type="button" class="btn btn-default"><span class="glyphicon glyphicon-export"></span> Export</button>
                        </div>
                    </div>
                </div>

                <div class="row content-block token-container">

                    <div class="col col-sm-8 token-overview">
                        <div class=" row">
                            <div class="col col-sm-5">
                                <h3 style="float: left;"><i class="glyphicon glyphicon-list"></i> &nbsp;Token Overview</h3>
                            </div>
                            <div class="col col-sm-7 text-right">
                                <?= $paginationTemplate ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col col-sm-12">
                                <? if ($this->voucherType != "single") { ?>
                                    <h5 class="subtitle"><?= number_format($this->count, 0, ',', ' ') ?> results
                                        found.</h5>
                                <? } ?>
                            </div>
                        </div>

                        <div class="table-container">
                            <table class="table">
                                <thead>
                                <tr class="active">
                                    <th><span class="sort glyphicon glyphicon-chevron-down" data-criteria="token"></span>&nbsp;Token</th>
                                    <th class="text-center"><span class="sort glyphicon glyphicon-chevron-down" data-criteria="usages"></span>&nbsp;Usages</th>
                                    <th class="text-center"><span class="sort glyphicon glyphicon-chevron-down" data-criteria="length"></span>&nbsp;Length</th>
                                    <th class="text-center"><span class="sort active glyphicon glyphicon-chevron-down" data-criteria="timestamp"></span>&nbsp;Creation Date</th>
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

                        <h3><i class="glyphicon glyphicon-search"></i> &nbsp;Filter by</h3>

                        <form class="form-horizontal js-filter-form" action="/plugin/OnlineShop/voucher/voucher-code-tab">
                            <div class="form-group">
                                <div class=" col col-sm-12">
                                    <label>Token</label>
                                    <input type="text" name="token" value="<?= $this->getParam('token') ?>" placeholder="token"
                                           class="form-control"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class=" col col-sm-6">
                                    <label>From</label>
                                    <input type="text" name="creation_from" value="<?= $this->getParam('creation_from') ?>"
                                           placeholder="YYYY/MM/DD" class="js-datepicker form-control"/>
                                </div>
                                <div class=" col col-sm-6">
                                    <label>To</label>
                                    <input type="text" name="creation_to" value="<?= $this->getParam('creation_to') ?>"
                                           placeholder="YYYY/MM/DD" class="js-datepicker form-control"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class=" col col-sm-6">
                                    <label>Usages</label>
                                    <input type="number" name="usages" value="<?= $this->getParam('usages') ?>" min="0"
                                           placeholder="usages" class="form-control"/>
                                </div>
                                <div class=" col col-sm-6">
                                    <label>Token Length</label>
                                    <input type="number" name="length" value="<?= $this->getParam('length') ?>" min="0"
                                           placeholder="length" class="form-control"/>
                                </div>
                            </div>

                            <input type="hidden" name="id" value="<?= $this->getParam('id') ?>">

                            <div class="form-group">
                                <div class=" col col-sm-12">
                                    <button class="btn btn-primary" type="submit">Apply Filter</button>
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
                        <h2>Data and Statistics</h2>
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
                            <button type="button" class="btn btn-default js-modal" data-modal="cleanup-reservations"><span class="glyphicon glyphicon-refresh"></span> CleanUp Reservations</button>
                            <button type="button" class="btn btn-default"><span class="glyphicon glyphicon-cloud-download"></span> Download Statistics</button>
                        </div>
                    </div>
                </div>

                <div class="row border content-block">
                    <div class="col col-sm-3">
                        <div class="statistics">
                            <h3>Token Statistics</h3>
                        </div>
                        <table class="table current-data">
                            <tbody>
                            <tr class="info">
                                <td>Overall</td>
                                <td><?= number_format($this->statistics['overallCount'], 0, ',', ' ') ?></td>
                            </tr>
                            <tr class="danger">
                                <td>Used</td>
                                <td><?= number_format($this->statistics['usageCount'], 0, ',', ' ') ?></td>
                            </tr>
                            <tr class="warning">
                                <td>Reserved</td>
                                <td><?= number_format($this->statistics['reservedCount'], 0, ',', ' ') ?></td>
                            </tr>
                            <tr class="success">
                                <td>Free</td>
                                <td><?= number_format($this->statistics['freeCount'], 0, ',', ' ') ?></td>
                            </tr>
                            </tbody>
                        </table>

                    </div>
                    <div class="col col-sm-9 canvas-container">
                        <div class="statistics">
                            <h3>Usage Statistics</h3>
                        </div>
                        <canvas id="canvas" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Modal Templates -->

<? if ($this->voucherType != "single") { ?>
    <?= $this->template('voucher/parts/modals/cleanup-modal.php') ?>
<? } ?>

<?= $this->template('voucher/parts/modals/generate-modal.php', ['settings' => $this->settings, 'generateWarning' => $this->generateWarning]) ?>
<?= $this->template('voucher/parts/modals/cleanup-reservations-modal.php') ?>


<!--Plugin and Lib Scripts -->

<script src="/plugins/OnlineShop/static/vendor/jquery-2.1.3.min.js"></script>
<script src="/plugins/OnlineShop/static/vendor/bootstrap/js/bootstrap.min.js"></script>

<script src="/plugins/OnlineShop/static/vendor/picker.v3.5.3.js"></script>
<script src="/plugins/OnlineShop/static/vendor/picker.date.v3.5.3.js"></script>
<script src="/plugins/OnlineShop/static/vendor/chart.min.js"></script>


<script>
    $(document).ready(function ($) {
        /**
         * Init Navigation Tabs
         */
        $('#tabs').tab();

        /**
         * Init Modal
         */
        $(".js-modal").click(function (e) {
            var selector = $(this).data('modal');
            $("#" + selector).modal({                    // wire up the actual modal functionality and show the dialog
                "backdrop": "static",
                "keyboard": true,
                "show": true                     // ensure the modal is shown immediately
            });
        });

        /**
         *  Init Datepicker
         */
        $('.js-datepicker').pickadate({
            formatSubmit: 'yyyy-mm-dd',
            format: 'yyyy-mm-dd',
            disabled: true
        });

        /**
         * Init Modal Loadings
         */
        $('body').on('click', '.modal .js-loading', function (e) {
            var text = $(this).data('msg');
            $(this).parent().html("<div class='text-left row'> <div class='col col-sm-12'> <span>" + text + "</span>&nbsp;<img class='pull-right' src='/pimcore/static/img/loading-white-bg.gif' alt='loading' style='margin-right: 40px;'><div><div>");
        });


//        var init_sort = (function(){
//           var criteria = <?//=$this->getParam('sort_criteria')?>
//           var order = <?//=$this->getParam('sort_order')?>
//
//            $('th .sort[data-criteria="criteria"]')
//        });
//
//        $('body').on('click', 'th span.sort', function (e) {
//            var form = $('.js-filter-form');
//
//            var criteria = $("<input>").attr("type", "hidden")
//                .attr("name", "sort_criteria").val($(this).data('criteria'));
//
//            var sort_order = $(this).hasClass('glyphicon-chevron-down') ? "ASC" : "DESC";
//            var order = $("<input>").attr("type", "hidden")
//                .attr("name", "sort_order").val(sort_order);
//
//            form.append(criteria);
//            form.append(order);
//
//            form.submit();
//        });

        /**
         * Line Chart for Usage Statistic
         *
         * @type {{labels: string[], datasets: {label: string, fillColor: string, strokeColor: string, pointColor: string, pointStrokeColor: string, pointHighlightFill: string, pointHighlightStroke: string, data: null[]}[]}}
         */
        var lineChartData = {
            labels: [<?foreach($this->statistics['usage'] as $date => $usage){?>"<?=$date?>",<?}?>],
            datasets: [
                {
                    label: "Usage Statistic",
                    fillColor: "rgba(220,220,220,0.2)",
                    strokeColor: "rgba(220,220,220,1)",
                    pointColor: "rgba(220,220,220,1)",
                    pointStrokeColor: "#fff",
                    pointHighlightFill: "#fff",
                    pointHighlightStroke: "rgba(220,220,220,1)",
                    data: [
                        <?foreach($this->statistics['usage'] as $date => $usage){?>
                        <?=$usage?>,
                        <?}?>
                    ]
                }
            ]
        };

        $('#statistic-tab').on('click', function () {
           window.setTimeout(function(){
               var ctx = document.getElementById("canvas").getContext("2d");
               window.myLine = new Chart(ctx).Line(lineChartData, {
                   responsive: true
               });
           },150);
        });
    });
</script>
</body>
