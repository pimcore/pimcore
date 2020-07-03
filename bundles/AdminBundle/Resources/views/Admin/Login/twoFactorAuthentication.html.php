<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables $app
 */

$view->extend('PimcoreAdminBundle:Admin/Login:layout.html.php');
$this->get("translate")->setDomain("admin");
?>



<?php if ($this->error) { ?>
    <div class="text error">
        <?= $this->translate($this->error) ?>
    </div>
<?php } else { ?>
    <div class="text info">
        <?= $this->translate("Enter your verification code"); ?>
    </div>
<?php } ?>

<form method="post" action="<?=$this->url('pimcore_admin_2fa-verify')?>">
    <input name="_auth_code" id="_auth_code" autocomplete="one-time-code" type="password" placeholder="<?= $this->translate("2fa_code"); ?>" required autofocus>
    <input type="hidden" name="csrfToken" value="<?= $this->csrfToken ?>">

    <button type="submit"><?= $this->translate("Login"); ?></button>
</form>

<a href="<?= $view->router()->path('pimcore_admin_logout') ?>"><?= $this->translate("Back to Login"); ?></a>

<?= $this->breachAttackRandomContent(); ?>


