<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables $app
 */

$this->extend('layout.html.php');

?>

<?= $this->template('Includes/content-default.html.php') ?>

<?php if(!$this->success) { ?>

    <?php if ($this->unsubscribeMethod) { ?>
        <div class="alert alert-danger">
            <?php if ($this->unsubscribeMethod == "email") { ?>
                Sorry, we don't have your address in our database.
            <?php } else { ?>
                Sorry, your unsubscribe token is invalid, try to remove your address manually:
            <?php } ?>
        </div>
    <?php } ?>

    <?php $this->form()->setTheme($form, ':Form/default'); ?>

    <?= $this->form()->start($form, [
        'attr' => [
            'class' => 'form-horizontal',
            'role'  => 'form'
        ]
    ]); ?>

    <?= $this->form()->row($form['email']) ?>

    <br/>

    <div class="form-group">
        <div class="col-lg-offset-2 col-lg-10">
            <?= $this->form()->widget($form['submit'], [
                'attr' => ['class' => 'btn btn-default']
            ]) ?>
        </div>
    </div>

    <?= $this->form()->end($form); ?>
<?php } else { ?>
    <div class="alert alert-success">
        <h2>Unsubscribed</h2>
    </div>
<?php } ?>
