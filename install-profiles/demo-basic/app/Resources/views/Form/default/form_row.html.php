<?php
$isValid = $form->vars['valid'];
?>

<div class="form-group <?= $isValid ? '' : 'has-error' ?>">
    <?= $this->form()->label($form, null, [
        'label_attr' => [
            'class' => 'col-lg-2 control-label'
        ]
    ]) ?>

    <div class="col-lg-10">
        <?= $this->form()->widget($form, [
            'attr' => [
                'class' => 'form-control'
            ]
        ]) ?>

        <?= $this->form()->errors($form) ?>
    </div>
</div>
