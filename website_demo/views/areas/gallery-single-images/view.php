<section class="area-gallery-single-images">

    <?php $this->template("/includes/area-headlines.php"); ?>

    <div class="row-fluid">
        <?php
        $block = $this->block("gallery");

        while ($block->loop()) { ?>
            <?php if(($block->getCurrent() % 4 == 0) && !$this->editmode) { ?><ul class="thumbnails"><?php } ?>
                <li class="span3">
                    <a href="<?php echo $this->image("image")->getThumbnail("galleryLightbox"); ?>" class="thumbnail">
                        <?php echo $this->image("image", array(
                            "thumbnail" => "galleryThumbnail",
                            "width" => 140
                        )); ?>
                    </a>
                </li>
            <?php if((($block->getCurrent()-2) >= $block->getCount() || (($block->getCurrent()+1) % 4 == 0)) && !$this->editmode) { ?></ul><?php } ?>
        <?php } ?>
    </div>

</section>

