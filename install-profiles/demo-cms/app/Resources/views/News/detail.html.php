<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables\GlobalVariables $app
 */

$this->extend('layout.html.php');

?>

<?php
// set page meta-data
$this->headTitle()->set($this->news->getTitle());
$this->headMeta()->setDescription($this->news->getShortText(), 160);

/** @var \Pimcore\Model\Object\News $news */
$news = $this->news;
?>

<section class="area-wysiwyg">

    <div class="page-header">
        <h1><?= $news->getTitle(); ?></h1>
    </div>

    <div class="lead">
        <p><?= $news->getShortText(); ?></p>
    </div>

    <?= $news->getText(); ?>

    <div class="row">
        <?php for ($i = 1; $i <= 3; $i++) { ?>

            <?php
            /** @var \Pimcore\Model\Document\Tag\Image $image */
            $image = $news->{"getImage_" . $i}();
            ?>

            <?php if ($image) { ?>
                <div class="col-lg-3">
                    <a href="<?= $image->getThumbnail("galleryLightbox"); ?>" class="thumbnail">
                        <?= $image->getThumbnail("galleryThumbnail")->getHTML(); ?>
                    </a>
                </div>
            <?php } ?>

        <?php } ?>
    </div>

</section>
