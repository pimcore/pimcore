<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables $app
 */
?>

<section class="area-tabbed-slider-text">

    <?php if($this->editmode) { ?>
        <div class="alert alert-info">
            How many tabs you want to show?

            <?php
                // prepare the store
                $selectStore = [];
                for($i=2; $i<6; $i++) {
                    $selectStore[] = [$i, $i];
                }
            ?>
            <?= $this->select("slides",[
                "store" => $selectStore,
                "reload" => true,
                "width" => 70
            ]); ?>
        </div>
    <?php } ?>

    <?php
        $id = "tabbed-slider-" . uniqid();
        $slides = 2; // default value
        if(!$this->select("slides")->isEmpty()){
            $slides = (int) $this->select("slides")->getData();
        }
    ?>
    <div id="<?= $id ?>" class="tabbed-slider carousel slide">
        <div class="carousel-inner">
            <?php for($i=0; $i<$slides; $i++) { ?>
                <div class="item <?= ($i==0 ? "active" : "") ?> item-<?= $i ?> <?= $id . "-" . $i ?>">
                    <?php if(!$this->image("image_" . $i)->isEmpty() || $this->editmode) { ?>
                        <?= $this->image("image_" . $i, [
                            "dropClass" => $id . "-" . $i,
                            "thumbnail" => "portalCarousel"
                        ]); ?>
                    <?php } ?>
                    <div class="carousel-caption">
                        <h1><?= $this->input("headline_" . $i) ?></h1>
                        <p><?= $this->textarea("description_" . $i, ["nl2br" => true]) ?></p>
                    </div>
                </div>
            <?php } ?>
        </div>
        <!-- End Carousel Inner -->
        <ul class="nav nav-pills nav-justified">
            <?php for($i=0; $i<$slides; $i++) { ?>
                <li data-target="#<?= $id ?>" data-slide-to="<?= $i ?>" class="<?= ($i==0 ? "active" : "") ?> item-<?= $i ?>">
                    <a href="#">
                        <?= $this->input("pill-title_" . $i) ?>
                        <small><?= $this->input("pill-small_" . $i) ?></small>
                    </a></li>
            <?php } ?>
        </ul>
    </div>

</section>

