<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml">
<head>

    <title>Welcome to pimcore!</title>

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

<?php

//detect browser
$supported = false;
$browser = new Pimcore_Browser();
$browserVersion = (int) $browser->getVersion();
$platform = $browser->getPlatform();

if ($browser->getBrowser() == Pimcore_Browser::BROWSER_FIREFOX && $browserVersion >= 3) {
    $supported = true;
}
if ($browser->getBrowser() == Pimcore_Browser::BROWSER_IE && $browserVersion >= 8) {
    $supported = true;
}
if ($browser->getBrowser() == Pimcore_Browser::BROWSER_CHROME && $browserVersion >= 5) {
    $supported = true;
}
if ($browser->getBrowser() == Pimcore_Browser::BROWSER_SAFARI && $browserVersion >= 4) {
    $supported = true;
}

$config = Pimcore_Config::getSystemConfig();

?>

<?php if ($config->general->loginscreencustomimage) { ?>
    <img src="<?php echo $config->general->loginscreencustomimage; ?>" class="background" id="backgroundimage" />
<?php } else { ?>
    <img src="/pimcore/static/img/login-reloaded/background.jpg" class="background" id="backgroundimage" />
<?php } ?>

<img src="/pimcore/static/img/loading.gif" width="1" height="1"/>

<div id="vcenter">
    <div id="content">

        <?php if ($this->error) { ?>
            <div class="error">
                <?php echo $this->translate($this->error) ?>
            </div>
        <?php } else { ?>
            <div class="logo"></div>
        <?php } ?>

        <form id="loginform" action="/admin/login/login" method="post" enctype="application/x-www-form-urlencoded">
            <p>
                <label><?php echo $this->translate("Username"); ?></label>
                <input class="credential" name="username" id="username" type="text" />
                <span class="clear"></span>
            </p>
            <p>
                <label><?php echo $this->translate("Password"); ?></label>
                <input class="credential" name="password" type="password" />
                <span class="clear"></span>
            </p>
            <p class="submit">
                <input class="submit" type="submit" value="<?php echo $this->translate("Login"); ?>" />
                <a href="/admin/login/lostpassword" class="lostpassword"><?php echo $this->translate("Forgot your password"); ?>?</a>
            </p>
        </form>

        <?php if (!$supported) { ?>
            <div id="browserinfo">
                <div class="message"><?php echo $this->translate("Your browser is not supported. Please install the latest version of one of the following browsers."); ?></div>
                <div class="links">
                    <a href="http://www.mozilla.com/" target="_blank"><img src="/pimcore/static/img/login-reloaded/firefox.png"/></a>
                    <a href="http://www.google.com/chrome/" target="_blank"><img src="/pimcore/static/img/login-reloaded/chrome.png"/></a>
                    <a href="http://www.apple.com/safari/" target="_blank"><img src="/pimcore/static/img/login-reloaded/safari.png"/></a>
                    <a href="http://www.microsoft.com/" target="_blank"><img src="/pimcore/static/img/login-reloaded/ie.png"/></a>
                </div>
                <div class="proceed" style="cursor: pointer;" onclick="showLogin();"><?php echo $this->translate("Click here to proceed"); ?></div>
            </div>
            <script type="text/javascript">
                function showLogin() {
                    document.getElementById("loginform").style.display = "block";
                    document.getElementById("browserinfo").style.display = "none";
                }
            </script>
            <style type="text/css">
                #loginform {
                    display: none;
                }
            </style>
        <?php } ?>

    </div>
</div>

<div id="footer">
    <div class="left" id="imageinfo"></div>
    <div class="right">pimcore. Open Source Framework for Content and Product Information Management<br />&copy; 2009-<?php echo date("Y") ?> elements.at New Media Solutions GmbH</div>
    <div class="background"></div>
</div>


<script type="text/javascript">
    document.getElementById("username").focus();
</script>

<?php if ($config->general->loginscreenimageservice) { ?>
    <script type="text/javascript" src="https://www.pimcore.org/imageservice/?nocache=1"></script>
<?php } ?>

</body>
</html>