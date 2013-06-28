<?php
$article = $this->object;
/* @var $article Website_Object_Artikel */
?>
<style type="text/css">
    .preview .headline {
        position: relative;
    }
    .preview .headline .supplier {
        position: absolute;
        right: 0px;
        top: 0px;
    }

    .preview .article {
        overflow: hidden;
        float: left;
        width: 540px;
    }

    .preview .article .pictures {
        width: 170px;
        float: left;
    }
    .preview .article .pictures .big {
        display: block;
        width: 160px;
        height: 160px;
    }
    .preview .article .description {
        width: 350px;
        float: left;

    }
    .preview .service {
        width: 180px;
        position: absolute;
        top: 0px;
        right: 0px;
    }
    .preview .service .headline {
        padding: 7px 0 0 15px;
        height: 23px;
        background-image: url("http://www.kautbullinger.de/shop/includes/templates/kautbullinger/images/category-tab-back.png");
        background-repeat: no-repeat;
        background-position: 0px -520px;
        font-size: 11pt;
        font-weight: bold;
        color: white;
        margin: 0px;
    }
    .preview .service .content {
        border-left: 1px solid #e16767;
        border-right: 1px solid #e16767;
        border-bottom: 1px solid #e16767;
        padding: 10px;
    }
    .preview .service .content .price {
        font-size: 12pt;
        font-weight: bold;
        color: #bf331a;
    }
    .preview .service .content .contact-list {
        margin: 20px 0px 0px 0px;
        padding: 0px;
        list-style: none;
    }
    .preview .service .content .contact-list .item {
        margin: 0px 0px 5px 0px;
        padding: 0px;
    }
    .preview .service .content .contact-list .item img {
        vertical-align: middle;
    }
</style>

<div class="article">
    <div class="headline">
        <h1><?= $article->getBez() ?>, <?= $article->getManufacturerName() ?></h1>
<!--        <img class="supplier" src="http://www.kautbullinger.de/shop/images/logos/075x/leitz.jpg" alt="Leitz" title=" Leitz " width="75" height="14" id="brand_image">-->
    </div>
    <div class="pictures">
        <div class="big">
            <?php if($picture = $article->getExtEinzelabbildung()): ?>
            <img src="<?= $picture->getThumbnail(array('width' => 160, 'height' => 160)) ?>" />
            <?php endif; ?>
        </div>
<!--        <div class="">-->
<!--            <img src="http://www.kautbullinger.de/shop/images/small/kautbullinger/11734.jpg" />-->
<!--            <img src="http://www.kautbullinger.de/shop/images/small/kautbullinger/11734.jpg" />-->
<!--            <img src="http://www.kautbullinger.de/shop/images/small/kautbullinger/11734.jpg" />-->
<!--            <img src="http://www.kautbullinger.de/shop/images/small/kautbullinger/11734.jpg" />-->
<!--            <img src="http://www.kautbullinger.de/shop/images/small/kautbullinger/11734.jpg" />-->
<!--            <img src="http://www.kautbullinger.de/shop/images/small/kautbullinger/11734.jpg" />-->
<!--            <img src="http://www.kautbullinger.de/shop/images/small/kautbullinger/11734.jpg" />-->
<!--        </div>-->
    </div>
    <div class="description">
        <?= $article->getExtTextKatalog() ?>
    </div>
</div>
<div class="service">
    <h3 class="headline">Bester Preis</h3>
    <div class="content">
        <span class="price">ab <?= number_format($article->getVkprs(), 2, ',', '.') ?> € </span>
        <ul class="contact-list">
            <li class="item"><img src="http://www.kautbullinger.de/shop/includes/templates/kautbullinger/images/i_icon_fax.gif" alt=""> &nbsp; <a href="http://www.kautbullinger.de/shop/contact_us.html">E-Mail senden</a></li>
            <li class="item"><img src="http://www.kautbullinger.de/shop/includes/templates/kautbullinger/images/i_icon_phone.gif" alt=""> &nbsp; (01801) 666 99 0-1100*</li>
        </ul>
    </div>
</div>