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

        <?php if ($user): ?>

            <?php
            /*
             * Show secure/user and secure/admin links depending on user role. ROLE_ADMIN inherits from ROLE_USER (see
             * role_hierarchy in security.yml, so the secure_user link is shown for all logged in users.
             */
            if ($this->security()->isGranted('ROLE_USER')): ?>
                <li>
                    <a href="<?= $this->path('demo_secure_user') ?>"><?= $this->translate('Secure User Page') ?></a>
                </li>
            <?php endif; ?>

            <?php if ($this->security()->isGranted('ROLE_ADMIN')): ?>
                <li>
                    <a href="<?= $this->path('demo_secure_admin') ?>"><?= $this->translate('Secure Admin Page') ?></a>
                </li>
            <?php endif; ?>

            <li role="separator" class="divider"></li>
        <?php endif; ?>

        <li>
            <?php if ($user): ?>

                <a href="<?= $this->path('demo_logout') ?>">Logout</a>

            <?php else: ?>

                <a href="<?= $this->path('demo_login') ?>">Login</a>

            <?php endif; ?>
        </li>
    </ul>
</li>
