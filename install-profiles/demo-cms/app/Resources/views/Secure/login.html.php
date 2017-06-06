<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables $app
 */

$this->extend('layout.html.php');

/** @var \Symfony\Component\Form\FormView $form */
$form = $this->form;
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

                <?php
                $this->form()->setTheme($form, ':Form/login');
                ?>

                <?= $this->form()->start($form); ?>
                <?= $this->form()->row($form['_username']) ?>
                <?= $this->form()->row($form['_password']) ?>
                <?= $this->form()->widget($form['_target_path']) ?>
                <?= $this->form()->widget($form['_submit'], [
                    'attr' => [
                        'class' => 'btn btn-primary pull-right'
                    ]
                ]) ?>
                <?= $this->form()->end($form); ?>
            </div>
        </div>
    </div>
</div>
