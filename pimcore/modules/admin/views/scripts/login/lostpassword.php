<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="robots" content="noindex, nofollow" />
    
    <title>Pimcore Lost Password Service</title>
    <link rel="stylesheet" type="text/css" href="/pimcore/static/css/login.css?_dc=<?php echo Pimcore_Version::$revision ?>"/>

    <?php // load plugin scripts ?>
    <?php
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


<img src="/pimcore/static/img/loading.gif" width="1" height="1"/>
<div id="wrap">
    <div id="error">
        <?php if ($this->error) { ?>
            <div class="message">
                <?php echo $this->translate($this->error) ?>
            </div>
        <?php } ?>
    </div>
    <div id="login" class="lost">
        <?php if($this->success){?>
            <p>A temporary login link has been sent to your email address. Please check your mailbox.</p>
            <p class="back"><a href="/admin/login/">back to login</a></p>    
        <?php } else { ?>
            <h1>Enter your username and pimcore will send a login link to your email address:</h1>    
        <form id="form" action="/admin/login/lostpassword" method="post" enctype="application/x-www-form-urlencoded">

            <p>
                <label>User</label>
                <input type="text" name="username" id="username"/>
            </p>
            <input type="image" name="submit" src="/pimcore/static/img/login/submit.png" value="Send" class="submit"
                   alt="Send" title="Send"/>
            <span class="clear"></span>
            <div class="link" ><a href="/admin/login/">back to login</a></div>
        </form>
        <?php } ?>


        <div id="footer">
            <a href="http://www.pimcore.org/">Pimcore | Open-Source Content Management Framework</a>
            <br/>
            &copy; 2009-<?php echo date("Y") ?> <a href="http://www.elements.at/">elements.at New Media Solutions
            GmbH</a>
        </div>

    </div>
</div>


<script type="text/javascript">
    document.getElementById("username").focus();
</script>

</body>
</html>