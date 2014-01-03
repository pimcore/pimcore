
<hr class="featurette-divider">

<?php while($this->block("block")->loop()) { ?>
    <div class="row featurette">

        <?php
            $position = $this->select("postition")->getData();
            if(!$position) {
                $position = "right";
            }
        ?>

        <div class="col-sm-7 col-sm-<?= ($position == "right") ? "push" : ""; ?>-5">
            <h2 class="featurette-heading">
                <?= $this->input("headline", ["width" => 400]); ?>
                <span class="text-muted"><?= $this->input("subline", ["width" => 400]); ?></span>
            </h2>
            <div class="lead">
                <?= $this->wysiwyg("content", ["width" => 350, "height" => 200]); ?>
            </div>
        </div>

        <div class="col-sm-5 col-sm-<?= ($position == "right") ? "pull" : ""; ?>-7">
            <?php if($this->editmode) { ?>
                <div class="editmode-label">
                    <label>Orientation:</label>
                    <?= $this->select("postition", ["store" => [["left","left"],["right","right"]]]); ?>
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
                        "html5" => true,
                        "thumbnail" => "featurerette"
                    ]);
                } else {
                    $imgConfig = [
                        "class" => "featurette-image img-responsive",
                        "thumbnail" => "featurerette"
                    ];

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
