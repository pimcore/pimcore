<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables\GlobalVariables $app
 */

use Pimcore\Model\Asset\Document;
use Pimcore\Model\Asset\Video;

$this->extend('layout.html.php');

?>

<?= $this->template('Includes/content-default.html.php') ?>

<?php if($this->editmode) { ?>
    <div class="alert alert-info">
        Specify the parent folder here (default is home)
        <?= $this->href("parentFolder") ?>
    </div>
<?php } ?>


<div class="row">
    <?php foreach ($this->list as $asset) { ?>
        <?php
        /** @var Asset|Video|Document $asset */
        ?>
        <?php if(in_array($asset->getType(), ["video", "image", "document"])) { ?>
            <div class="col-xs-3" style="padding-bottom: 10px">
                <?php if($asset instanceof \Pimcore\Model\Asset\Image) { ?>
                    <?= $asset->getThumbnail([
                        "width" => 180,
                        "height" => 180,
                        "cover" => true
                    ])->getHTML(["class" => "thumbnail"]) ?>
                <?php } else { ?>
                    <img src="<?= $asset->getImageThumbnail([
                        "width" => 180,
                        "height" => 180,
                        "cover" => true
                    ]) ?>" class="thumbnail" width="180" height="180">
                <?php } ?>
            </div>
        <?php } ?>
    <?php } ?>
</div>




