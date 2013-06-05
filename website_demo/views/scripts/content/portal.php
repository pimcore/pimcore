
<?php $this->headLink(array(
    "rel" => "stylesheet",
    "href" => "/website/static/css/portal.css"));
?>

<?php if($this->editmode) { ?>
    <div class="editmode-carousel-container">
        Number of Slides: <?php echo $this->select("carouselSlides", array(
            "width" => 60,
            "reload" => true,
            "store" => array(array(1,1),array(2,2),array(3,3), array(4,4))
        )); ?>
    </div>
<?php } ?>

<!-- Carousel
    ================================================== -->
<div id="myCarousel" class="carousel slide">
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
                        <h1><?php echo $this->input("cHeadline_".$i, array("width" => "300")); ?></h1>

                        <p class="lead"><?php echo $this->textarea("cText_".$i, array("width" => "300")); ?></p>
                        <?php echo $this->link("cLink_".$i, array("class" => "btn btn-large")); ?>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
    <a class="left carousel-control" href="#myCarousel" data-slide="prev">&lsaquo;</a>
    <a class="right carousel-control" href="#myCarousel" data-slide="next">&rsaquo;</a>
</div><!-- /.carousel -->


<!-- Marketing messaging and featurettes
================================================== -->
<!-- Wrap the rest of the page in another container to center all the content. -->

<div class="container marketing">
    <?php echo $this->areablock("content"); ?>
</div><!-- /.container -->