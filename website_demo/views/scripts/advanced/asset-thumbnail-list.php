
<?php $this->template("/content/default.php"); ?>

<?php if($this->editmode) { ?>
    <div class="alert alert-info">
        Specify the parent folder here (default is home)
        <?= $this->href("parentFolder") ?>
    </div>
<?php } ?>


<div class="row">
    <?php foreach ($this->list as $asset) { ?>
        <?php if(in_array($asset->getType(), ["video", "image", "document"])) { ?>
            <div class="col-xs-6 col-md-3" style="padding-bottom: 10px">
                <?php if($asset instanceof Asset_Image) { ?>
                    <?= $asset->getThumbnail(array(
                        "width" => 180,
                        "height" => 180,
                        "cover" => true
                    ))->getHTML(array("class" => "thumbnail")) ?>
                <?php } else { ?>
                    <img src="<?= $asset->getImageThumbnail(array(
                        "width" => 180,
                        "height" => 180,
                        "cover" => true
                    )) ?>" class="thumbnail" width="180" height="180">
                <?php } ?>
            </div>
        <?php } ?>
    <?php } ?>
</div>




