<?php
/**
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $this
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $view
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\GlobalVariables\GlobalVariables $app
 */
?>

<section class="area-gallery-folder">

    <?= $this->renderlet("gallery", [
        "controller" => "content",
        "action" => "gallery-renderlet"
    ]); ?>

</section>

