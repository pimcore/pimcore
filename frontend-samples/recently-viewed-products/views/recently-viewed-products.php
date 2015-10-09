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