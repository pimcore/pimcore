<?php
/** @var $view \Pimcore\Templating\PhpEngine */
$view->extend('PimcoreAdminBundle:Admin/Login:layout.html.php');

$this->get("translate")->setDomain("admin");

?>



<?php if ($this->success) { ?>
    <div class="text info">
        <?= $this->translate("A temporary login link has been sent to your email address."); ?>
        <br/>
        <?= $this->translate("Please check your mailbox."); ?>
    </div>
<?php } else { ?>
    <?php if ($this->error) { ?>
        <div class="text error">
            <?= $this->translate('lostpassword_reset_error'); ?>
        </div>
    <?php } else { ?>
        <div class="text info">
            <?= $this->translate("Enter your username and pimcore will send a login link to your email address"); ?>
        </div>
    <?php } ?>


    <form method="post" action="<?= $view->router()->path('pimcore_admin_login_lostpassword') ?>">
        <input type="text" name="username" placeholder="<?= $this->translate("Username"); ?>" required autofocus>
        <button type="submit" name="submit"><?= $this->translate("Submit"); ?></button>
    </form>
<?php } ?>

<a href="<?= $view->router()->path('pimcore_admin_login') ?>"><?= $this->translate("Back to Login"); ?></a>
