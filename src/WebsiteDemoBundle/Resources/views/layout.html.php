<?php
use Pimcore\Model\Document;
use Pimcore\Model\Document\Page;

/** @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $view */
/** @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $this */
?>
<!DOCTYPE html>
<html lang="<?= $this->getLocale() ?>">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="/pimcore/static/img/favicon/favicon-32x32.png"/>

    <?php
    // portal detection => portal needs an adapted version of the layout
    $isPortal = $this->isPortal ?: false;

    /** @var Document|Page $document */
    $document = $this->document;

    // output the collected meta-data
    if (!$document) {
        // use "home" document as default if no document is present
        $this->document = Document::getById(1);
        $document = $this->document;
    }

    if ($document->getTitle()) {
        // use the manually set title if available
        $this->headTitle()->set($document->getTitle());
    }

    if ($document->getDescription()) {
        // use the manually set description if available
        // TODO HEAD META HELPER
        // $this->headMeta()->appendName('description', $document->getDescription());
    }

    $this->headTitle()->append("pimcore Demo");
    $this->headTitle()->setSeparator(" : ");

    echo $this->headTitle();

    // TODO HEAD META HELPER
    // echo $this->headMeta();
    ?>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Le styles -->
    <?php
    // we use the view helper here to have the cache buster functionality
    $this->headLink()->appendStylesheet('/bundles/websitedemo/bootstrap/css/bootstrap.css');
    $this->headLink()->appendStylesheet('/bundles/websitedemo/css/global.css');
    $this->headLink()->appendStylesheet('/bundles/websitedemo/lib/video-js/video-js.min.css', "screen");
    $this->headLink()->appendStylesheet('/bundles/websitedemo/lib/magnific/magnific.css', "screen");

    if ($this->editmode) {
        $this->headLink()->appendStylesheet('/bundles/websitedemo/css/editmode.css', "screen");
    }
    ?>

    <?= $this->headLink(); ?>

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="/bundles/websitedemo/js/html5shiv.js"></script>
    <script src="/bundles/websitedemo/js/respond.min.js"></script>
    <![endif]-->
</head>

<body class="<?= $isPortal ? "portal-page" : '' ?>">

<div class="navbar-wrapper">

    <?php
    $mainNavStartNode = $document->getProperty('mainNavStartNode');
    if (!$mainNavStartNode) {
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
                        <img src="/bundles/websitedemo/img/logo-white.svg" alt="pimcore Demo">
                    </a>
                </div>
                <div class="navbar-collapse collapse">
                    <?php
                    $mainNavigation = $this->zf1_pimcoreNavigation($document, $mainNavStartNode);
                    echo $mainNavigation->menu()->renderMenu(null, [
                        'maxDepth' => 1,
                        'ulClass'  => 'nav navbar-nav'
                    ]);
                    ?>
                </div>
            </div>

            <?= $this->template('WebsiteDemoBundle:Includes:language.html.php'); ?>
        </div>
    </div>
</div>

<?php if (!$isPortal): ?>
    <?= $this->template('WebsiteDemoBundle:Includes:jumbotron.html.php') ?>

    <div id="content" class="container">
        <?php
        $mainColClass = 'col-md-9 col-md-push-3';
        if ($document->getProperty('leftNavHide')) {
            $mainColClass = 'col-md-12';
        }
        ?>

        <div class="<?= $mainColClass ?>">
            <?php $this['slots']->output('_content') ?>

            <div>
                <a href="/"><?= $this->zf1_translate('Home'); ?></a> &gt;
                <?= $mainNavigation->breadcrumbs()->setMinDepth(null); ?>
            </div>
        </div>

        <?php if (!$document->getProperty('leftNavHide')): ?>
            <div class="col-md-3 col-md-pull-9 sidebar">
                <div class="bs-sidebar hidden-print affix-top" role="complementary">
                    <?php
                    $startNode = $document->getProperty('leftNavStartNode');
                    if (!$startNode) {
                        $startNode = $mainNavStartNode;
                    }
                    ?>

                    <h3><?= $startNode->getProperty('navigation_name'); ?></h3>
                    <?= $this->zf1_pimcoreNavigation($document, $startNode)->menu()->renderMenu(null, [
                        'ulClass'                          => 'nav bs-sidenav',
                        'expandSiblingNodesOfActiveBranch' => true
                    ]); ?>
                </div>
                <?= $this->inc($document->getProperty('sidebar')); ?>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php $this['slots']->output('_content') ?>
<?php endif; ?>

<?php
// include a document-snippet - in this case the footer document
echo $this->inc('/' . $this->getLocale() . '/shared/includes/footer');

// global scripts, we use the view helper here to have the cache buster functionality
$this->headScript()->appendFile('/bundles/websitedemo/js/jquery-1.11.0.min.js');
$this->headScript()->appendFile('/bundles/websitedemo/bootstrap/js/bootstrap.js');
$this->headScript()->appendFile('/bundles/websitedemo/lib/magnific/magnific.js');
$this->headScript()->appendFile('/bundles/websitedemo/lib/video-js/video.js');
$this->headScript()->appendFile('/bundles/websitedemo/js/srcset-polyfill.min.js');

echo $this->headScript();
?>

<script>
    videojs.options.flash.swf = "/bundles/websitedemo/lib/video-js/video-js.swf";
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
    $('.tabbed-slider').on('click', '.nav a', function () {
        clickEvent = true;
        $('.nav li').removeClass('active');
        $(this).parent().addClass('active');
    }).on('slid.bs.carousel', function (e) {
        if (!clickEvent) {
            var count = $('.nav').children().length - 1;
            var current = $('.nav li.active');
            current.removeClass('active').next().addClass('active');
            var id = parseInt(current.data('slide-to'));
            if (count == id) {
                $('.nav li').first().addClass('active');
            }
        }
        clickEvent = false;
    });

    $("#portalHeader img, #portalHeader .item, #portalHeader").height($(window).height());

    <?php if(!$this->editmode): ?>

    // center the caption on the portal page
    $("#portalHeader .carousel-caption").css("bottom", Math.round(($(window).height() - $("#portalHeader .carousel-caption").height()) / 3) + "px");

    $(document).ready(function () {

        // lightbox (magnific)
        $('a.thumbnail').magnificPopup({
            type: 'image',
            gallery: {
                enabled: true
            }
        });

        $(".image-hotspot").tooltip();
        $(".image-marker").tooltip();
    });

    <?php endif; ?>
</script>

</body>
</html>
