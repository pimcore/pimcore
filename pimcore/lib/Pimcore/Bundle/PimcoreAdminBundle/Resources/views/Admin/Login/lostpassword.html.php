<?php
/** @var $view \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine */
$view->extend('PimcoreAdminBundle:Login:layout.html.php');
?>

<div id="vcenter">
    <div id="hcenter">
        <div id="content">

            <?php if ($this->success) { ?>
                <div class="body info">
                    <?= $this->translate("A temporary login link has been sent to your email address."); ?>
                    <br/>
                    <?= $this->translate("Please check your mailbox."); ?>
                </div>
            <?php } else { ?>
                <div class="body info">
                    <?= $this->translate("Enter your username and pimcore will send a login link to your email address"); ?>
                </div>

                <div id="loginform">

                    <form method="post" action="<?= $view->router()->path('pimcore_admin_login_lostpassword') ?>">
                        <div class="form-fields">
                            <input type="text" name="username" placeholder="<?= $this->translate("Username"); ?>"/>
                        </div>

                        <div class="body">
                            <button type="submit" name="submit"><?= $this->translate("Submit"); ?></button>
                        </div>
                    </form>
                </div>
            <?php } ?>

            <div class="body lostpassword" style="padding-top: 30px;">
                <a href="<?= $view->router()->path('pimcore_admin_login') ?>"><?= $this->translate("Back to Login"); ?></a>
            </div>
        </div>
    </div>
</div>
