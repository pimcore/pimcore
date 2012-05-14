
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
                    $paginator = Zend_Paginator::factory($this->productList);

                    $paginator->setCurrentPageNumber($this->currentPage);
                    $paginator->setItemCountPerPage($this->pageLimit);
                    $paginator->setPageRange(5);

                    echo $this->paginationControl($paginator,
                            'Sliding',
                            '_shared/pagination.php'
                    );

                ?>
            </div>
        <?php } else if(count($this->productList->getProducts()) > count($this->productList)) { ?>
            <a href="?<?= $_SERVER['QUERY_STRING']?>&fullpage=1">show more</a>
        <?php } ?>
    </div>

</div>
