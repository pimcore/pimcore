<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Online-Shop BackOffice</title>

    <!-- Bootstrap core CSS -->
    <link href="/plugins/OnlineShop/static/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="/plugins/OnlineShop/static/css/backoffice.css" rel="stylesheet">

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
            <a href="#" class="navbar-brand dropdown-toggle"><span class="glyphicon glyphicon glyphicon-inbox"></span> Back-Office</span></a>
        </div>

        <div class="navbar-collapse collapse">
            <ul class="nav navbar-nav navbar-right">
                <?php
                $user = \Pimcore\Tool\Admin::getCurrentUser();
                $currentAction = Zend_Controller_Front::getInstance()->getRequest()->getActionName();
                $currentController = Zend_Controller_Front::getInstance()->getRequest()->getControllerName();
                $arrActions = [];

                if($user->isAllowed('plugin_onlineShop_backOffice_order'))
                {
                    $arrActions['order'][] = 'list';
                }

                foreach($arrActions as $controller => $actions):
                    foreach($actions as $action): ?>
                    <li class="<?= $currentController == 'admin-' . $controller && $currentAction == $action ? 'active' : '' ?>">
                        <a href="<?= $this->url(['action' => $action, 'controller' => 'admin-' . $controller, 'module' => 'BackOffice'], null, true); ?>"><?= $this->translate('onlineShop.backOffice.' . $controller.'-'.$action) ?></a>
                    </li>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </ul>
        </div><!--/.nav-collapse -->
    </div>
</div>

<div class="container">

    <?= $this->layout()->content ?>

</div><!-- /.container -->


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
</body>
</html>
