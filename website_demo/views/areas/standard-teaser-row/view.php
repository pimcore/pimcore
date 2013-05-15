
<section class="area-standard-teaser-row">
    <div class="row-fluid">
        <?php for($t=0; $t<3; $t++) { ?>
            <div class="span4">
                <?php if($this->editmode) { ?>
                    <div class="editmode-label">
                        <label>Type:</label>
                        <?php echo $this->select("type_".$t, array(
                            "width" => 80,
                            "reload" => true,
                            "store" => array(array("direct","direct"),array("snippet","snippet"))
                        )); ?>
                    </div>
                <?php } ?>
                <?php
                    $type = $this->select("type_".$t)->getData();
                    if($type == "direct") {
                        $this->template("/snippets/standard-teaser.php", array("suffix" => $t+1));
                    } else {
                        echo $this->snippet("teaser_".$t);
                    }
                ?>
            </div>
        <?php } ?>
    </div>
</section> 
