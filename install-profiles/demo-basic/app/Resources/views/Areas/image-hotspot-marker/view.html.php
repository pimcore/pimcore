<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables $app
 */
?>

<section class="area-image">

    <div style="position: relative;">
        <?= $this->image("image", [
            "thumbnail" => "content"
        ]) ?>

        <?php if(!$this->editmode) { ?>
            <?php // markers ?>
            <?php foreach ($this->image("image")->getMarker() as $marker) { ?>
                <?php
                    $title = "";
                    foreach($marker["data"] as $d) {
                        if($d["name"] == "title") {
                            $title = $d["value"];
                        }
                    }
                ?>
                <div class="image-marker"
                     style="top:<?=$marker["top"] ?>%; left:<?=$marker["left"] ?>%;"
                     data-toggle="tooltip"
                     title="<?= $title; ?>"
                    ></div>
            <?php } ?>
        <?php } ?>

        <?php if(!$this->editmode) { ?>
            <?php // hotspots ?>
            <?php foreach ($this->image("image")->getHotspots() as $hotspot) { ?>
                <?php
                    $title = "";
                    foreach($hotspot["data"] as $d) {
                        if($d["name"] == "title") {
                            $title = $d["value"];
                        }
                    }
                ?>
                <div  class="image-hotspot"
                      style="width: <?=$hotspot["width"] ?>%; height: <?=$hotspot["height"] ?>%; top:<?=$hotspot["top"] ?>%; left:<?=$hotspot["left"] ?>%;"
                      data-toggle="tooltip"
                      title="<?= $title; ?>"
                    ></div>
            <?php } ?>
        <?php } ?>
    </div>
</section>
