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

    <?php
    // see comment on objectForm template regarding the form theme
    $this->form()->setTheme($form, ':Form/default');
    ?>

    <?= $this->form()->start($form, [
        'attr' => [
            'class' => 'form-horizontal',
            'role'  => 'form'
        ]
    ]); ?>

    <div class="row">
        <div class="col-md-9">
            <?= $this->form()->row($form['gender']) ?>
            <?= $this->form()->row($form['firstname']) ?>
            <?= $this->form()->row($form['lastname']) ?>
            <?= $this->form()->row($form['email']) ?>
        </div>
        <div class="col-md-3">
            <h4><?= $this->translate("Click one of the following logos to auto-fill the form with your data") ?>.</h4>
            <a href="?provider=Facebook"><img style="max-width: 20%;" src="/static/img/social-icons/facebook.png"></a>
            <a href="?provider=Twitter"><img style="max-width: 20%;" src="/static/img/social-icons/twitter.png"></a>
            <a href="?provider=Google"><img style="max-width: 20%;" src="/static/img/social-icons/google.png"></a>
            <a href="?provider=Google"><img style="max-width: 20%;" src="/static/img/social-icons/youtube.png"></a>
        </div>
    </div>

    <div class="col-lg-12">
        <div class="form-group">
            <h2><?= $form['message']->vars['label'] ?></h2>
            <?= $this->form()->widget($form['message'], [
                'attr'     => [
                    'style' => 'height: 300px',
                    'class' => 'form-control'
                ]
            ]); ?>
        </div>
    </div>

    <br />

    <div class="col-lg-12 form-group">
        <?= $this->form()->widget($form['submit'], [
            'attr' => ['class' => 'btn btn-default']
        ]) ?>
    </div>

    <?= $this->form()->end($form); ?>

<?php } else { ?>

    <h2><?= $this->translate("Thank you very much"); ?></h2>

    <p>
        We received the following information from you:

        <br />
        <br />

        <b>Firstname: </b> <?= $this->escape($this->firstname); ?><br/>
        <b>Lastname: </b> <?= $this->escape($this->lastname); ?><br/>
        <b>E-Mail: </b> <?= $this->escape($this->email); ?><br/>
    </p>

<?php } ?>
