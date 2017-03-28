<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables\GlobalVariables $app
 */
?>

<?php foreach($this->allChildren as $child) { ?>

    <?php
        if($child instanceof \Pimcore\Model\Document\Hardlink) {
            $child = \Pimcore\Model\Document\Hardlink\Service::wrap($child);
        }
    ?>

    <?= $this->inc($child) ?>
<?php } ?>
