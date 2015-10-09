<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


foreach($this->productList as $product) { ?>
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