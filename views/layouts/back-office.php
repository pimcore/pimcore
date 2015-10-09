<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Online-Shop Back Office</title>

    <!-- Bootstrap core CSS -->
    <link href="/plugins/OnlineShop/static/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="/plugins/OnlineShop/static/css/back-office.css" rel="stylesheet">

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
            <a href="#" class="navbar-brand dropdown-toggle"><span class="glyphicon glyphicon-shopping-cart"></span> Online-Shop Back Office</span></a>
        </div>

        <div class="navbar-collapse collapse">
            <ul class="nav navbar-nav navbar-right">
                <?php
                $user = \Pimcore\Tool\Admin::getCurrentUser();
                $currentAction = Zend_Controller_Front::getInstance()->getRequest()->getActionName();
                $currentController = Zend_Controller_Front::getInstance()->getRequest()->getControllerName();
                $arrActions = [];

                if($user->isAllowed('plugin_onlineshop_back-office_order'))
                {
                    $arrActions['order'][] = 'list';
                }

                foreach($arrActions as $controller => $actions):
                    foreach($actions as $action): ?>
                    <li class="<?= $currentController == 'admin-' . $controller && $currentAction == $action ? 'active' : '' ?>">
                        <a href="<?= $this->url(['action' => $action, 'controller' => 'admin-' . $controller, 'module' => 'OnlineShop'], null, true); ?>"><?= $this->translate('online-shop.back-office.' . $controller.'-'.$action) ?></a>
                    </li>
                    <?php endforeach; ?>
                <?php endforeach; ?>

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

    <?= $this->layout()->content ?>

</div>


<?= $this->headLink() ?>

<!-- Bootstrap core JavaScript
================================================== -->
<!-- Placed at the end of the document so the pages load faster -->
<script src="/plugins/OnlineShop/static/vendor/jquery-1.11.1.min.js"></script>
<script src="/plugins/OnlineShop/static/vendor/bootstrap/js/bootstrap.min.js"></script>

<script>
    var pimcore = parent.pimcore;
</script>

<?= $this->headScript() ?>

<!--<script src="/plugins/OnlineShop/static/js/back-office.js"></script>-->

</body>
</html>
