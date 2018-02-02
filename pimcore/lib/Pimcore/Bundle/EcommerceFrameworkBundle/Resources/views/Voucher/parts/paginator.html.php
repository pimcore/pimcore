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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


/**
 * @var \Pimcore\Templating\PhpEngine $this
 */
?>

<div class="paging">
<?php
if ($this->pageCount > 1): ?>
    <ul class="pagination">
        <!-- Link zur vorherigen Seite -->
        <?php if (isset($this->previous)): ?>
            <li class="first"><a class="pagination-li" href="<?=$this->pimcoreUrl(['page' => $this->previous])?>" rel="<?=$this->previous?>"><span class="pag-text-label"><span class="glyphicon glyphicon-chevron-left"></span>
                        <?=$this->translateAdmin('bundle_ecommerce_voucherservice_paging-previous')?></span>
                </a>
            </li>
        <?php else: ?>
            <li class="first"><span class="pag-text-label"><span class="glyphicon glyphicon-chevron-left"></span><?=$this->translateAdmin('bundle_ecommerce_voucherservice_paging-previous')?></span></li>
        <?php endif; ?>

        <!-- Numbered page links -->
        <?php foreach ($this->pagesInRange as $page): ?>
            <?php if ($page != $current): ?>
                <li><a class="pagination-li" href="<?=$this->pimcoreUrl(['page' => $page])?>" rel="<?=$page?>"><?=$page?></a></li>
            <?php else: ?>
                <li class="current"><span class="active"><?=$page?></span></li>
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- Link zur nächsten Seite -->
        <?php if (isset($this->next)): ?>
            <li class="last"><a class="pagination-li" href="<?=$this->pimcoreUrl(['page' => $this->next])?>" rel="<?=$this->next?>"><span class="pag-text-label"><?=$this->translateAdmin('bundle_ecommerce_voucherservice_paging-next')?><span class="glyphicon glyphicon-chevron-right"></span></span></a></li>

        <?php else: ?>
            <li class="last"><span class="pag-text-label"><?=$this->translateAdmin('bundle_ecommerce_voucherservice_paging-next')?><span class="glyphicon glyphicon-chevron-right"></span></span></li>
        <?php endif; ?>
    </ul>
<?php endif; ?>


</div>

<style>

    .paging ul.pagination li{
        padding: 0 ;
    }

    .paging ul.pagination li a, ul.pagination li span {
        border:0;
        color:black;
        display: inline-block;
        font-size: 14px;
        padding: 2px;
        margin: -2px 2px 0;
        text-decoration: none;
    }

    .paging ul.pagination  a:hover, ul.pagination  a:focus, ul.pagination li a.active, ul.pagination li span.active {
        background: #ecf0f1;
    }

    .bg-pattern .paging ul.pagination  a:hover, .bg-pattern ul.pagination  a:focus, .bg-pattern ul.pagination li a.active, .bg-pattern ul.pagination li span.active {
        text-decoration: underline;
        background-color: transparent;
    }

    .paging ul.pagination span.pag-text-label{
        font-weight: bold;
    }

    .paging .pagination li span, .paging .pagination li a{
        background-color: transparent;
    }

</style>
