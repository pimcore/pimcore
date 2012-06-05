<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml">
<head>

    <title>pimcore - Lost Password Service</title>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="robots" content="noindex, nofollow" />

    <link rel="stylesheet" href="/pimcore/static/css/login-reloaded.css" type="text/css" />

    <?php
        // load plugin scripts
        try {
            $pluginBroker = Zend_Registry::get("Pimcore_API_Plugin_Broker");
            if ($pluginBroker instanceof Pimcore_API_Plugin_Broker) {
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
        catch (Exception $e) {}
    ?>

</head>
<body>

<img src="/pimcore/static/img/login-reloaded/background.jpg" class="background" id="backgroundimage" />

<div id="vcenter">
    <div id="content">

        <?php if ($this->error) { ?>
            <div class="error">
                <?php echo $this->translate($this->error) ?>
            </div>
        <?php } else { ?>
            <div class="logo"></div>
        <?php } ?>

        <?php if($this->success) { ?>
            <form action="/admin/login/" id="loginform" method="post">
                <p style="text-align: center;">
                    <?php echo $this->translate("A temporary login link has been sent to your email address."); ?>
                    <br />
                    <?php echo $this->translate("Please check your mailbox."); ?>
                </p>
                <p class="submit">
                    <input class="submit" type="submit" value="Back to Login" />
                </p>
            </form>
        <?php } else { ?>
            <form action="/admin/login/lostpassword" id="loginform" method="post" enctype="application/x-www-form-urlencoded">
                <p>
                    <?php echo $this->translate("Enter your username and pimcore will send a login link to your email address"); ?>:
                </p>
                <p>
                    <label><?php echo $this->translate("Username"); ?></label>
                    <input class="credential" name="username" id="username" type="text" />
                    <span class="clear"></span>
                </p>
                <p class="submit">
                    <input class="submit" type="submit" value="<?php echo $this->translate("Submit"); ?>" />
                    <a href="/admin/login/" class="lostpassword"><?php echo $this->translate("Back to Login"); ?></a>
                </p>
            </form>
        <?php } ?>
    </div>
</div>

<div id="footer">
    <div class="left" id="imageinfo"></div>
    <div class="right">pimcore. Open Source Framework for Content and Product Information Management<br />&copy; <?php echo date("Y") ?>-2011 elements.at New Media Solutions GmbH</div>
    <div class="background"></div>
</div>


<script type="text/javascript">
    if(document.getElementById("username")) {
        document.getElementById("username").focus();
    }
</script>

</body>
</html>