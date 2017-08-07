<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables $app
 */
?>

<section class="area-gallery-single-images">

    <div class="row">
        <?php
        $block = $this->block("gallery");

        while ($block->loop()) { ?>
            <div class="col-md-3 col-xs-6">
                <?php if(!$this->editmode) { ?>
                    <a href="<?= $this->image("image")->getThumbnail("galleryLightbox"); ?>" class="thumbnail">
                <?php } ?>

                    <?= $this->image("image", [
                        "thumbnail" => "galleryThumbnail"
                    ]); ?>

                <?php if(!$this->editmode) { ?>
                    </a>
                <?php } ?>
            </div>
        <?php } ?>
    </div>

</section>

