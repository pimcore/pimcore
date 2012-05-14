<?php foreach($this->productList as $product) { ?>
    <?= $this->partial("/_shared/productCell.php", array("color" => $this->_getParam("color"), "product" => $product, "config" => $this->config, "document" => $this->document, "cellClass" => "cell")); ?>
<?php } ?>

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