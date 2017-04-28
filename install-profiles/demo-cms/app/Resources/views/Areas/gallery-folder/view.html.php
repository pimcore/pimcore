<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables $app
 */
?>

<section class="area-gallery-folder">

    <?= $this->renderlet("gallery", [
        "controller" => "content",
        "action" => "gallery-renderlet"
    ]); ?>

</section>

