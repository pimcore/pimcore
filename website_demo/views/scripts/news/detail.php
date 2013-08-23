<section class="area-wysiwyg">

    <div class="page-header">
        <h1><?php echo $this->news->getTitle(); ?></h1>
    </div>

    <div class="lead">
        <p><?php echo $this->news->getShortText(); ?></p>
    </div>

    <?php echo $this->news->getText(); ?>


    <div class="row">
        <?php for($i=1; $i<=3; $i++) { ?>
            <?php
                $image = $this->news->{"getImage_" . $i}();
            ?>
            <?php if($image) { ?>
                <div class="col-lg-3">
                    <a href="<?php echo $image->getThumbnail("galleryLightbox"); ?>" class="thumbnail">
                        <img src="<?php echo $image->getThumbnail("galleryThumbnail"); ?>">
                    </a>
                </div>
            <?php } ?>
        <?php } ?>
    </div>

</section>