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

<?php if ($user): ?>
    <div class="alert alert-success">
        Logged in as User <strong><?= $user->getUsername() ?></strong>. <a href="<?= $this->path('demo_logout') ?>">Log out.</a>
    </div>
<?php else: ?>
    <div class="alert alert-danger">
        Not logged in. <a href="<?= $this->path('demo_login') ?>">Log in.</a>
    </div>
<?php endif; ?>
