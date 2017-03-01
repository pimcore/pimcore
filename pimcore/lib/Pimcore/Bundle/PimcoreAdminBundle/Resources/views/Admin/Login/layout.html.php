<?php
/** @var $view \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine */
?>
<!DOCTYPE html>
<html>
<head>

    <title>Welcome to Pimcore!</title>

    <meta charset="UTF-8">
    <meta name="robots" content="noindex, follow"/>

    <link rel="icon" type="image/png" href="/pimcore/static6/img/favicon/favicon-32x32.png"/>

    <link rel="stylesheet" href="/pimcore/static6/css/login.css" type="text/css"/>
    <script type="text/javascript" src="/pimcore/static6/js/lib/jquery.min.js"></script>

    <?php foreach ($this->pluginCssPaths as $pluginCssPath): ?>
        <link rel="stylesheet" type="text/css" href="<?= $pluginCssPath ?>?_dc=<?= $pluginDcValue; ?>"/>
    <?php endforeach; ?>

    <?php
    // load plugin scripts
    try {
        $pluginBroker = $this->container()->get('pimcore.plugin_broker');
        if ($pluginBroker instanceof \Pimcore\API\Plugin\Broker) {
            foreach ($pluginBroker->getPlugins() as $plugin) {
                if ($plugin->isInstalled()) {
                    $cssPaths = $plugin->getCssPaths();
                    if (!empty($cssPaths)) {
                        foreach ($cssPaths as $cssPath) {
                            $cssPath = trim($cssPath);
                            if (!empty($cssPath)) {
                                ?>
                                <link rel="stylesheet" type="text/css" href="<?= $cssPath ?>?_dc=<?= time() ?>"/>
                                <?php

                            }
                        }
                    }
                }
            }
        }
    } catch (\Exception $e) {
    }
    ?>

</head>
<body>

<?php $view->slots()->output('_content') ?>

<div id="footer">
    &copy; 2009-<?= date("Y") ?> <a href="http://www.pimcore.org/">pimcore GmbH</a>, a proud member of the
    <a href="http://www.elements.at/">elements group</a>
</div>

<?php $view->slots()->output('below_footer') ?>

</body>
</html>
