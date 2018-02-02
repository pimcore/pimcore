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

<?= $this->template('Includes/content-default.html.php') ?>

<?php if(!$this->success) { ?>
    <?php if ($this->submitted) { ?>
        <div class="alert alert-danger">
            <?= $this->translate("Sorry, something went wrong, please check the data in the form and try again!"); ?>
        </div>
        <br />
        <br />
    <?php } ?>

    <?php $this->form()->setTheme($form, ':Form/default'); ?>

    <?= $this->form()->start($form, [
        'attr' => [
            'class' => 'form-horizontal',
            'role'  => 'form'
        ]
    ]); ?>

    <?= $this->form()->row($form['gender']) ?>
    <?= $this->form()->row($form['firstname']) ?>
    <?= $this->form()->row($form['lastname']) ?>
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
    <div class="alert alert-success"><?= $this->translate("Success, Please check your mailbox!"); ?></div>
<?php } ?>
