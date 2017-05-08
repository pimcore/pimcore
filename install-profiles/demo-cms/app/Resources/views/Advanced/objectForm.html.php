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


<?php if ((!$this->error && !$this->success) || $this->editmode) { ?>
    <?= $this->template('Includes/content-default.html.php') ?>
<?php } ?>

<?php if ($this->error || $this->editmode) { ?>
    <br/>
    <div class="alert alert-error">
        <?= $this->input("errorMessage"); ?>
    </div>
<?php } ?>

<?php if (!$this->success) { ?>

    <?php
    // We created a custom form theme, overriding the form group template with bootstrap markup in app/Resources/view/Form/default
    // In the form theme, we just can override what we want to change as non-existing templates will fall back to the default
    // implementation.
    //
    // To customize a template, just add the corresponding template to the folder above. The original templates can be
    // found in vendor/symfony/symfony/src/Symfony/Bundle/FrameworkBundle/Resources/views/Form
    //
    // see:
    // * http://symfony.com/doc/current/form/form_customization.html and
    // * http://symfony.com/doc/current/form/rendering.html
    // * http://symfony.com/doc/current/form/form_themes.html
    $this->form()->setTheme($form, ':Form/default');
    ?>

    <?= $this->form()->start($form, [
        'attr' => [
            'class' => 'form-horizontal',
            'role'  => 'form'
        ]
    ]); ?>

    <?php // call row() for normal inputs ?>
    <?= $this->form()->row($form['gender']) ?>
    <?= $this->form()->row($form['firstname']) ?>
    <?= $this->form()->row($form['lastname']) ?>
    <?= $this->form()->row($form['email']) ?>
    <?= $this->form()->row($form['message']) ?>

    <?php // render checkbox manually as we need custom markup ?>
    <div class="form-group">
        <div class="col-lg-offset-2 col-lg-10">
            <div class="checkbox">
                <label>
                    <?= $this->form()->widget($form['terms']) ?> <?= $form['terms']->vars['label'] ?>
                </label>
            </div>
        </div>
    </div>

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

    <h2><?= $this->translate("Thank you very much"); ?></h2>
    <p>
        We received the following information from you:

        <br/>
        <br/>

        <b>Firstname: </b> <?= $this->escape($this->firstname); ?><br/>
        <b>Lastname: </b> <?= $this->escape($this->lastname); ?><br/>
        <b>E-Mail: </b> <?= $this->escape($this->email); ?><br/>
    </p>

<?php } ?>
