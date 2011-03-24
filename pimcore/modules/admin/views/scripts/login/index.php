<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="robots" content="noindex, nofollow" />
    
    <title>Welcome to Pimcore!</title>    
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

<?php

//detect browser
$supported = false;
$browser = new Pimcore_Browser();  
$browserVersion = (int) $browser->getVersion();
        
if ($browser->getBrowser() == "Firefox" && $browserVersion >= 3) {
    $supported = true;
}
if ($browser->getBrowser() == "Internet Explorer" && $browserVersion >= 8) {
    $supported = true;
}
if ($browser->getBrowser() == "Chrome" && $browserVersion >= 5) {
    $supported = true;
}
if ($browser->getBrowser() == "Safari" && $browserVersion >= 4) {
    $supported = true;
}

?>

<img src="/pimcore/static/img/loading.gif" width="1" height="1"/>

<div id="wrap">
    <div id="error">
        <?php if ($this->error) { ?>
            <div class="message">
                <?php echo $this->translate($this->error) ?>
            </div>
        <?php } ?>
    </div>
    <div id="login">
        <form id="form" action="/admin/login/login" method="post" enctype="application/x-www-form-urlencoded">
            <p>
                <label>User</label>
                <input type="text" name="username" id="username"/>
            </p>

            <p>
                <label>Password</label>
                <input type="password" name="password"/>
            </p>
            <input type="image" name="submit" src="/pimcore/static/img/login/submit.png" value="Login" class="submit"
                   alt="Login" title="Login"/>
            <span class="clear"></span>
            <div class="link" ><a href="/admin/login/lostpassword">lost password?</a></div>
        </form>

    <?php if (!$supported) { ?>
        <div class="browsernotsupported">
            <div class="text">Your browser is out of date! Please install the newest version of one of the following
                browsers.
            </div>
            <a href="http://www.mozilla.com/" target="_blank"><img src="/pimcore/static/img/login/firefox.png"/></a>
            <a href="http://www.google.com/chrome/" target="_blank"><img
                    src="/pimcore/static/img/login/chrome.png"/></a>
            <a href="http://www.apple.com/safari/" target="_blank"><img src="/pimcore/static/img/login/safari.png"/></a>
            <a href="http://www.microsoft.com/" target="_blank"><img src="/pimcore/static/img/login/ie.png"/></a>

            <div class="text" style="cursor: pointer;" onclick="showLogin();">Click here to proceed</div>
        </div>

        <script type="text/javascript">
            function showLogin() {
                document.getElementById("form").style.display = "block";
            }
        </script>
        <style type="text/css">
            #form {
                display: none;
            }
        </style>
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