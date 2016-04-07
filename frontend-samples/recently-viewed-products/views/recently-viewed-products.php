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


if($this->products) { ?>
    
    <div class="headline">
        <h4>Recently viewed products</h4>
    </div>

    <div class="teasers">
        <?php foreach ($this->products as $product) { ?>
            <?= $this->partial("/_shared/productCell.php", array("product" => $product, "config" => $this->config, "document" => $this->document, "cellClass" => "leftteaser")); ?>
        <?php } ?>
    </div>

<?php } ?>