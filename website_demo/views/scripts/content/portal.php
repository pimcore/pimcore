<?php $this->layout(); // enable the layout engine, it's not necessary to call ->setLayout() if we want to use the default layout ("layout.php") ?>

<div id="myCarousel" class="carousel header slide" data-ride="carousel">
    <!-- Indicators -->
    <ol class="carousel-indicators">
        <li data-target="#myCarousel" data-slide-to="0" class="active"></li>
        <li data-target="#myCarousel" data-slide-to="1"></li>
        <li data-target="#myCarousel" data-slide-to="2"></li>
    </ol>
    <div class="carousel-inner">
        <?php
        $count = $this->select("carouselSlides")->getData();
        if(!$count) {
            $count = 1;
        }
        for($i=0; $i<$count; $i++) { ?>
            <div class="item<?php if(!$i) { ?> active<?php } ?>">
                <?= $this->image("cImage_".$i)->frontend(); ?>
                <div class="container">
                    <div class="carousel-caption">
                        <?php
                        if($this->editmode) {
                            echo $this->image("cImage_".$i, [
                                "reload" => true,
                                "hidetext" => true,
                                "title" => "Drag Image Here",
                                "height" => 30
                            ]);
                            echo "<br /><br />";
                        }
                        ?>

                        <h1><?= $this->input("cHeadline_".$i, ["width" => 900]); ?></h1>
                        <div class="caption"><?= $this->textarea("cText_".$i, ["width" => 900]); ?></div>
                        <div class="margin-bottom-10"><?= $this->link("cLink_".$i, ["class" => "btn btn-large btn-default"]); ?></div>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
    <a class="left carousel-control" href="#myCarousel" data-slide="prev"><span class="glyphicon glyphicon-chevron-left"></span></a>
    <a class="right carousel-control" href="#myCarousel" data-slide="next"><span class="glyphicon glyphicon-chevron-right"></span></a>
</div>

<?php if($this->editmode) { ?>
    <div class="container" style="padding-bottom: 40px">
        Number of Slides: <?= $this->select("carouselSlides", [
            "width" => 60,
            "reload" => true,
            "store" => [[1,1],[2,2],[3,3],[4,4]]
        ]); ?>
    </div>
<?php } ?>

<div class="container">
    <?= $this->areablock("content"); ?>
</div>