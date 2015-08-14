<section class="area-gallery-single-images">

    <div class="row">
        <?php
        $block = $this->block("gallery");

        while ($block->loop()) { ?>
            <div class="col-md-3 col-xs-6">
                <a href="<?= $this->image("image")->getThumbnail("galleryLightbox"); ?>" class="thumbnail">
                    <?= $this->image("image", [
                        "thumbnail" => "galleryThumbnail"
                    ]); ?>
                </a>
            </div>
        <?php } ?>
    </div>

</section>

