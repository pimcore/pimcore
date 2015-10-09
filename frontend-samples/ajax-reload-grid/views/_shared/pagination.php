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


if($this->pageCount > 1) { ?>
    <div class="paginationCol">
        <?php if (isset($this->previous)) { ?>
            <a href="<?= OnlineShop_Framework_FilterService_Helper::createPagingQuerystring($this->previous) ?>"
               class="pageLeft"
            >
                back
            </a>
        <?php } ?>

        <?php foreach ($this->pagesInRange as $page) { ?>
            <?php if($this->current == $page) { ?>
                <span><?= $page ?></span>
            <?php } else { ?>
                <a href="<?= OnlineShop_Framework_FilterService_Helper::createPagingQuerystring($page) ?>">
                    <?= $page ?>
                </a>
            <?php } ?>

        <?php } ?>

        <?php if (isset($this->next)) { ?>
            <a href="<?= OnlineShop_Framework_FilterService_Helper::createPagingQuerystring($this->next) ?>"
               class="pageRight"
            >
                next
            </a>
        <?php } ?>
    </div>
<?php } ?>