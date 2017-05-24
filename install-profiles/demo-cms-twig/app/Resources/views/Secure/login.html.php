<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables $app
 */

$this->extend('layout.html.php');
?>

<div class="row">
    <div class="col-md-6 col-md-push-3">

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <?= $this->translate('Please log in to continue') ?>
                </h3>
            </div>

            <div class="panel-body">

                <?php if ($this->error): ?>
                    <div class="alert alert-danger"><?php echo $this->error->getMessage() ?></div>
                <?php endif ?>

                <p><?= $this->translate('Depending on the user role and its permissions, the user dropdown in the navbar will show different entries.') ?></p>
                <p><?= $this->translate('Available Users') ?>:</p>

                <ul>
                    <?php foreach ($this->availableUsers as $availableUser): ?>

                        <li>
                            <span class="label label-success"><?= $availableUser['username'] ?></span>
                            with password
                            <span class="label label-default"><?= $availableUser['password'] ?></span>

                            &mdash;

                            <?php foreach ($availableUser['roles'] as $role): ?>
                                <span class="label label-default"><?= $role ?></span>
                            <?php endforeach; ?>
                        </li>

                    <?php endforeach; ?>
                </ul>

                <form action="<?php echo $this->path('demo_login') ?>" method="post">
                    <div class="form-group">
                        <label for="login-username">
                            <?= $this->translate('Username') ?>
                        </label>
                        <input type="text" id="login-username" class="form-control" name="_username" value="<?php echo $this->lastUsername ?>" placeholder="<?= $this->translate('Username:') ?>" />
                    </div>

                    <div class="form-group">
                        <label for="login-password">
                            <?= $this->translate('Password') ?>
                        </label>
                        <input type="password" id="login-password" class="form-control" name="_password" placeholder="<?= $this->translate('Password') ?>" />
                    </div>

                    <input type="hidden" name="_target_path" value="/<?= $this->getLocale() ?>" />

                    <button type="submit" class="btn btn-primary pull-right">Login</button>
                </form>

            </div>
        </div>
    </div>
</div>
