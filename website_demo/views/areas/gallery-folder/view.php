<section class="area-gallery-folder">

    <?php $this->template("/includes/area-headlines.php"); ?>

    <?= $this->renderlet("gallery", array(
        "controller" => "content",
        "action" => "gallery-renderlet"
    )); ?>

</section>

