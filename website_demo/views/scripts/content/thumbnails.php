
<?php $this->template("/includes/content-headline.php"); ?>


<?php echo $this->areablock("content"); ?>

<?php
    // this is just used for demonstration
    $image = Asset::getById(53);
?>

<h2>
    <?php echo $this->translate("Original Dimensions of the Image"); ?>:
    <?php
        echo $image->getWidth() . "x" . $image->getHeight();
    ?>
</h2>

<section class="thumbnail-examples">
    <?php
        $thumbnails = array(
            "Cover" => "exampleCover",
            "Contain" => "exampleContain",
            "Frame" => "exampleFrame",
            "Rotate" => "exampleRotate",
            "Resize" => "exampleResize",
            "Scale by Width" => "exampleScaleWidth",
            "Scale by Height" => "exampleScaleHeight",
            "Contain &amp; Overlay" => "exampleOverlay",
            "Rounded Corners" => "exampleCorners",
            "Sepia" => "exampleSepia",
            "Grayscale" => "exampleGrayscale",
            "Mask" => "exampleMask",
            "Combined 1" => "exampleCombined1",
            "Combined 2" => "exampleCombined2",
        );
    ?>

    <?php
        $i=0;
        foreach($thumbnails as $title => $name) { ?>
        <?php if($i % 3 === 0) { ?><div class="row"><?php } ?>
            <div class="col-lg-4">
                <?php
                    $thumbnail = $image->getThumbnail($name);
                ?>
                <div class="img-container">
                    <img src="<?php echo $thumbnail; ?>">
                </div>
                <h3><?php echo $this->translate($title); ?></h3>
                <div>
                    <?php echo $this->translate("Dimensions"); ?>:
                    <?php
                        echo $thumbnail->getWidth() . "x" . $thumbnail->getHeight()
                    ?>
                </div>
            </div>
        <?php $i++; if($i % 3 === 0 || $i >= count($thumbnails)) { ?></div><?php } ?>
    <?php } ?>
</section>



<?php echo $this->areablock("content_bottom"); ?>