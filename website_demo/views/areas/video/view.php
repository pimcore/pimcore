
<section class="area-video">

    <?php $this->template("/includes/area-headlines.php"); ?>

    <?= $this->video("video", [
        "html5" => true,
        "thumbnail" => "content",
        "height" => 380
    ]); ?>

</section>