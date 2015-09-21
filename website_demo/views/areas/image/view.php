<section class="area-image">

    <a href="<?= $this->image("image")->getThumbnail("galleryLightbox"); ?>" class="thumbnail">
        <?= $this->image("image", [
            "thumbnail" => "content"
        ]); ?>
    </a>

</section>