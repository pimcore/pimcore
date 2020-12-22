<?php
/** @var \Pimcore\Templating\PhpEngine $view */
$view->extend('PimcoreAdminBundle:Admin/Login:layout.html.php');

$this->get("translate")->setDomain("admin");

//detect browser
$supported      = false;
$browser        = new \Pimcore\Browser();
$browserVersion = (float)$browser->getVersion();
$platform       = $browser->getPlatform();

if ($browser->getBrowser() == \Pimcore\Browser::BROWSER_FIREFOX && $browserVersion >= 72) {
    $supported = true;
}
if ($browser->getBrowser() == \Pimcore\Browser::BROWSER_CHROME && $browserVersion >= 84) {
    $supported = true;
}
if ($browser->getBrowser() == \Pimcore\Browser::BROWSER_SAFARI && $browserVersion >= 13.1) {
    $supported = true;
}
if ($browser->getBrowser() == \Pimcore\Browser::BROWSER_OPERA && $browserVersion >= 67) {
    $supported = true;
}
if ($browser->getBrowser() == \Pimcore\Browser::BROWSER_EDGE && $browserVersion >= 84) {
    $supported = true;
}

?>


<div id="loginform">
    <form id="form-element" method="post" action="<?= $view->router()->path('pimcore_admin_login_check', ['perspective' => strip_tags($view->request()->getParameter('perspective'))]) ?>">

        <?php if ($this->error) { ?>
            <div class="text error">
                <?= $this->translate($this->error) ?>
            </div>
        <?php } ?>

        <input type="text" name="username" autocomplete="username" placeholder="<?= $this->translate("Username"); ?>" required autofocus>
        <input type="password" name="password" autocomplete="current-password" placeholder="<?= $this->translate("Password"); ?>" required>
        <input type="hidden" name="csrfToken" id="csrfToken" value="<?= $this->csrfToken ?>">

        <button type="submit"><?= $this->translate("Login"); ?></button>
    </form>

    <a href="<?= $view->router()->path('pimcore_admin_login_lostpassword') ?>" class="lostpassword"><?= $this->translate("Forgot your password"); ?>?</a>
</div>

<?php if (!$supported) { ?>
    <div id="browserinfo">
        <div class="text">
            <?= $this->translate("Your browser is not supported. Please install the latest version of one of the following browsers."); ?>
        </div>

        <div class="text browserinfo">
            <a href="http://www.google.com/chrome/" target="_blank" title="Chrome"><img src="/bundles/pimcoreadmin/img/login/chrome.svg" alt="Chrome"/></a>
            <a href="http://www.mozilla.org/" target="_blank" title="Firefox"><img src="/bundles/pimcoreadmin/img/login/firefox.svg" alt="Firefox"/></a>
            <a href="http://www.apple.com/safari/" target="_blank" title="Safari"><img src="/bundles/pimcoreadmin/img/login/safari.svg" alt="Safari"/></a>
            <a href="http://www.microsoft.com/" target="_blank" title="Edge"><img src="/bundles/pimcoreadmin/img/login/edge.svg" alt="Edge"/></a>
        </div>

        <a href="#" onclick="showLogin();"><?= $this->translate("Click here to proceed"); ?></a>
    </div>

    <script type="text/javascript">
        function showLogin() {
            document.getElementById('loginform').style.display = 'block';
            document.getElementById('browserinfo').style.display = 'none';
        }
    </script>
    <style type="text/css">
        #loginform {
            display: none;
        }
    </style>

<?php } ?>

<?php $view->slots()->start('below_footer') ?>

<script>
    <?php if(!$view->getParam("deeplink")) { ?>
    // clear opened tabs store
    localStorage.removeItem("pimcore_opentabs");
    <?php } ?>

    // hide symfony toolbar by default
    var symfonyToolbarKey = 'symfony/profiler/toolbar/displayState';
    if(!window.localStorage.getItem(symfonyToolbarKey)) {
        window.localStorage.setItem(symfonyToolbarKey, 'none');
    }

    var formElement = document.getElementById('form-element');
    var csrfRefreshInProgress = false;

    function refreshCsrfToken() {
        csrfRefreshInProgress = true;
        formElement.style.opacity = '0.3';

        var request = new XMLHttpRequest();
        request.open('GET', '<?= $view->router()->path('pimcore_admin_login_csrf_token') ?>');
        request.onload = function () {
            if (this.status >= 200 && this.status < 400) {
                var res = JSON.parse(this.response);
                document.getElementById('csrfToken').setAttribute('value', res['csrfToken']);

                formElement.style.opacity = '1';
                csrfRefreshInProgress = false;
            }
        };
        request.send();
    }

    document.addEventListener('visibilitychange', function(ev) {
        if(document.visibilityState === 'visible') {
            refreshCsrfToken();
        }
    });

    window.setInterval(refreshCsrfToken, <?= $this->csrfTokenRefreshInterval ?>);

    formElement.addEventListener("submit", function(evt) {
        if(csrfRefreshInProgress) {
            evt.preventDefault();
        }
    }, true);

</script>

<?php $view->slots()->stop() ?>

<?= $this->breachAttackRandomContent(); ?>
