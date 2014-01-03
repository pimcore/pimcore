<section class="area-gallery-folder">

    <?php $this->template("/includes/area-headlines.php"); ?>

    <?= $this->renderlet("gallery", [
        "controller" => "content",
        "action" => "gallery-renderlet"
    ]); ?>

</section>

