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

/**
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $this
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $view
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\GlobalVariables\GlobalVariables $app
 */


if ($this->pageCount): ?>
    <ul class="pagination">
        <?php if (isset($this->previous)): ?>
            <li>
                <a href="<?= $this->pimcoreUrl(['page' => $this->previous]); ?>">
                    <span class="glyphicon glyphicon-chevron-left"></span>
                </a>
            </li>
        <?php endif; ?>

        <?php foreach ($this->pagesInRange as $page): ?>
            <li class="<?= $page == $this->current ? 'active' : '' ?>">
                <a href="<?= $this->pimcoreUrl(['page' => $page]); ?>">
                    <?= $page; ?>
                </a>
            </li>
        <?php endforeach; ?>

        <?php if (isset($this->next)): ?>
            <li>
                <a href="<?= $this->pimcoreUrl(['page' => $this->next]); ?>">
                    <span class="glyphicon glyphicon-chevron-right"></span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
<?php endif; ?>
