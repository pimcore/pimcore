
<hr class="featurette-divider">

<?php while($this->block("block")->loop()) { ?>
    <div class="row featurette">

        <?php
            $position = $this->select("postition")->getData();
            if(!$position) {
                $position = "right";
            }
        ?>

        <div class="col-md-7 pull-<?= ($position == "right") ? "left" : "right"; ?>">
            <h2 class="featurette-heading">
                <?= $this->input("headline", array("width" => 400)); ?>
                <span class="text-muted"><?= $this->input("subline", array("width" => 400)); ?></span>
            </h2>
            <div class="lead">
                <?= $this->wysiwyg("content", array("width" => 350, "height" => 200)); ?>
            </div>
        </div>

        <div class="col-md-5 pull-<?= $position; ?>">
            <?php if($this->editmode) { ?>
                <div class="editmode-label">
                    <label>Orientation:</label>
                    <?= $this->select("postition", array("store" => array(array("left","left"),array("right","right")))); ?>
                </div>
                <div class="editmode-label">
                    <label>Type:</label>
                    <?= $this->select("type", array("reload" => true, "store" => array(array("video","video"),array("image","image")))); ?>
                </div>
            <?php } ?>

            <?php
                $type = $this->select("type")->getData();
                if($type == "video") {
                    echo $this->video("video", array(
                        "html5" => true,
                        "thumbnail" => "featurerette"
                    ));
                } else {
                    $imgConfig = array(
                        "class" => "featurette-image img-responsive",
                        "thumbnail" => "featurerette"
                    );

                    if($this->editmode) {
                        $imgConfig["width"] = 300;
                    }

                    echo $this->image("image", $imgConfig);
                }
            ?>
        </div>
    </div>

    <hr class="featurette-divider">
<?php } ?>
