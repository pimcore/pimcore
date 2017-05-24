<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables $app
 */
?>


<?php if(!$this->success) { ?>
    <div class="alert alert-danger">
        <h2><?= $this->translate("Sorry, something went wrong, please sign up again!"); ?></h2>
    </div>
<?php } else { ?>
    <div class="alert alert-success">
        <h2><?= $this->translate("Thanks for confirming your address!"); ?></h2>
    </div>
<?php } ?>
