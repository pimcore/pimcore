
<section>

    <?php if($this->asset) { ?>
        <div class="row-fluid">
            <?php
                $children = $this->asset->getChilds();
                $count = 0;
                $totalCount = count($children);
                foreach ($children as $image) { ?>
                <?php if($count % 4 == 0) { ?><ul class="thumbnails"><?php }
                    $count++;
                    ?>
                    <?php if($image instanceof Asset_Image) { ?>
                        <li class="span3">
                            <a href="<?php echo $image->getThumbnail("galleryLightbox"); ?>" class="thumbnail">
                                <img src="<?php echo $image->getThumbnail("galleryThumbnail"); ?>">
                            </a>
                        </li>
                    <?php } ?>
                <?php if($count >= $totalCount || ($count % 4 == 0)) { ?></ul><?php } ?>
            <?php } ?>
        </div>
    <?php } ?>

</section>