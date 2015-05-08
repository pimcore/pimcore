<?php
/**
 * Created by PhpStorm.
 * User: tballmann
 * Date: 08.05.2015
 * Time: 11:21
 *
 * @var OnlineShop_Framework_AbstractOrderItem $orderItem;
 */

$orderItem = $this->orderItem;
$urlSave = $this->url();
?>
<form action="<?= $urlSave ?>" method="post">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
        <h4 class="modal-title"><?= $orderItem->getProductName() ?> <small><?= $this->translate('online-shop.back-office.order.item-cancel') ?></small></h4>
    </div>
    <div class="modal-body">
        <div class="row form-group">
            <label class="col-sm-12" for="inputMessage"><?= $this->translate('online-shop.back-office.order.item-cancel.message') ?></label>
            <div class="col-sm-12"><textarea class="form-control" name="message" rows="8" id="inputMessage"></textarea></div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="submit" class="btn btn-danger" name="confirmed" value="1"><?= $this->translate('online-shop.back-office.order.item-cancel') ?></button>
    </div>
</form>