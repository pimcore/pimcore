<?php
/**
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $this
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $view
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\GlobalVariables\GlobalVariables $app
 */
?>

<section class="area-video">

    <?= $this->video("video", [
        "attributes" => [
            "class" => "video-js vjs-default-skin vjs-big-play-centered",
            "data-setup" => "{}"
        ],
        "thumbnail" => "content",
        "height" => 380
    ]); ?>

</section>