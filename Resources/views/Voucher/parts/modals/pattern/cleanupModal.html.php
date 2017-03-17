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
 */
?>

<div id="cleanUp" class="modal fade">
    <div class="modal-dialog">
        <form class="form-horizontal js-cleanup-modal-form"
              action="<?=$this->path('pimcore_ecommerce_backend_voucher_cleanup')?>" method="get">
        <div class="modal-content">

            <input type="hidden" name="id" value="<?= $this->id ?>">

            <!-- dialog body -->
            <div class="modal-body">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <div class="clearfix"></div>
            </div>
            <div class="modal-body-content">

                    <h3><?=$this->translateAdmin('plugin_onlineshop_voucherservice_modal_cleanup-headline')?></h3>

                    <div class="form-group">
                        <div class="btn-group" data-toggle="buttons">
                            <label class="btn btn-default cleanup-radio">
                                <input type="radio" name="usage" value='used' class="form-control">
                                <?=$this->translateAdmin('plugin_onlineshop_voucherservice_modal_cleanup-used-checkbox')?>
                            </label>

                            <label class="btn btn-default cleanup-radio">
                                <input type="radio" name="usage" value='unused' class="form-control">
                                <?=$this->translateAdmin('plugin_onlineshop_voucherservice_modal_cleanup-unused-checkbox')?>
                            </label>
                            <label class="btn btn-default cleanup-radio">
                                <input type="radio" name="usage" value='both' class="form-control">
                                <?=$this->translateAdmin('plugin_onlineshop_voucherservice_modal_cleanup-both-checkbox')?>
                            </label>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 20px">
                        <div class="col col-sm-6">
                            <label><?=$this->translateAdmin('plugin_onlineshop_voucherservice_modal_cleanup-older-than')?></label>
                            <input type="text" name="olderThan" class="form-control js-datepicker">
                        </div>
                    </div>

            </div>

            <!-- dialog buttons -->
            <div class="modal-footer">
                <div class="col col-sm-6 text-left">
                    <p>
                        <?=$this->translateAdmin('plugin_onlineshop_voucherservice_modal_cleanup-infotext')?>
                    </p>
                </div>
                <button onclick="$('.js-cleanup-modal-form').submit()" type="submit" class="btn btn-primary js-loading" data-msg="<?=$this->translateAdmin('plugin_onlineshop_voucherservice_modal_cleanup-loadingtext')?>">
                    <?=$this->translateAdmin('plugin_onlineshop_voucherservice_modal_cleanup-submit-button')?>
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->translateAdmin('plugin_onlineshop_voucherservice_modal_cancel')?></button>
            </div>
        </div>
        </form>
    </div>
</div>