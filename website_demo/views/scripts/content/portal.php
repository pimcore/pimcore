
<?php $this->headLink(array(
    "rel" => "stylesheet",
    "href" => "/website/static/css/portal.css"));
?>

<div id="myCarousel" class="carousel slide">
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
                <?php echo $this->image("cImage_".$i, array())->frontend(); ?>
                <div class="container">
                    <div class="carousel-caption">
                        <?php
                        if($this->editmode) {
                            echo $this->image("cImage_".$i, array(
                                "reload" => true,
                                "hidetext" => true,
                                "title" => "Drag Image Here",
                                "height" => 30
                            ));
                            echo "<br /><br />";
                        }
                        ?>

                        <h1><?php echo $this->input("cHeadline_".$i); ?></h1>
                        <div class="caption"><?php echo $this->textarea("cText_".$i); ?></div>
                        <div class="margin-bottom-10"><?php echo $this->link("cLink_".$i, array("class" => "btn btn-large btn-default")); ?></div>
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
        Number of Slides: <?php echo $this->select("carouselSlides", array(
            "width" => 60,
            "reload" => true,
            "store" => array(array(1,1),array(2,2),array(3,3), array(4,4))
        )); ?>
    </div>
<?php } ?>

<div class="container">
    <?php echo $this->areablock("content"); ?>
</div>