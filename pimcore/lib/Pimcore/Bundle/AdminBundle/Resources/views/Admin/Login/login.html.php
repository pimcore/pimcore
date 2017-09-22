<?php
/** @var $view \Pimcore\Templating\PhpEngine */
$view->extend('PimcoreAdminBundle:Admin/Login:layout.html.php');

$this->get("translate")->setDomain("admin");

//detect browser
$supported      = false;
$browser        = new \Pimcore\Browser();
$browserVersion = (int)$browser->getVersion();
$platform       = $browser->getPlatform();

if ($browser->getBrowser() == \Pimcore\Browser::BROWSER_FIREFOX && $browserVersion >= 39) {
    $supported = true;
}
if ($browser->getBrowser() == \Pimcore\Browser::BROWSER_IE && $browserVersion >= 11) {
    $supported = true;
}
if ($browser->getBrowser() == \Pimcore\Browser::BROWSER_CHROME && $browserVersion >= 40) {
    $supported = true;
}
if ($browser->getBrowser() == \Pimcore\Browser::BROWSER_SAFARI && $browserVersion >= 7) {
    $supported = true;
}
if ($browser->getBrowser() == \Pimcore\Browser::BROWSER_OPERA && $browserVersion >= 30) {
    $supported = true;
}

$config = $this->config;
?>

<?php if ($config->general->loginscreencustomimage) { ?>
    <style type="text/css">
        body {
            background: url(<?= $config->general->loginscreencustomimage; ?>) no-repeat center center fixed;
            -webkit-background-size: cover;
            -moz-background-size: cover;
            -o-background-size: cover;
            background-size: cover;
        }

        #header {
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }

        #content {
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }
    </style>
<?php } ?>

<div id="vcenter" class="<?= ($config->general->loginscreencustomimage ? "customimage" : ""); ?>">
    <div id="hcenter">
        <div id="header">
            <img src="/pimcore/static6/img/logo-claim-gray.svg">
        </div>
        <div id="content">
            <div id="loginform">
                <form method="post" action="<?= $view->router()->path('pimcore_admin_login_check') ?>" autocomplete="off">

                    <?php if ($this->error) { ?>
                        <div class="body error">
                            <?= $this->translate($this->error) ?>
                        </div>
                    <?php } ?>

                    <div class="form-fields">
                        <input type="text" name="username" placeholder="<?= $this->translate("Username"); ?>" required autofocus/>
                        <input type="password" name="password" placeholder="<?= $this->translate("Password"); ?>" required/>
                    </div>

                    <div class="body">
                        <button type="submit"><?= $this->translate("Login"); ?></button>
                    </div>
                </form>

                <div class="body lostpassword">
                    <a href="<?= $view->router()->path('pimcore_admin_login_lostpassword') ?>" class="lostpassword"><?= $this->translate("Forgot your password"); ?>?</a>
                </div>
            </div>

            <?php if (!$supported) { ?>
                <div id="browserinfo">
                    <div class="error">
                        <?= $this->translate("Your browser is not supported. Please install the latest version of one of the following browsers."); ?>
                    </div>

                    <div class="body">
                        <div class="links">
                            <a href="http://www.google.com/chrome/" target="_blank" title="Chrome"><img src="/pimcore/static6/img/login/chrome.svg" alt="Chrome"/></a>
                            <a href="http://www.mozilla.com/" target="_blank" title="Firefox"><img src="/pimcore/static6/img/login/firefox.svg" alt="Firefox"/></a>
                            <a href="http://www.apple.com/safari/" target="_blank" title="Safari"><img src="/pimcore/static6/img/login/safari.svg" alt="Safari"/></a>
                            <a href="http://www.microsoft.com/" target="_blank" title="Edge"><img src="/pimcore/static6/img/login/edge.svg" alt="Edge"/></a>
                        </div>
                        <br>
                        <a href="#" onclick="showLogin();"><?= $this->translate("Click here to proceed"); ?></a>
                    </div>

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
    </div>
</div>

<?php $view->slots()->start('below_footer') ?>

    <?php if (!$config->general->loginscreencustomimage) { ?>
        <div id="background"></div>
        <div id="backgroundImageInfo"></div>
    <?php } ?>

    <script type="text/javascript">
        <?php if(!$view->getParam("deeplink")) { ?>
        // clear opened tabs store
        localStorage.removeItem("pimcore_opentabs");
        <?php } ?>
        $("#username").select();
    </script>



<?php $view->slots()->stop() ?>
