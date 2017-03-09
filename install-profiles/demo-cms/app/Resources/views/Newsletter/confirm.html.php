<?php
/**
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $this
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $view
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\GlobalVariables\GlobalVariables $app
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
