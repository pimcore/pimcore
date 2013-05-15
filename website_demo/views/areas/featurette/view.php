
<hr class="featurette-divider">

<?php while($this->block("block")->loop()) { ?>
    <div class="featurette">
        <?php if($this->editmode) { ?>
            <div class="featurette-image pull-right" style="width: 400px; height: 260px;">
                <div class="editmode-label">
                    <label>Orientation:</label>
                    <?php echo $this->select("postition", array("store" => array(array("left","left"),array("right","right")))); ?>
                </div>
                <div class="editmode-label">
                    <label>Type:</label>
                    <?php echo $this->select("type", array("reload" => true, "store" => array(array("video","video"),array("image","image")))); ?>
                </div>
        <?php } ?>
            <?php
                $position = $this->select("postition")->getData();
                if(!$position) {
                    $position = "right";
                }

                $type = $this->select("type")->getData();
                if($type == "video") {
                    echo '<div class="video featurette-image pull-' . $position . '">';
                    echo $this->video("video", array(
                        "html5" => true,
                        "thumbnail" => "featurerette",
                        "width" => $this->editmode ? 300 : 512
                    ));
                    echo '</div>';
                } else {
                    echo $this->image("image", array(
                        "class" => "featurette-image pull-".$position,
                        "thumbnail" => "featurerette"
                    ));
                }
            ?>

        <?php if($this->editmode) { ?></div><?php } ?>

        <h2 class="featurette-heading">
            <?php echo $this->input("headline", array("width" => 250)); ?>
            <span class="muted"><?php echo $this->input("subline", array("width" => 250)); ?></span>
        </h2>

        <div class="lead">
            <?php echo $this->wysiwyg("content", array("width" => 250, "height" => 200)); ?>
        </div>
    </div>

    <hr class="featurette-divider">
<?php } ?>
