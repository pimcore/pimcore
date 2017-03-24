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
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $this
 */
?>

<div id="generate" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body-content">
                <h3><?=$this->translateAdmin('plugin_onlineshop_voucherservice_modal_generate-headline')?></h3>
                <div class="row">
                    <div class="col col-sm-10">
                        <table class="table current-data table-only-body">
                            <tbody>
                            <?php foreach ($this->settings as $name => $setting) { ?>
                                <tr>
                                    <td><?= $this->translateAdmin($name) ?></td>
                                    <td>
                                        <?php if (is_numeric($setting)) {
                                            echo number_format($setting, 0, ',', ' ');
                                        } else {
                                            echo $setting;
                                        } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="<?=$this->path('pimcore_ecommerce_backend_voucher_generate', $this->urlParams)?>" class="btn btn-primary js-loading" data-msg="<?=$this->translateAdmin('plugin_onlineshop_voucherservice_modal_generate-infotext')?>"><?=$this->translateAdmin('plugin_onlineshop_voucherservice_modal_generate-submit-button')?></a>
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->translateAdmin('plugin_onlineshop_voucherservice_modal_cancel')?></button>
            </div>
        </div>
    </div>
</div>
