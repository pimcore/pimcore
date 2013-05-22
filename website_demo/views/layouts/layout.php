<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>">
<head>
    <meta charset="utf-8">

    <?php
        // portal detection => portal needs an adapted version of the layout
        $isPortal = false;
        if($this->getParam("controller") == "content" && $this->getParam("action") == "portal") {
            $isPortal = true;
        }

        // output the collected meta-data
        if(!$this->document) {
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
    <link href="/website/static/bootstrap/css/bootstrap.css" rel="stylesheet">
    <?php if(!$this->editmode) { // do not provide responsive in editmode, ... it works but it's annoying ;-) ?>
        <link href="/website/static/bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
    <?php } ?>
    <link href="/website/static/css/global.css" rel="stylesheet">
    <link rel="stylesheet" href="/website/static/lib/projekktor/theme/style.css" type="text/css" media="screen" />
    <link rel="stylesheet" href="/website/static/lib/magnific/magnific.css" type="text/css" media="screen" />

    <?php echo $this->headLink(); ?>

    <?php if($this->editmode) { ?>
        <link href="/website/static/css/editmode.css?_dc=<?php echo time(); ?>" rel="stylesheet">
    <?php } ?>

    <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
    <script src="/website/static/bootstrap/js/html5shiv.js"></script>
    <![endif]-->


    <script>
      (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
      (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
      m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
      })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

      ga('create', 'UA-12436865-5', 'pimcore.org');
      ga('send', 'pageview');
    </script>
</head>

<body>


<!-- NAVBAR
================================================== -->
<div class="navbar-wrapper">
    <!-- Wrap the .navbar in .container to center it within the absolutely positioned parent. -->
    <div class="container">

        <div class="navbar navbar-inverse">
            <div class="navbar-inner">
                <!-- Responsive Navbar Part 1: Button for triggering responsive navbar (not covered in tutorial). Include responsive CSS to utilize. -->
                <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="brand" href="<?php echo Document::getById(1); ?>">
                    <img src="/website/static/img/logo.png">
                </a>
                <!-- Responsive Navbar Part 2: Place all navbar contents you want collapsed withing .navbar-collapse.collapse. -->
                <div class="nav-collapse collapse">
                    <?php
                        $navStartNode = Document::getById(1);
                        $navigation = $this->pimcoreNavigation()->getNavigation($this->document, $navStartNode);
                        $this->navigation($navigation);
                        echo $this->navigation()->menu()->setUseTranslator(false)->renderMenu($navigation, array("maxDepth" => 1, "ulClass" => "nav"));
                    ?>
                </div>
                <!--/.nav-collapse -->
            </div>
            <!-- /.navbar-inner -->
        </div>
        <!-- /.navbar -->

    </div>
    <!-- /.container -->
</div>
<!-- /.navbar-wrapper -->


<?php if(!$isPortal) { ?>
    <!-- Subhead
    ================================================== -->
    <header class="jumbotron subhead" id="overview">
        <div class="container">
            <h1><?php echo $this->input("headTitle"); ?></h1>
            <p class="lead"><?php echo $this->input("headDescription"); ?></p>
        </div>
    </header>
    <?php
        $color = $this->document->getProperty("headerColor");
        if($color && $color != "orange") { // orange is the default color

        $colorMapping = array(
            "blue" => array("#074f73","#0088cc"),
            "green" => array("#278415","#1a9f00")
        );
        $c = $colorMapping[$color];
        ?>
        <style>
            .jumbotron {
                background: <?php echo $c[1]; ?>; /* Old browsers */
                background: -moz-linear-gradient(45deg, <?php echo $c[0]; ?> 0%, <?php echo $c[1]; ?> 100%); /* FF3.6+ */
                background: -webkit-gradient(linear, left bottom, right top, color-stop(0%, <?php echo $c[0]; ?>), color-stop(100%, <?php echo $c[1]; ?>)); /* Chrome,Safari4+ */
                background: -webkit-linear-gradient(45deg, <?php echo $c[0]; ?> 0%, <?php echo $c[1]; ?> 100%); /* Chrome10+,Safari5.1+ */
                background: -o-linear-gradient(45deg, <?php echo $c[0]; ?> 0%, <?php echo $c[1]; ?> 100%); /* Opera 11.10+ */
                background: -ms-linear-gradient(45deg, <?php echo $c[0]; ?> 0%, <?php echo $c[1]; ?> 100%); /* IE10+ */
                background: linear-gradient(45deg, <?php echo $c[0]; ?> 0%, <?php echo $c[1]; ?> 100%); /* W3C */
                filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='<?php echo $c[0]; ?>', endColorstr='<?php echo $c[1]; ?>', GradientType=1); /* IE6-9 fallback on horizontal gradient */
            }
        </style>
    <?php } ?>

    <div class="container">
        <div class="row">
            <?php if(!$this->document->getProperty("leftNavHide")) { ?>
                <div class="span3 bs-docs-sidebar">
                    <?php
                        $startNode = $this->document->getProperty("leftNavStartNode");
                        if(!$startNode) {
                            $startNode = Document::getById(1);
                        }
                        $navigation = $this->pimcoreNavigation()->getNavigation($this->document, $startNode);
                        $this->navigation($navigation);
                        echo $this->navigation()->menu($navigation)->setUseTranslator(false)->renderMenu($navigation, array(
                            "ulClass" => "nav nav-list bs-docs-sidenav",
                            "expandSiblingNodesOfActiveBranch" => true
                        ));
                    ?>
                </div>
            <?php } ?>

            <div class="<?php if(!$this->document->getProperty("leftNavHide")) { ?>span9<?php } else { ?>span12<?php } ?>" id="content">
                <?php echo $this->layout()->content; ?>


                <div class="breadcrumb">
                    <?php
                        $navStartNode = Document::getById(1);
                        $navigation = $this->pimcoreNavigation()->getNavigation($this->document, $navStartNode);
                        $this->navigation($navigation);
                        echo $this->navigation()->breadcrumbs()->setUseTranslator(false)->setMinDepth(0)->setLinkLast(true);
                    ?>
                </div>
            </div>
        </div>
    </div>
<?php } else { ?>
    <?php echo $this->layout()->content; ?>
<?php } ?>


