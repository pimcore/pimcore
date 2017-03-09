<!DOCTYPE html>
<html lang="<?= $this->language; ?>">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="/pimcore/static/img/favicon/favicon-32x32.png" />

    <?php
        // portal detection => portal needs an adapted version of the layout
        $isPortal = false;
        if($this->getParam("controller") == "content" && $this->getParam("action") == "portal") {
            $isPortal = true;
        }

        // output the collected meta-data
        if(!$this->document) {
            // use "home" document as default if no document is present
            $this->document = Document::getById(1);
        }

        if($this->document->getTitle()) {
            // use the manually set title if available
            $this->headTitle()->set($this->document->getTitle());
        }

        if($this->document->getDescription()) {
            // use the manually set description if available
            $this->headMeta()->appendName('description', $this->document->getDescription());
        }

        $this->headTitle()->append("pimcore Demo");
        $this->headTitle()->setSeparator(" : ");

        echo $this->headTitle();
        echo $this->headMeta();
    ?>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Le styles -->
    <?php
        // we use the view helper here to have the cache buster functionality
        $this->headLink()->appendStylesheet('/website/static/bootstrap/css/bootstrap.css');
        $this->headLink()->appendStylesheet('/website/static/css/global.css');
        $this->headLink()->appendStylesheet('/website/static/lib/video-js/video-js.min.css', "screen");
        $this->headLink()->appendStylesheet('/website/static/lib/magnific/magnific.css', "screen");

        if($this->editmode) {
            $this->headLink()->appendStylesheet('/website/static/css/editmode.css', "screen");
        }
    ?>

    <?= $this->headLink(); ?>

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="/website/static/js/html5shiv.js"></script>
    <script src="/website/static/js/respond.min.js"></script>
    <![endif]-->

</head>

<body class="<?= $isPortal ? "portal-page" : "" ?>">

<div class="navbar-wrapper">
    <?php
        $mainNavStartNode = $this->document->getProperty("mainNavStartNode");
        if(!$mainNavStartNode) {
            $mainNavStartNode = Document::getById(1);
        }
    ?>
    <div class="container">
        <div class="navbar navbar-inverse navbar-static-top">
            <div class="container">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="<?= $mainNavStartNode; ?>">
                        <img src="/website/static/img/logo-white.svg" alt="pimcore Demo">
                    </a>
                </div>
                <div class="navbar-collapse collapse">
                    <?php
                        $mainNavigation = $this->pimcoreNavigation($this->document, $mainNavStartNode);
                        echo $mainNavigation->menu()->renderMenu(null, [
                            "maxDepth" => 1,
                            "ulClass" => "nav navbar-nav"
                        ]);
                    ?>
                </div>
            </div>
            <?= $this->template("/includes/language.php"); ?>
        </div>
    </div>
</div>

