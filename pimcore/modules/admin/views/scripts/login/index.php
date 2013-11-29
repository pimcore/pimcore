<!DOCTYPE html>
<html>
<head>

    <title>Welcome to pimcore!</title>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="robots" content="noindex, follow" />

    <link rel="stylesheet" href="/pimcore/static/css/login.css" type="text/css" />
    <script type="text/javascript" src="/pimcore/static/js/lib/jquery.min.js"></script>

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

<?php

//detect browser
$supported = false;
$browser = new Pimcore_Browser();
$browserVersion = (int) $browser->getVersion();
$platform = $browser->getPlatform();

if ($browser->getBrowser() == Pimcore_Browser::BROWSER_FIREFOX && $browserVersion >= 4) {
    $supported = true;
}
if ($browser->getBrowser() == Pimcore_Browser::BROWSER_IE && $browserVersion >= 9) {
    $supported = true;
}
if ($browser->getBrowser() == Pimcore_Browser::BROWSER_CHROME && $browserVersion >= 6) {
    $supported = true;
}
if ($browser->getBrowser() == Pimcore_Browser::BROWSER_SAFARI && $browserVersion >= 5) {
    $supported = true;
}

$config = Pimcore_Config::getSystemConfig();

?>

<?php if ($config->general->loginscreencustomimage) { ?>
    <style type="text/css">
        body {
            background: url(<?php echo $config->general->loginscreencustomimage; ?>) no-repeat center center fixed;
            -webkit-background-size: cover;
            -moz-background-size: cover;
            -o-background-size: cover;
            background-size: cover;
        }
    </style>
<?php } ?>

<div id="vcenter" class="<?php echo ($config->general->loginscreencustomimage ? "customimage" : ""); ?>">
    <div id="content">
        <div id="right">
            <form id="loginform" method="post" action="/admin/login/login">
                <label><?php echo $this->translate("Username"); ?></label>
                <input id="username" type="text" name="username" />

                <label><?php echo $this->translate("Password"); ?></label>
                <input type="password" name="password" />

                <input type="submit" name="submit" value="<?php echo $this->translate("Login"); ?>" />

                <?php if ($this->error) { ?>
                    <div class="error">
                        <?php echo $this->translate($this->error) ?>
                    </div>
                <?php } ?>
            </form>

            <?php if (!$supported) { ?>
                <div id="browserinfo">
                    <div class="error">
                        <?php echo $this->translate("Your browser is not supported. Please install the latest version of one of the following browsers."); ?>
                    </div>
                    <div class="links">
                        <a href="http://www.mozilla.com/" target="_blank"><img src="/pimcore/static/img/login/firefox.png"/></a>
                        <a href="http://www.google.com/chrome/" target="_blank"><img src="/pimcore/static/img/login/chrome.png"/></a>
                        <a href="http://www.apple.com/safari/" target="_blank"><img src="/pimcore/static/img/login/safari.png"/></a>
                        <a href="http://www.microsoft.com/" target="_blank"><img src="/pimcore/static/img/login/ie.png"/></a>
                    </div>
                    <a href="#" onclick="showLogin();"><?php echo $this->translate("Click here to proceed"); ?></a>

                    <script type="text/javascript">
                        function showLogin() {
                            $("#loginform").show();
                            $("#browserinfo").hide();
                        }
                    </script>
                    <style type="text/css">
                        #loginform {
                            display: none;
                        }
                    </style>

                </div>
            <?php } ?>
        </div>
        <a href="/admin/login/lostpassword" class="lostpassword"><?php echo $this->translate("Forgot your password"); ?></a>
    </div>
</div>
<div id="footer">
    <a href="http://www.pimcore.org/">pimcore. Open Source Multichannel Experience and Engagement Platform</a>
    <br />
    &copy; 2009-<?php echo date("Y") ?> <a href="http://www.pimcore.org/">pimcore GmbH</a>, a proud member of the <a href="http://www.elements.at/">elements group</a>
</div>


<script type="text/javascript">
    <?php if(!$this->getParam("deeplink")) { ?>
        // clear opened tabs store
        localStorage.removeItem("pimcore_opentabs");
    <?php } ?>
    $("#username").select();
</script>

<script type="text/javascript" src="https://www.pimcore.org/imageservice/?nocache=1&build=<?php echo Pimcore_Version::getRevision(); ?>"></script>

</body>
</html>