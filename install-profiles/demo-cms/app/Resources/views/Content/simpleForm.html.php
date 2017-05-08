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

<?php if (!$this->success): ?>

    <?php
    // see comment on advanced/objectForm template regarding the form theme
    $this->form()->setTheme($form, ':Form/default');
    ?>

    <?= $this->form()->start($form, [
        'attr' => [
            'class' => 'form-horizontal',
            'role'  => 'form'
        ]
    ]); ?>

    <?= $this->form()->row($form['firstname']) ?>
    <?= $this->form()->row($form['lastname']) ?>
    <?= $this->form()->row($form['email']) ?>

    <?php // render checkbox manually as we need custom markup ?>
    <div class="form-group">
        <div class="col-lg-offset-2 col-lg-10">
            <div class="checkbox">
                <label>
                    <?= $this->form()->widget($form['checkbox']) ?> <?= $form['checkbox']->vars['label'] ?>
                </label>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="col-lg-offset-2 col-lg-10">
            <?= $this->form()->widget($form['submit'], [
                'attr' => ['class' => 'btn btn-default']
            ]) ?>
        </div>
    </div>

    <?= $this->form()->end($form) ?>

<?php else: ?>

    <h2><?= $this->translate('Thank you very much'); ?></h2>

    <p>
        We received the following information from you:

        <br/>
        <br/>

        <b>Firstname: </b> <?= $this->escape($this->firstname); ?><br/>
        <b>Lastname: </b> <?= $this->escape($this->lastname); ?><br/>
        <b>E-Mail: </b> <?= $this->escape($this->email); ?><br/>
    </p>
<?php endif; ?>