<?php if(!$isPortal) { ?>
    <header class="jumbotron subhead">
        <div class="container">
            <h2><?= $this->input("headTitle"); ?></h2>
            <p class="lead"><?= $this->input("headDescription"); ?></p>
        </div>
    </header>
    <?php
        $color = $this->document->getProperty("headerColor");
        if($color) { // orange is the default color

            $colorMapping = [
                "blue" => ["#258dc1","#2aabeb"],
                "green" => ["#278415","#1a9f00"]
            ];
            $c = $colorMapping[$color];
        ?>
        <style>
            .jumbotron {
                background: <?= $c[1]; ?>; /* Old browsers */
                background: -moz-linear-gradient(45deg, <?= $c[0]; ?> 0%, <?= $c[1]; ?> 100%); /* FF3.6+ */
                background: -webkit-gradient(linear, left bottom, right top, color-stop(0%, <?= $c[0]; ?>), color-stop(100%, <?= $c[1]; ?>)); /* Chrome,Safari4+ */
                background: -webkit-linear-gradient(45deg, <?= $c[0]; ?> 0%, <?= $c[1]; ?> 100%); /* Chrome10+,Safari5.1+ */
                background: -o-linear-gradient(45deg, <?= $c[0]; ?> 0%, <?= $c[1]; ?> 100%); /* Opera 11.10+ */
                background: -ms-linear-gradient(45deg, <?= $c[0]; ?> 0%, <?= $c[1]; ?> 100%); /* IE10+ */
                background: linear-gradient(45deg, <?= $c[0]; ?> 0%, <?= $c[1]; ?> 100%); /* W3C */
                filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='<?= $c[0]; ?>', endColorstr='<?= $c[1]; ?>', GradientType=1); /* IE6-9 fallback on horizontal gradient */
            }
        </style>
    <?php } ?>

    <div id="content" class="container">
        <div class="col-md-<?php if(!$this->document->getProperty("leftNavHide")) { ?>9 col-md-push-3<?php } else { ?>12<?php } ?>">
            <?= $this->layout()->content; ?>

            <div>
                <a href="/"><?= $this->translate("Home"); ?></a> &gt;
                <?= $mainNavigation->breadcrumbs()->setMinDepth(null); ?>
            </div>
        </div>

        <?php if(!$this->document->getProperty("leftNavHide")) { ?>
            <div class="col-md-3 col-md-pull-9 sidebar">
                <div class="bs-sidebar hidden-print affix-top" role="complementary">
                    <?php
                        $startNode = $this->document->getProperty("leftNavStartNode");
                        if(!$startNode) {
                            $startNode = $mainNavStartNode;
                        }
                    ?>
                    <h3><?= $startNode->getProperty("navigation_name"); ?></h3>
                    <?= $this->pimcoreNavigation($this->document, $startNode)->menu()->renderMenu(null, [
                        "ulClass" => "nav bs-sidenav",
                        "expandSiblingNodesOfActiveBranch" => true
                    ]); ?>
                </div>
                <?= $this->inc($this->document->getProperty("sidebar")); ?>
            </div>
        <?php } ?>
    </div>
<?php } else { ?>
    <?= $this->layout()->content; ?>
<?php } ?>



<?php
    // include a document-snippet - in this case the footer document
    echo $this->inc("/" . $this->language . "/shared/includes/footer");

    // global scripts, we use the view helper here to have the cache buster functionality
    $this->headScript()->appendFile('/website/static/js/jquery-1.11.0.min.js');
    $this->headScript()->appendFile('/website/static/bootstrap/js/bootstrap.js');
    $this->headScript()->appendFile('/website/static/lib/magnific/magnific.js');
    $this->headScript()->appendFile('/website/static/lib/video-js/video.js');
    $this->headScript()->appendFile('/website/static/js/srcset-polyfill.min.js');

    echo $this->headScript();
?>

<script>
    videojs.options.flash.swf = "/website/static/lib/video-js/video-js.swf";
</script>

<script>

    // main menu
    $(".navbar-wrapper ul.nav>li>ul").each(function () {
        var li = $(this).parent();
        var a = $("a.main", li);

        $(this).addClass("dropdown-menu");
        li.addClass("dropdown");
        a.addClass("dropdown-toggle");
        li.on("mouseenter", function () {
            $("ul", $(this)).show();
        });
        li.on("mouseleave", function () {
            $("ul", $(this)).hide();
        });
    });

    // side menu
    $(".bs-sidenav ul").each(function () {
        $(this).addClass("nav");
    });

    // gallery carousel: do not auto-start
    $('.gallery').carousel('pause');

    // tabbed slider text
    var clickEvent = false;
    $('.tabbed-slider').on('click', '.nav a', function() {
        clickEvent = true;
        $('.nav li').removeClass('active');
        $(this).parent().addClass('active');
    }).on('slid.bs.carousel', function(e) {
        if(!clickEvent) {
            var count = $('.nav').children().length -1;
            var current = $('.nav li.active');
            current.removeClass('active').next().addClass('active');
            var id = parseInt(current.data('slide-to'));
            if(count == id) {
                $('.nav li').first().addClass('active');
            }
        }
        clickEvent = false;
    });

    $("#portalHeader img, #portalHeader .item, #portalHeader").height($(window).height());

    <?php if(!$this->editmode) { ?>

        // center the caption on the portal page
        $("#portalHeader .carousel-caption").css("bottom", Math.round(($(window).height() - $("#portalHeader .carousel-caption").height())/3) + "px");

        $(document).ready(function() {

            // lightbox (magnific)
            $('a.thumbnail').magnificPopup({
                type:'image',
                gallery: {
                    enabled: true
                }
            });

            $(".image-hotspot").tooltip();
            $(".image-marker").tooltip();
        });

    <?php } ?>
</script>

</body>
</html>
