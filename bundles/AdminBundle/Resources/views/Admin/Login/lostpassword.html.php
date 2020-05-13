<?php
/** @var \Pimcore\Templating\PhpEngine $view */
$view->extend('PimcoreAdminBundle:Admin/Login:layout.html.php');

$this->get("translate")->setDomain("admin");

?>



<?php if ($this->getRequest()->getMethod() === 'POST') { ?>
    <div class="text error">
        <?= $this->translate("A temporary login link has been sent to your email address."); ?>
        <br/>
        <?= $this->translate("Please check your mailbox."); ?>
    </div>
<?php } else { ?>
    <div class="text info">
        <?= $this->translate("Enter your username and pimcore will send a login link to your email address"); ?>
    </div>

    <form method="post" action="<?= $view->router()->path('pimcore_admin_login_lostpassword') ?>">
        <input type="text" name="username" autocomplete="username" placeholder="<?= $this->translate("Username"); ?>" required autofocus>
        <input type="hidden" name="csrfToken" value="<?= $this->csrfToken ?>">

        <button type="submit" name="submit"><?= $this->translate("Submit"); ?></button>
    </form>
<?php } ?>

<a href="<?= $view->router()->path('pimcore_admin_login') ?>"><?= $this->translate("Back to Login"); ?></a>

<?= $this->breachAttackRandomContent(); ?>


