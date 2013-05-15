

<?php
    $suffix = $this->suffix;
    if(!$suffix) {
        $suffix = "";
    }
?>

<?php echo $this->image("image".$suffix, array(
    "thumbnail" => "standardTeaser",
    "class" => $this->checkbox("circle".$suffix)->isChecked() ? "img-circle" : "",
    "width" => 140,
    "height" => 140
)) ?>

<?php if($this->editmode) { ?>
    <div class="editmode-label">
        <label>Image-Circle:</label>
        <?php echo $this->checkbox("circle".$suffix); ?>
    </div>
<?php } ?>

<h2><?php echo $this->input("headline".$suffix, array("width" => 200)) ?></h2>

<div>
    <?php echo $this->wysiwyg("text".$suffix, array("width" => 200, "height" => 100)); ?>
</div>

<p>
    <?php echo $this->link("link".$suffix, array("class" => "btn")); ?>
</p>

<?php
    // unset the suffix otherwise it will cause problems when using in a loop
    $this->suffix = null;
?>