<?php
    // include a document-snippet
    echo $this->inc("/shared/includes/footer");
?>

<!-- Le javascript
================================================== -->
<!-- Placed at the end of the document so the pages load faster -->
<script src="/website/static/bootstrap/js/jquery.js"></script>
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
</script>
<script src="/website/static/bootstrap/js/bootstrap-transition.js"></script>
<script src="/website/static/bootstrap/js/bootstrap-alert.js"></script>
<script src="/website/static/bootstrap/js/bootstrap-modal.js"></script>
<script src="/website/static/bootstrap/js/bootstrap-dropdown.js"></script>
<script src="/website/static/bootstrap/js/bootstrap-scrollspy.js"></script>
<script src="/website/static/bootstrap/js/bootstrap-tab.js"></script>
<script src="/website/static/bootstrap/js/bootstrap-tooltip.js"></script>
<script src="/website/static/bootstrap/js/bootstrap-popover.js"></script>
<script src="/website/static/bootstrap/js/bootstrap-button.js"></script>
<script src="/website/static/bootstrap/js/bootstrap-collapse.js"></script>
<script src="/website/static/bootstrap/js/bootstrap-carousel.js"></script>
<script src="/website/static/bootstrap/js/bootstrap-typeahead.js"></script>
<script src="/website/static/lib/projekktor/projekktor-1.2.25r232.min.js"></script>
<script src="/website/static/lib/magnific/magnific.js"></script>
<script>
    !function ($) {
        $(function () {
            // carousel demo
            $('#myCarousel').carousel({
                interval: <?php echo ($this->editmode ? "false" : "5000"); ?>
            })
        })
    }(window.jQuery);

    <?php if(!$this->editmode) { ?>
        $(document).ready(function() {
            // initialize projekktor, the HTML5 video player
            projekktor(
                'video',
                {playerFlashMP4: "/website/static/lib/projekktor/jarisplayer.swf"}
            );

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
<script src="/website/static/bootstrap/js/holder/holder.js"></script>
</body>
</html>
