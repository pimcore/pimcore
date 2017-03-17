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

<div id="cleanup-reservations" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <div class="clearfix"></div>
            </div>
            <div class="modal-body-content">
                <form class="form-horizontal js-cleanup-reservations-modal-form"
                      action="<?=$this->path('pimcore_ecommerce_backend_voucher_cleanup-reservations', $this->urlParams)?>">
                    <h3><?=$this->translateAdmin('plugin_onlineshop_voucherservice_modal_cleanup-reservations-headline')?></h3>
                    <div class="form-group" style="margin-top: 20px">
                        <div class="col col-sm-12">
                            <label for="duration"><?=$this->translateAdmin('plugin_onlineshop_voucherservice_modal_cleanup-reservations-olderthan-x-minutes')?></label>
                            <input type="number" name="duration" id="duration" class="form-control form-control-25 text-center" min="0" value ="5"/>
                        </div>
                        <input type="hidden" name="id" value="<?= $this->getParam('id') ?>">
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <div class="col col-sm-6 text-left">
                    <p><?=$this->translateAdmin('plugin_onlineshop_voucherservice_modal_cleanup-reservations-infotext')?></p>
                </div>
                <button onclick="$('.js-cleanup-reservations-modal-form').submit()" class="btn btn-primary js-loading"
                        data-msg="Cleaning up Tokens, please wait."><?=$this->translateAdmin('plugin_onlineshop_voucherservice_modal_cleanup-reservations-submit')?>
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->translateAdmin('plugin_onlineshop_voucherservice_modal_cancel')?></button>
            </div>
        </div>
    </div>
</div>