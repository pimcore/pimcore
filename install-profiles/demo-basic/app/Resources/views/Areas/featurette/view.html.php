<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables $app
 */
?>

<section class="area-featurette">
    <?php while($this->block("block")->loop()) { ?>
        <div class="row featurette">

            <?php
                $position = $this->select("position")->getData();
                if(!$position) {
                    $position = "right";
                }
            ?>

            <div class="col-sm-7 col-sm-<?= ($position == "right") ? "push" : ""; ?>-5">
                <h2 class="featurette-heading">
                    <?= $this->input("headline"); ?>
                    <span class="text-muted"><?= $this->input("subline"); ?></span>
                </h2>
                <div class="lead">
                    <?= $this->wysiwyg("content", ["height" => 200]); ?>
                </div>
            </div>

            <div class="col-sm-5 col-sm-<?= ($position == "right") ? "pull" : ""; ?>-7">
                <?php if($this->editmode) { ?>
                    <div class="editmode-label">
                        <label>Orientation:</label>
                        <?= $this->select("position", ["store" => [["left","left"],["right","right"]]]); ?>
                    </div>
                    <div class="editmode-label">
                        <label>Type:</label>
                        <?= $this->select("type", ["reload" => true, "store" => [["video","video"], ["image","image"]]]); ?>
                    </div>
                <?php } ?>

                <?php
                    $type = $this->select("type")->getData();
                    if($type == "video") {
                        echo $this->video("video", [
                            "thumbnail" => "featurerette",
                            "attributes" => [
                                "class" => "video-js vjs-default-skin vjs-big-play-centered",
                                "data-setup" => "{}"
                            ],
                        ]);
                    } else {
                        $imgConfig = [
                            "class" => "featurette-image img-responsive",
                            "thumbnail" => "featurerette"
                        ];

                        echo $this->image("image", $imgConfig);
                    }
                ?>
            </div>
        </div>

        <?php if($this->block("block")->getCurrent() < $this->block("block")->getCount()-1) { ?>
            <hr class="featurette-divider">
        <?php } ?>
    <?php } ?>
</section>
