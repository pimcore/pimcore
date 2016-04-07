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


?>


<div class="js_ajaxgrid contentblock filtering" <?php if($this->editmode) { echo 'style="padding-top: 10px"'; } ?>>
    <form id="js_filterfield" method="GET">
        <input type="hidden" name="page" value="<?= $this->currentPage ?>" />
        <input type="hidden" name="filterdef" id="filterdef" value="<?= $this->filterDefinitionObject->getId() ?>" />

        <div class="left">

            <div class="headline">
                <?= $this->input("grid_headline") ?>
            </div>

            <?php if($this->filterDefinitionObject->getFilters()) { ?>
                <?php foreach ($this->filterDefinitionObject->getFilters() as $filter) { ?>
                    <?php
                        echo $this->filterService->getFilterFrontend($filter, $this->productList, $this->currentFilter);
                    ?>
                <?php } ?>
            <?php } ?>


        </div>

        <?php if($this->orderByOptions) { ?>
            <div class="right">

                <div class="headline">
                    <?= $this->translate("grid_sortby") ?>
                </div>

                <!-- // ---- SORTING Filter ---- // -->
                <div class="filter standard">
                    <div class="select order js_filterparent">
                        <input class="js_optionvaluefield" type="hidden" name="orderBy" value="<?= $this->currentOrderBy ?>" />
                        <div class="selection">
                            <div class="head">
                                <span class="arrow"></span>
                                <span class="name js_curent_selection_text"><?= $this->translate("grid_sortby_" . $this->currentOrderBy) ?></span>
                            </div>
                            <div class="actual">
                            </div>
                        </div>
                        <div class="options">
                            <ul>
                                <?php foreach($this->orderByOptions as $orderField => $directions) { ?>
                                    <?php foreach($directions as $dir => $value) { ?>
                                        <?php if($value) { ?>
                                            <li><span class="option js_optionfilter_option" rel="<?= $orderField . '#' . $dir?>"><?= $this->translate("grid_sortby_" . $orderField . '#' . $dir) ?> </span></li>
                                        <?php } ?>
                                    <?php } ?>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        <?php } ?>
    </form>
</div>

<div class="contentblock teasergrid" >
    <?php if($this->editmode) { ?>
        <div class="cell">
            <div class="introtext">
                <div class="text">
                    <h4><?= $this->input("grid_seo_headline") ?></h4>
                    <p><?= $this->wysiwyg("grid_seo_text") ?></p>
                </div>
            </div>
        </div>
    <?php } else { ?>
        <?php if(!empty($this->grid_seo_headline) && !empty($this->grid_seo_text)) { ?>
            <div class="cell">
                <div class="introtext">
                    <div class="text">
                        <h4><?= $this->grid_seo_headline ?></h4>
                        <p><?= $this->grid_seo_text ?></p>
                    </div>
                </div>
            </div>
        <?php } ?>
    <?php } ?>
    <!--<div class="link">
        <a href="#">read more</a>
    </div>-->

    <?php foreach($this->productList as $product) { ?>
        <?= $this->partial("/_shared/productCell.php", array("color" => $this->_getParam("color"), "product" => $product, "config" => $this->config, "document" => $this->document, "cellClass" => "cell")); ?>
    <?php } ?>

    <div class="js_ajaxcontainer">
        <?php if($this->pageLimit == count($this->productList->getProducts())) { ?>
            <div class="pagination" style="clear:both">
                <?php
                    $paginator = \Zend_Paginator::factory($this->productList);

                    $paginator->setCurrentPageNumber($this->currentPage);
                    $paginator->setItemCountPerPage($this->pageLimit);
                    $paginator->setPageRange(5);

                    echo $this->paginationControl($paginator,
                            'Sliding',
                            '_shared/pagination.php'
                    );

                ?>
            </div>
        <?php } else if(count($this->productList->getProducts()) < count($this->productList)) { ?>
            <a href="?<?= $_SERVER['QUERY_STRING']?>&fullpage=1">show more</a>
        <?php } ?>
    </div>

</div>