<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables $app
 */
?>

<section class="area-featurette">
    <?php while($this->scheduledblock("block")->loop()) { ?>
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

                <?php } ?>

                <?php
                    $imgConfig = [
                        "class" => "featurette-image img-responsive",
                        "thumbnail" => "featurerette"
                    ];

                    echo $this->image("image", $imgConfig);
                ?>
            </div>
        </div>

    <?php } ?>
</section>
