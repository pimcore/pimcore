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


if($this->pageCount > 1) { ?>
    <div class="paginationCol">
        <?php if (isset($this->previous)) { ?>
            <a href="<?= \OnlineShop\Framework\FilterService\Helper::createPagingQuerystring($this->previous) ?>"
               class="pageLeft"
            >
                back
            </a>
        <?php } ?>

        <?php foreach ($this->pagesInRange as $page) { ?>
            <?php if($this->current == $page) { ?>
                <span><?= $page ?></span>
            <?php } else { ?>
                <a href="<?= \OnlineShop\Framework\FilterService\Helper::createPagingQuerystring($page) ?>">
                    <?= $page ?>
                </a>
            <?php } ?>

        <?php } ?>

        <?php if (isset($this->next)) { ?>
            <a href="<?= \OnlineShop\Framework\FilterService\Helper::createPagingQuerystring($this->next) ?>"
               class="pageRight"
            >
                next
            </a>
        <?php } ?>
    </div>
<?php } ?>