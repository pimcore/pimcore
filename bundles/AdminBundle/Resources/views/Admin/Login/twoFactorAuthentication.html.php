<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables $app
 */

$view->extend('PimcoreAdminBundle:Admin/Login:layout.html.php');
$this->get("translate")->setDomain("admin");
?>

<div id="vcenter">
    <div id="hcenter">
        <div id="header">
            <img src="/admin/settings/display-custom-logo">
        </div>
        <div id="content">

            <?php if ($this->error) { ?>
                <div class="body error">
                    <?= $this->translate($this->error) ?>
                </div>
            <?php } else { ?>
                <div class="body info">
                    <?= $this->translate("Enter your verification code"); ?>
                </div>
            <?php } ?>


            <div id="twofactorform">

                <form method="post" action="<?=$this->url('pimcore_admin_2fa-verify')?>" autocomplete="off">

                    <div class="form-fields">
                        <input name="_auth_code" id="_auth_code" placeholder="<?= $this->translate("2fa_code"); ?>" required autofocus>
                    </div>

                    <div class="body">
                        <button type="submit"><?= $this->translate("Login"); ?></button>
                    </div>
                </form>
            </div>

            <div class="body lostpassword" style="padding-top: 30px;">
                <a href="<?= $view->router()->path('pimcore_admin_logout') ?>"><?= $this->translate("Back to Login"); ?></a>
            </div>

        </div>
    </div>
</div>


<?php $view->slots()->start('below_footer') ?>
<script type="text/javascript">
    $("#_auth_code").select();
</script>
<?php $view->slots()->stop() ?>
