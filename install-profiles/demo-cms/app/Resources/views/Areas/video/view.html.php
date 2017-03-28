<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables\GlobalVariables $app
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
