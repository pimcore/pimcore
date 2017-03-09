<?php
/**
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $this
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $view
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\GlobalVariables\GlobalVariables $app
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
