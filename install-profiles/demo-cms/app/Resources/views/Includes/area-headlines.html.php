<?php
/**
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $this
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $view
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\GlobalVariables\GlobalVariables $app
 */
?>

<?php if ($this->editmode || !$this->input("headline")->isEmpty()) { ?>
    <div class="page-header">
        <h2><?= $this->input("headline"); ?></h2>
    </div>
<?php } ?>

<?php if ($this->editmode || !$this->wysiwyg("lead")->isEmpty()) { ?>
    <div class="lead">
        <?= $this->wysiwyg("lead", ["height" => 100]); ?>
    </div>
<?php } ?>

