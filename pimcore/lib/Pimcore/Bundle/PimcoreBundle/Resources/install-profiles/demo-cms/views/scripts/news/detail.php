<?php
    // set page meta-data
    $this->headTitle()->set($this->news->getTitle());
    $this->headMeta($this->news->getShortText(), "description");
?>
<section class="area-wysiwyg">

    <div class="page-header">
        <h1><?= $this->news->getTitle(); ?></h1>
    </div>

    <div class="lead">
        <p><?= $this->news->getShortText(); ?></p>
    </div>

    <?= $this->news->getText(); ?>


    <div class="row">
        <?php for($i=1; $i<=3; $i++) { ?>
            <?php
                $image = $this->news->{"getImage_" . $i}();
            ?>
            <?php if($image) { ?>
                <div class="col-lg-3">
                    <a href="<?= $image->getThumbnail("galleryLightbox"); ?>" class="thumbnail">
                        <?= $image->getThumbnail("galleryThumbnail")->getHTML(); ?>
                    </a>
                </div>
            <?php } ?>
        <?php } ?>
    </div>

</section>