<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


$queryString = $_SERVER['QUERY_STRING'] ? '#'.$_SERVER['QUERY_STRING'] : '';
    $linkProduct = $this->product;

    $children = $this->product->getColorVariants(true); // $product->getChilds(array(Object_Abstract::OBJECT_TYPE_OBJECT));
    $thumbnails = array();
    $imageChildren = array();
    if(count($children) > 0) {
        $linkProduct = $children[0];
    }

    $thumbnail = $this->product->getFirstImage('productThumb');

    foreach ($children as $child) {
        if($this->color && $child->getColor()) {
            if(in_array($this->color, $child->getColor())) {
                $thumbnail =  $child->getFirstImage('productThumb');
                $linkProduct = $child;
            }
        }
    }


    $productSeoName =  (  trim( $this->product->getSeoname() != "" ) ) ? trim($this->product->getSeoname() ) : trim($this->product->getName());
    $friendlyUrl = $linkProduct->getFriendlyUrl();

?>

<div class="<?= $this->cellClass ?>">
    <div class="gridteaser product">
        <div class="productcontainer gotolink">
            <div class="image">
                <?php
                    $url = $this->document . "/" .  $this->url(array("productUrlName" => $friendlyUrl, "productId" => $linkProduct->getId()), "product_detail") . $queryString;
                    $url = str_replace("//", "/", $url);
                ?>
                <a title="<?= $productSeoName ?>" href="<?= $url ?>">
                    <img src="<?= $thumbnail ?>" alt="<?= $productSeoName ?>" />
                </a>
            </div>
            <div class="text">
                <div class="info">
                    <div class="left">
                        <span class="name"><?= $this->product->getName() ?></span><br />

                    </div>
                    <div class="right">
                        <?php if ($this->config->enablePrices) { ?>
                            <?php $priceRange = $this->product->getPriceRange() ?>
                            <?php if (!empty($priceRange)) { ?>
                                <span class="price">
                                    <strong><?= $this->priceFormater($priceRange) ?></strong>
                                </span><br />
                            <?php } ?>
                        <?php } ?>
                        <?php if ($this->config->enableRating) { ?>
                            <?php $rating = $this->product->getRating(); ?>
                            <?php $starRating = round($rating) ?>
                            <span class="starrating">
                                <?php for ($i = 1; $i<=5; $i++): ?>
                                    <?php if ($i <= $starRating): ?>
                                        <span class="star"></span>
                                    <?php else: ?>
                                        <span class="star empty"></span>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </span>

                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="hoverinfo">
            <?php if (count($children) > 1) { ?>

                    <div class="colors">
                        <?php foreach ($children as $child) { ?>
                            <div class="color">
                                <?php
                                    $url = $this->document . "/" . $this->url(array("productUrlName" => $friendlyUrl, "productId" => $child->getId()), "product_detail") . $queryString;
                                    $url = str_replace("//", "/", $url);
                                ?>
                                <a title="<?= $child->getColorName();?>" href="<?= $url ?>">
                                    <img src="<?= $child->getFirstImage('productVariant') ?>" alt="<?=$child->getColorName() . ' - '. $productSeoName?>" />
                                </a>
                            </div>
                        <?php } ?>
                    </div>

            <? } ?>
             <p class="cta">
                <a class="roundedbutton red" title="<?= $productSeoName ?>" href="<?= $url ?>"><?= $this->translate('productitem.calltoaction');?></a>
             </p>
            </div>
        </div>
    </div>

</div>