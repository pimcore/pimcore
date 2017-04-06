<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables\GlobalVariables $app
 */

/** @var \Symfony\Component\Security\Core\User\User $user */
$user = $this->app->getUser();
?>

<li class="dropdown">
    <a href="#" class="dropdown-toggle" id="loginSelector" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="true">

        <?php if ($user): ?>

            <span class="glyphicon glyphicon-user"></span> <strong><?= $user->getUsername() ?></strong>

        <?php else: ?>

            <?= $this->translate('Not logged in') ?>

        <?php endif; ?>

        <span class="caret"></span>
    </a>
    <ul class="dropdown-menu navbar-login" aria-labelledby="loginSelector">
        <li>
            <?php if ($user): ?>

                <a href="<?= $this->path('demo_logout') ?>">Logout</a>

            <?php else: ?>

                <a href="<?= $this->path('demo_login') ?>">Login</a>

            <?php endif; ?>
        </li>
    </ul>
</li>
