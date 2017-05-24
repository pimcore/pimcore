<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables $app
 */

$this->extend('layout.html.php');

/** @var \Symfony\Component\Security\Core\User\User $user */
$user = $this->app->getUser();
?>

<h3><?= $this->admin ? 'Admin' : 'User' ?></h3>

<?php if ($this->admin): ?>
    <p><?= $this->translate("This page can only be seen as admin user.") ?></p>
<?php else: ?>
    <p><?= $this->translate("This page can only be seen as logged in user.") ?></p>
<?php endif; ?>

<p class="alert alert-success">
    <?= $this->translate("Currently logged in as") ?>
    <strong>
        <span class="glyphicon glyphicon-user"></span>
        <?= $user->getUsername() ?>
    </strong>

    <?= $this->translate('with roles') ?>
    <strong><?= implode(', ', $user->getRoles()); ?></strong>.
</p>

<p>
    <a href="<?= $this->path('demo_logout') ?>" class="btn btn-primary">
        <?= $this->translate('Logout') ?>
    </a>
</p>
