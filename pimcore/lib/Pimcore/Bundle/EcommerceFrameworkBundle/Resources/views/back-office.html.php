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
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables\GlobalVariables $app
 */

?>

<!DOCTYPE html>
<html lang="<?= $this->getLocale() ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Ecommerce Framework Back Office</title>

    <!-- Bootstrap core CSS -->
    <link href="/bundles/pimcoreecommerceframework/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="/bundles/pimcoreecommerceframework/css/back-office.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body>

<!-- Fixed navbar -->
<div class="navbar navbar-default navbar-fixed-top" role="navigation">
    <div class="container">

        <div class="navbar-header">
            <a href="#" class="navbar-brand dropdown-toggle"><span class="glyphicon glyphicon-shopping-cart"></span> Ecommerce Framework Back Office</span></a>
        </div>

        <div class="navbar-collapse collapse">
            <ul class="nav navbar-nav navbar-right">
                <?php
                $user = \Pimcore\Tool\Admin::getCurrentUser();
                $currentRouteName = $this->getRequest()->get('_route');
                $arrActions = [];

                if($user->isAllowed('bundle_ecommerce_back-office_order'))
                {
                    $arrActions['order'][] = 'list';
                }
                ?>

                <?php foreach($arrActions as $controller => $actions) { ?>
                    <?php foreach($actions as $action) { ?>

                        <?php $route = "pimcore_ecommerce_backend_admin-" . $controller . "_" . $action ?>
                    <li class="<?= $currentRouteName == $route ? 'active' : '' ?>">
                        <a href="<?= $this->path($route); ?>"><?= $this->translateAdmin('bundle_ecommerce.back-office.' . $controller.'-'.$action) ?></a>
                    </li>
                    <?php } ?>
                <?php } ?>

                <!-- notifications -->
                <!--
                <li class="hidden-xs">
                    <a class="dropdown-toggle" href="#" data-toggle="dropdown" title="Notifications">
                        <span class="glyphicon glyphicon-inbox" style="position: static; font-size: 1.33333333em; line-height: 0.75em; vertical-align: -15%;">
                            <span class="count-circle count-circle-middle slide-up">1</span>
                        </span>
                    </a>
                    <div class="dropdown-menu" style="min-width:250px; padding: 5px;">
                        <div class="list-group margin-bottom-5">
                            <a href="#" class="list-group-item small"><span class="badge bg-warning pulse">5</span> Logging overages in sector C.</a>
                            <a href="#" class="list-group-item small"><span class="badge bg-danger">14</span> <span class="text-warning">Users with request timed out.</span></a>
                            <a href="#" class="list-group-item small"><span class="badge bg-success">0</span> Service errors since 12:01AM.</a>
                            <a href="#" class="list-group-item small"><span class="badge">1</span> Blade server pending backup.</a>
                        </div>
                        <p class="text-center"><a href="/user-profile" class="small">All notifications</a></p>
                    </div>
                </li>
                -->
            </ul>
        </div><!--/.nav-collapse -->
    </div>
</div>

<div class="container">

    <?php $this->slots()->output('_content') ?>

</div>


<?= $this->headLink() ?>

<!-- Bootstrap core JavaScript
================================================== -->
<!-- Placed at the end of the document so the pages load faster -->
<script src="/bundles/pimcoreecommerceframework/vendor/jquery-1.11.1.min.js"></script>
<script src="/bundles/pimcoreecommerceframework/vendor/bootstrap/js/bootstrap.min.js"></script>

<script>
    var pimcore = parent.pimcore;
</script>

<?= $this->headScript() ?>

<!--<script src="/bundles/pimcoreecommerceframework/js/back-office.js"></script>-->

</body>
</html>
