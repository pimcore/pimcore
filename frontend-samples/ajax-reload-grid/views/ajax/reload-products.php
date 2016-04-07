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


foreach($this->productList as $product) { ?>
    <?= $this->partial("/_shared/productCell.php", array("color" => $this->_getParam("color"), "product" => $product, "config" => $this->config, "document" => $this->document, "cellClass" => "cell")); ?>
<?php } ?>

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