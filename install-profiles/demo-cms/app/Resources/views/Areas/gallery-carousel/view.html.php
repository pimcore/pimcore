<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables\GlobalVariables $app
 */
?>

<section class="area-gallery-carousel">

    <?php if($this->editmode) { ?>
        <div class="alert alert-info" style="height: 75px">
            <div class="col-xs-6">
                How many images you want to show?

                <?php
                    // prepare the store
                    $selectStore = [];
                    for($i=2; $i<30; $i++) {
                        $selectStore[] = [$i, $i];
                    }
                ?>
                <?= $this->select("slides",[
                    "store" => $selectStore,
                    "reload" => true,
                    "width" => 70
                ]); ?>
            </div>
            <div class="col-xs-6">
                Show Previews
                <?= $this->checkbox("showPreviews") ?>
            </div>
        </div>

        <style type="text/css">
            .gallery .item {
                min-height: 200px;
            }
        </style>
    <?php } ?>

    <?php
        $id = "gallery-carousel-" . uniqid();
        $slides = 2; // default value
        if(!$this->select("slides")->isEmpty()){
            $slides = (int) $this->select("slides")->getData();
        }
    ?>
    <div id="<?= $id ?>" class="gallery carousel slide">
        <!-- Indicators -->
        <?php $showPreview = $this->checkbox("showPreviews")->isChecked() && !$this->editmode; ?>
        <ol class="carousel-indicators <?= $showPreview ? "preview visible-lg" : "" ?>">
            <?php for($i=0; $i<$slides; $i++) { ?>
                <li data-target="#<?= $id ?>" data-slide-to="<?= $i ?>" class="<?= ($i==0 ? "active" : "") ?>">
                    <?php if($showPreview) { ?>
                        <?= $this->image("image_" . $i, [
                            "thumbnail" => "galleryCarouselPreview"
                        ]) ?>
                    <?php } ?>
                </li>
            <?php } ?>
        </ol>

        <div class="carousel-inner">
            <?php for($i=0; $i<$slides; $i++) { ?>
                <div class="item <?= ($i==0 ? "active" : "") ?> <?= $id . "-" . $i ?>">
                    <?= $this->image("image_" . $i, [
                        "thumbnail" => "galleryCarousel",
                        "dropClass" => $id . "-" . $i,
                        "defaultHeight" => 200
                    ]) ?>
                    <div class="carousel-caption">
                        <?php if($this->editmode || !$this->input("caption-title-" . $i)->isEmpty()) { ?>
                            <h3><?= $this->input("caption-title-" . $i, ["width" => 400]) ?></h3>
                        <?php } ?>
                        <?php if($this->editmode || !$this->textarea("caption-text-" . $i)->isEmpty()) { ?>
                            <p>
                                <?= $this->textarea("caption-text-" . $i, ["width" => 400]) ?>
                            </p>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>

        <a class="left carousel-control" href="#<?= $id ?>" data-slide="prev">
            <span class="glyphicon glyphicon-chevron-left"></span>
        </a>
        <a class="right carousel-control" href="#<?= $id ?>" data-slide="next">
            <span class="glyphicon glyphicon-chevron-right"></span>
        </a>
    </div>

</section>

