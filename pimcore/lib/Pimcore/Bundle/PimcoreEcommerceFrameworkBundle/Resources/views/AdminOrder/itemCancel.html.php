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


$orderItem = $this->orderItem;
$urlSave = $this->pimcoreUrl();
?>
<form action="<?= $urlSave ?>" method="post">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
        <h4 class="modal-title"><?= $orderItem->getProductName() ?> <small><?= $this->translateAdmin('online-shop.back-office.order.item-cancel') ?></small></h4>
    </div>
    <div class="modal-body">
        <div class="row form-group">
            <label class="col-sm-12" for="inputMessage"><?= $this->translateAdmin('online-shop.back-office.order.item-cancel.message') ?></label>
            <div class="col-sm-12"><textarea class="form-control" name="message" rows="8" id="inputMessage"></textarea></div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="submit" class="btn btn-danger" name="confirmed" value="1"><?= $this->translateAdmin('online-shop.back-office.order.item-cancel') ?></button>
    </div>
</form>