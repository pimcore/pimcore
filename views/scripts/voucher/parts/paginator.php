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


?>

<div class="paging">
<?php
if ($this->pageCount > 1): ?>
    <ul class="pagination">
        <!-- Link zur vorherigen Seite -->
        <?php if (isset($this->previous)): ?>
            <li class="first"><a class="pagination-li" href="<?=$this->url(array_merge($this->urlParams, ['action' => 'voucher-code-tab', 'page' => $this->previous]),'plugin')?>" rel="<?=$this->previous?>"><span class="pag-text-label"><span class="glyphicon glyphicon-chevron-left"></span>
                        <?=$this->ts('plugin_onlineshop_voucherservice_paging-previous')?></span>
                </a>
            </li>
        <?php else: ?>
<!--            <li class="first first-page"><span class="pag-text-label">first</span></li>-->
            <li class="first"><span class="pag-text-label"><span class="glyphicon glyphicon-chevron-left"></span><?=$this->ts('plugin_onlineshop_voucherservice_paging-previous')?></span></li>
        <?php endif; ?>

        <!-- Numbered page links -->
        <?php foreach ($this->pagesInRange as $page): ?>
            <?if ($page != $this->current): ?>
                <li><a class="pagination-li" href="<?=$this->url(array_merge($this->urlParams, ['action' => 'voucher-code-tab', 'page' => $page]),'plugin')?>" rel="<?=$page?>"><?=$page?></a></li>
            <?php else: ?>
                <li class="current"><span class="active"><?=$page?></span></li>
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- Link zur nächsten Seite -->
        <?php if (isset($this->next)): ?>
            <li class="last"><a class="pagination-li" href="<?=$this->url(array_merge($this->urlParams, ['action' => 'voucher-code-tab', 'page' => $this->next]),'plugin')?>" rel="<?=$this->next?>"><span class="pag-text-label"><?=$this->ts('plugin_onlineshop_voucherservice_paging-next')?><span class="glyphicon glyphicon-chevron-right"></span></span></a></li>
<!--            <li class="last last-page"><a class="pagination-li" href="--><?//=$baseUrl?><!--?page=--><?//=$this->last?><!--" data-href="--><?//=$this->url . "&page=". $this->last?><!--" rel="--><?//= $this->last ?><!--"><span class="pag-text-label">--><?//= $this->translate('paging.lastpage') ?><!--</span></a></li>-->

        <?php else: ?>
            <li class="last"><span class="pag-text-label"><?=$this->ts('plugin_onlineshop_voucherservice_paging-next')?><span class="glyphicon glyphicon-chevron-right"></span></span></li>
<!--            <li class="last last-page"><span class="pag-text-label">--><?//= $this->translate('paging.lastpage') ?><!--</span></li>-->
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