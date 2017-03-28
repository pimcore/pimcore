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

<div class="row border content-block">
    <div class="col col-sm-3">
        <div class="statistics">
            <h3><?=$this->translateAdmin('plugin_onlineshop_voucherservice_token-statistic-headline')?></h3>
        </div>
        <canvas id="canvas-token"></canvas>
        <table class="table current-data" style="margin-top: 35px;">
            <tbody>
            <tr>
                <td><span class="glyphicon glyphicon-list-alt"></span>&nbsp; <?=$this->translateAdmin('plugin_onlineshop_voucherservice_token-overall')?></td>
                <td><?= number_format($this->statistics['overallCount'], 0, ',', ' ') ?></td>
            </tr>
            <tr>
                <td><span style="color: <?=$this->colors['used']?>;" class="glyphicon glyphicon-share"></span>&nbsp; <?=$this->translateAdmin('plugin_onlineshop_voucherservice_token-used')?></td>
                <td><?= number_format($this->statistics['usageCount'], 0, ',', ' ') ?></td>
            </tr>
            <tr>
                <td><span style="color: <?=$this->colors['reserved']?>;" class="glyphicon glyphicon-edit"></span>&nbsp; <?=$this->translateAdmin('plugin_onlineshop_voucherservice_token-reserved')?></td>
                <td><?= number_format($this->statistics['reservedCount'], 0, ',', ' ') ?></td>
            </tr>
            <tr>
                <td><span style="color: <?=$this->colors['free']?>;" class="glyphicon glyphicon-check"></span>&nbsp; <?=$this->translateAdmin('plugin_onlineshop_voucherservice_token-free')?></td>
                <td><?= number_format($this->statistics['freeCount'], 0, ',', ' ') ?></td>
            </tr>
            </tbody>
        </table>

    </div>
    <div class="col col-sm-9 canvas-container">
        <div class="statistics">
            <h3><?=$this->translateAdmin('plugin_onlineshop_voucherservice_usage-headline')?></h3>
        </div>
        <canvas id="canvas-usage" height="130" style="padding-right: 50px;"></canvas>
    </div>
</div>
