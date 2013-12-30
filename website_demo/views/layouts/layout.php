<!DOCTYPE html>
<html lang="<?= $this->language; ?>">
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
    <link href="/website/static/bootstrap/dist/css/bootstrap.css" rel="stylesheet">

    <link href="/website/static/css/global.css" rel="stylesheet">

    <link rel="stylesheet" href="/website/static/lib/projekktor/theme/style.css" type="text/css" media="screen" />
    <link rel="stylesheet" href="/website/static/lib/magnific/magnific.css" type="text/css" media="screen" />

    <?= $this->headLink(); ?>

    <?php if($this->editmode) { ?>
        <link href="/website/static/css/editmode.css?_dc=<?= time(); ?>" rel="stylesheet">
    <?php } ?>

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="/website/static/bootstrap/assets/js/html5shiv.js"></script>
    <script src="/website/static/bootstrap/assets/js/respond.min.js"></script>
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
                        <img src="/website/static/img/logo.png">
                    </a>
                </div>
                <div class="navbar-collapse collapse">
                    <?php
                        $mainNavigation = $this->pimcoreNavigation()->getNavigation($this->document, $mainNavStartNode);
                        $this->navigation($mainNavigation);
                        echo $this->navigation()->menu()->setUseTranslator(false)->renderMenu($mainNavigation, array("maxDepth" => 1, "ulClass" => "nav navbar-nav"));
                    ?>
                </div>
            </div>
            <?= $this->inc("/shared/includes/languages"); ?>
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

            $colorMapping = array(
                "blue" => array("#258dc1","#2aabeb"),
                "green" => array("#278415","#1a9f00")
            );
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
        <div class="col-md-<?php if(!$this->document->getProperty("leftNavHide")) { ?>9<?php } else { ?>12<?php } ?> col-md-push-3">
            <?= $this->layout()->content; ?>

            <div>
                <a href="/"><?= $this->translate("Home"); ?></a> &gt;
                <?php
                    $this->navigation($mainNavigation);
                    echo $this->navigation()->breadcrumbs()->setMinDepth(null);
                ?>
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
                    <?php
                    $leftNavigation = $this->pimcoreNavigation()->getNavigation($this->document, $startNode);
                    $this->navigation($leftNavigation);
                    echo $this->navigation()->menu($leftNavigation)->setUseTranslator(false)->renderMenu($leftNavigation, array(
                        "ulClass" => "nav bs-sidenav",
                        "expandSiblingNodesOfActiveBranch" => true
                    ));
                    ?>
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
?>

<script src="/website/static/bootstrap/assets/js/jquery.js"></script>
<script src="/website/static/bootstrap/dist/js/bootstrap.js"></script>



<script src="/website/static/lib/projekktor/projekktor-1.2.25r232.min.js"></script>
<script src="/website/static/lib/magnific/magnific.js"></script>
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


</body>
</html>
