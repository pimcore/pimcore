<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables $app
 */
?>

<?php
    $suffix = $this->suffix;
    if(!$suffix) {
        $suffix = "";
    }
?>

<?php if(empty($suffix) && $this->editmode) { ?>
    <style type="text/css">
        .teaser {
            max-width: 250px;
        }

        .teaser img {
            max-width: 100%;
        }
    </style>
<?php } ?>

<div class="teaser">

    <?= $this->image("image".$suffix, [
        "thumbnail" => "standardTeaser",
        "class" => $this->checkbox("circle".$suffix)->isChecked() ? "img-circle" : ""
    ]) ?>

    <?php if($this->editmode) { ?>
        <div class="editmode-label">
            <label>Image-Circle:</label>
            <?= $this->checkbox("circle".$suffix); ?>
        </div>
    <?php } ?>

    <h2><?= $this->input("headline".$suffix) ?></h2>

    <div>
        <?= $this->wysiwyg("text".$suffix, ["height" => 100]); ?>
    </div>

    <p>
        <?= $this->link("link".$suffix, ["class" => "btn btn-default"]); ?>
    </p>

    <?php
    // unset the suffix otherwise it will cause problems when using in a loop
    $this->suffix = null;
    ?>

</div>
