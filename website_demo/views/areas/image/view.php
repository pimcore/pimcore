<section class="area-image">

    <?php $this->template("/includes/area-headlines.php"); ?>

    <a href="<?php echo $this->image("image")->getThumbnail("galleryLightbox"); ?>" class="thumbnail">
        <?php echo $this->image("image", array(
            "thumbnail" => "content"
        )); ?>
    </a>

</section>