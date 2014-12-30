<!DOCTYPE html>
<html>
<head>

    <title>Welcome to pimcore!</title>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="robots" content="noindex, follow" />

    <link rel="stylesheet" href="/pimcore/static/css/login.css" type="text/css" />

    <?php
    // load plugin scripts
    try {
        $pluginBroker = \Zend_Registry::get("Pimcore_API_Plugin_Broker");
        if ($pluginBroker instanceof \Pimcore\API\Plugin\Broker) {
            foreach ($pluginBroker->getPlugins() as $plugin) {
                if ($plugin->isInstalled()) {
                    $cssPaths = $plugin->getCssPaths();
                    if (!empty($cssPaths)) {
                        foreach ($cssPaths as $cssPath) {
                            $cssPath = trim($cssPath);
                            if (!empty($cssPath)) {
                                ?>
                                <link rel="stylesheet" type="text/css" href="<?php echo $cssPath ?>?_dc=<?php echo time() ?>"/>
                            <?php

                            }
                        }
                    }
                }
            }
        }
    }
    catch (\Exception $e) {}
    ?>

</head>
<body>

<div id="vcenter">
    <div id="hcenter">
        <div id="content">

            <?php if($this->success) { ?>
                <div class="body info">
                    <?php echo $this->translate("A temporary login link has been sent to your email address."); ?>
                    <br />
                    <?php echo $this->translate("Please check your mailbox."); ?>

                    <br />
                    <br />

                    <a href="/admin/login/"><?php echo $this->translate("Back to login"); ?></a>
                </div>
            <?php } else { ?>
                <div class="body info">
                    <?php echo $this->translate("Enter your username and pimcore will send a login link to your email address"); ?>
                </div>

                <div id="loginform">

                    <form method="post" action="/admin/login/lostpassword">
                        <div class="form-fields">
                            <input type="text" name="username" placeholder="<?= $this->translate("Username"); ?>" />
                        </div>

                        <div class="body">
                            <button type="submit" name="submit"><?= $this->translate("Submit"); ?></button>
                        </div>
                    </form>
                </div>
            <?php } ?>

            <div class="body lostpassword" style="padding-top: 30px;">
                <a href="/admin/login/"><?php echo $this->translate("Back to login"); ?></a>
            </div>
        </div>
    </div>
</div>

<div id="footer">
    <a href="http://www.pimcore.org/">pimcore. Open Source Multichannel Experience and Engagement Platform</a>
    <br />
    &copy; 2009-<?php echo date("Y") ?> <a href="http://www.pimcore.org/">pimcore GmbH</a>, a proud member of the <a href="http://www.elements.at/">elements group</a>
</div>

</body>
</html>