<section class="area-gallery-folder">

    <?php $this->template("/includes/area-headlines.php"); ?>

    <?php echo $this->renderlet("gallery", array(
        "controller" => "content",
        "action" => "gallery-renderlet"
    )); ?>

</section>

