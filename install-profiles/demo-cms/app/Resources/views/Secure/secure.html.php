<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables\GlobalVariables $app
 */

$this->extend('layout.html.php');

/** @var \Symfony\Component\Security\Core\User\User $user */
$user = $this->app->getUser();
?>

<h3><?= $this->admin ? 'Admin' : 'User' ?></h3>
<p>
    <?php if ($this->admin): ?>
        <?= $this->translate("This page can only be seen as logged in admin.") ?>
    <?php else: ?>
        <?= $this->translate("This page can only be seen as logged in user.") ?>
    <?php endif; ?>

    <?= $this->translate("Currently logged in as") ?>
    <strong>
        <span class="glyphicon glyphicon-user"></span>
        <?= $user->getUsername() ?>
    </strong>

    <?= $this->translate('with roles') ?>
    <?= implode(', ', $user->getRoles()); ?>
</p>

<p>
    <a href="<?= $this->path('demo_logout') ?>" class="btn btn-primary">Logout</a>
</p>
